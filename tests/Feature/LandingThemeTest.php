<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\LandingPage;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Support\LandingThemes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Landing page designs: one page's content shown in every theme, and orders still placed from it. */
class LandingThemeTest extends TestCase
{
    use RefreshDatabase;

    private function page(string $theme, array $over = []): LandingPage
    {
        Setting::set('phone_check', 'off');
        Setting::bust();
        $category = Category::forceCreate(['name' => 'Supplements', 'slug' => 'supplements']);
        $product = Product::forceCreate([
            'category_id' => $category->id, 'name' => 'Biotin 5000mcg', 'slug' => 'biotin', 'price' => 1200,
            'stock' => 20, 'is_active' => true, 'approval_status' => 'approved', 'image' => 'products/biotin.jpg',
        ]);

        return LandingPage::forceCreate($over + [
            'title' => 'Biotin Offer', 'slug' => 'biotin-offer', 'product_id' => $product->id, 'status' => 'published', 'theme' => $theme,
            'hero_heading' => 'চুল পড়া কমাতে বায়োটিন', 'order_button_text' => 'অর্ডার করুন', 'thank_you_heading' => 'ধন্যবাদ!',
            'compare_at_price' => 1500, 'collect_address' => true, 'require_address' => true,
            'benefits' => [['icon' => '💇', 'title' => 'চুল পড়া কমায়', 'description' => 'গোড়া মজবুত করে', 'image' => null]],
            'who_for' => [['icon' => '😟', 'text' => 'যাদের **চুল বেশি পড়ছে**', 'image' => null]],
            'faqs' => [['question' => 'অরিজিনাল কি?', 'answer' => 'হ্যাঁ, USA থেকে আমদানি।']],
            'trust_badges' => [['icon' => '✅', 'text' => '১০০% অরিজিনাল', 'image' => null]],
            'how_it_works_video' => 'https://youtu.be/abcDEF12345',
            'delivery_zones' => [['label' => 'ঢাকার ভিতরে', 'charge' => 60], ['label' => 'ঢাকার বাইরে', 'charge' => 120]],
        ]);
    }

    public function test_every_theme_shows_the_page_content_with_one_h1_and_the_order_form(): void
    {
        foreach (array_diff(LandingThemes::keys(), ['classic']) as $theme) {
            LandingPage::query()->delete();
            Product::query()->delete();
            Category::query()->delete();
            $this->page($theme);

            $html = $this->get('/biotin-offer')->assertOk()
                ->assertSee('class="lt lt--' . $theme . '"', false)
                ->assertSee('চুল পড়া কমাতে বায়োটিন')
                ->assertSee('চুল পড়া কমায়')
                ->assertSee('অরিজিনাল কি?')
                ->assertSee('id="hero-cta"', false)
                ->assertSee('id="order-form"', false)
                ->assertSee('name="phone"', false)
                ->assertSee('name="address"', false)
                ->assertSee('name="quantity"', false)
                ->assertSee('<link rel="canonical" href="' . url('biotin-offer') . '">', false)
                ->getContent();

            $this->assertSame(1, substr_count($html, '<h1'), "$theme has one h1");
            // the video is a picture until tapped: no player is loaded with the page
            // (a <template> is inert: the browser builds it only when the play button is tapped)
            $this->assertStringNotContainsString('<iframe', preg_replace('#<template\b.*?</template>#s', '', $html), "$theme loads no video player up front");
            $this->assertStringContainsString('i.ytimg.com/vi/abcDEF12345/hqdefault.jpg', $html);
        }
    }

    public function test_the_offer_theme_puts_the_order_form_before_the_benefits(): void
    {
        $this->page('offer');
        $html = $this->get('/biotin-offer')->getContent();

        $this->assertLessThan(strpos($html, 'id="benefits"'), strpos($html, 'id="order-form"'));
    }

    public function test_a_classic_page_keeps_its_original_design(): void
    {
        $this->page('classic');

        $this->get('/biotin-offer')->assertOk()->assertDontSee('lt lt--', false)->assertSee('max-w-md mx-auto', false);
    }

    public function test_an_unknown_theme_falls_back_to_classic(): void
    {
        $this->page('neon');

        $this->get('/biotin-offer')->assertOk()->assertDontSee('lt lt--', false);
    }

    public function test_a_custom_brand_colour_recolours_the_theme(): void
    {
        $this->page('clinical', ['brand_color' => '#7c3aed']);

        $this->get('/biotin-offer')->assertSee('--brand:#7c3aed;', false);
    }

    public function test_an_order_placed_from_a_themed_page_is_saved(): void
    {
        $lp = $this->page('care');

        $this->post('/biotin-offer', [
            'name' => 'Nusaiba Haque', 'phone' => '01518303867', 'address' => 'Gulshan-1, Dhaka', 'quantity' => 2, 'delivery_zone' => 1,
        ])->assertRedirect(route('landing.show', $lp));

        $order = Order::with('items')->where('landing_page_id', $lp->id)->first();
        $this->assertNotNull($order);
        $this->assertSame(2, (int) $order->items->first()->quantity);
        // thank-you screen
        $this->get('/biotin-offer')->assertOk()->assertSee('ধন্যবাদ!');
    }

    public function test_the_admin_saves_the_chosen_theme_and_rejects_unknown_ones(): void
    {
        Setting::set('two_factor_enabled', '0');
        $admin = User::factory()->create(['role' => 'admin']);
        $lp = $this->page('classic');
        $form = ['title' => 'Biotin Offer', 'slug' => 'biotin-offer', 'status' => 'published', 'product_id' => $lp->product_id];

        $this->actingAs($admin)->put(route('admin.landing-pages.update', $lp), $form + ['theme' => 'premium'])->assertSessionHasNoErrors();
        $this->assertSame('premium', $lp->fresh()->theme);

        $this->actingAs($admin)->put(route('admin.landing-pages.update', $lp), $form + ['theme' => 'neon'])->assertSessionHasErrors('theme');
        $this->assertSame('premium', $lp->fresh()->theme);

        $this->actingAs($admin)->get(route('admin.landing-pages.edit', $lp))->assertOk()->assertSee('Design Theme')->assertSee('Premium Dark');
    }

    public function test_dark_brand_colours_get_white_button_text_and_light_ones_dark(): void
    {
        $this->assertSame('#ffffff', LandingThemes::readableOn('#0e7490'));
        $this->assertSame('#14110a', LandingThemes::readableOn('#facc15'));
    }
}
