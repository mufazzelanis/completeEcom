<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPageFormTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Setting::set('two_factor_enabled', '0');

        return User::factory()->create(['role' => 'admin']);
    }

    private function form(array $over = []): array
    {
        return $over + ['title' => 'About Us', 'type' => 'static', 'template' => 'default', 'content' => '<p>We sell original products.</p>', 'is_active' => '1'];
    }

    public function test_create_form_has_a_slug_field_and_uses_it(): void
    {
        $this->actingAs($this->admin());
        $this->get(route('admin.pages.create'))->assertOk()->assertSee('name="slug"', false)->assertSee('Search engine preview');

        $this->post(route('admin.pages.store'), $this->form(['slug' => 'about-mitavin']))->assertSessionHasNoErrors();
        $this->get('/pages/about-mitavin')->assertOk()->assertSee('We sell original products.');

        // no slug → from the title; a Bangla-only title still gets a working address
        $this->post(route('admin.pages.store'), $this->form(['title' => 'Terms & Conditions']))->assertSessionHasNoErrors();
        $this->assertTrue(Page::where('slug', 'terms-conditions')->exists());
        $this->post(route('admin.pages.store'), $this->form(['title' => 'আমাদের সম্পর্কে']))->assertSessionHasNoErrors();
        $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', Page::where('title', 'আমাদের সম্পর্কে')->value('slug'));

        $this->post(route('admin.pages.store'), $this->form(['slug' => 'আমাদের']))->assertSessionHasErrors('slug');
    }

    public function test_changing_the_slug_redirects_the_old_address(): void
    {
        $this->actingAs($this->admin());
        $this->post(route('admin.pages.store'), $this->form(['slug' => 'about']));
        $page = Page::where('slug', 'about')->firstOrFail();

        $this->get(route('admin.pages.edit', $page))->assertOk()->assertSee('name="slug"', false)->assertSee("slug: 'about'", false);
        $this->put(route('admin.pages.update', $page), $this->form(['slug' => 'about-us']))->assertSessionHasNoErrors();

        $this->assertSame('about-us', $page->fresh()->slug);
        $this->get('/pages/about')->assertRedirect('/pages/about-us');
        $this->get('/pages/about-us')->assertOk();
    }

    public function test_menu_pages_keep_their_address(): void
    {
        $this->actingAs($this->admin());
        $page = Page::create(['title' => 'Return Policy', 'slug' => 'return-policy', 'type' => 'static', 'is_active' => true]);

        $this->put(route('admin.pages.update', $page), $this->form(['title' => 'Return Policy', 'slug' => 'returns']))->assertSessionHasNoErrors();
        $this->assertSame('return-policy', $page->fresh()->slug);
    }
}
