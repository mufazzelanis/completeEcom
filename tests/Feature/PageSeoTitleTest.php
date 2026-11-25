<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Live crawl: "Privacy Policy | Mitavin" / "Return Policy - Mitavin" were under 25 characters. */
class PageSeoTitleTest extends TestCase
{
    use RefreshDatabase;

    public function test_short_page_titles_get_the_shop_line_and_long_ones_stay(): void
    {
        Setting::set('site_name', 'Mitavin');
        Page::forceCreate(['title' => 'Privacy Policy', 'slug' => 'privacy-policy', 'content' => '<p>We keep your data safe.</p>', 'is_active' => true]);
        Page::forceCreate(['title' => 'Return Policy', 'slug' => 'return-policy', 'content' => '<p>Returns within 7 days.</p>', 'is_active' => true, 'meta_title' => 'Return Policy - Mitavin']);
        Page::forceCreate(['title' => 'Shipping', 'slug' => 'shipping', 'content' => '<p>x</p>', 'is_active' => true, 'meta_title' => 'Delivery Charges and Shipping Times Across Bangladesh']);

        $this->get('/pages/privacy-policy')->assertOk()->assertSee('<title>Privacy Policy | Mitavin – Online Shopping in Bangladesh</title>', false);
        $this->get('/pages/return-policy')->assertOk()->assertSee('<title>Return Policy - Mitavin – Online Shopping in Bangladesh</title>', false);
        $this->get('/pages/shipping')->assertOk()->assertSee('<title>Delivery Charges and Shipping Times Across Bangladesh</title>', false);
    }
}
