<?php

namespace Tests\Feature;

use App\Models\Redirect;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Old addresses redirected to the homepage are soft 404s — the admin is warned and can turn them into 410s in one click. */
class RedirectHomeWarningTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_redirects_are_flagged_and_can_become_gone(): void
    {
        Setting::set('two_factor_enabled', '0');
        $admin = User::factory()->create(['role' => 'admin']);
        Redirect::create(['source' => '/old-junk', 'target' => url('/'), 'status_code' => 301, 'is_active' => true]);
        Redirect::create(['source' => '/old-cream', 'target' => url('/shop'), 'status_code' => 301, 'is_active' => true]);

        $this->actingAs($admin)->get(route('admin.redirects.index'))->assertOk()
            ->assertSee('1 redirect sends visitors to the homepage', false)->assertSee('Change all to 410 Gone');

        $this->actingAs($admin)->post(route('admin.redirects.home-to-gone'))->assertSessionHas('success');

        $this->assertSame(410, (int) Redirect::where('source', '/old-junk')->value('status_code'));
        $this->assertSame(301, (int) Redirect::where('source', '/old-cream')->value('status_code'));
        $this->get('/old-junk')->assertStatus(410);
        $this->get('/old-cream')->assertRedirect(url('/shop'));
    }
}
