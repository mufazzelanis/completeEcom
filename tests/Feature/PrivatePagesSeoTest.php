<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Search Console: "Indexed, though blocked by robots.txt" for /checkout and /login. Private pages
 * must say noindex AND be fetchable, or Google indexes the bare URL from links without reading it.
 */
class PrivatePagesSeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::set('two_factor_enabled', '0');
        Setting::bust();
    }

    public function test_private_pages_tell_google_not_to_index_them_even_when_they_redirect(): void
    {
        foreach (['/cart', '/checkout', '/login', '/register', '/wishlist', '/track-order', '/account', '/forgot-password'] as $url) {
            $this->assertSame('noindex, follow', $this->get($url)->headers->get('X-Robots-Tag'), $url);
        }
    }

    public function test_robots_txt_lets_google_read_those_pages_but_still_guards_admin_and_unsubscribe_links(): void
    {
        $robots = $this->get('/robots.txt')->assertOk()->getContent();

        foreach (['/cart', '/checkout', '/account', '/login$', '/register$', '/wishlist$', '/orders/', '/track-order'] as $path) {
            $this->assertStringNotContainsString("Disallow: {$path}\n", $robots . "\n", $path);
        }
        $this->assertStringContainsString('Disallow: /admin', $robots);
        $this->assertStringContainsString('Disallow: /email/', $robots);
        $this->assertStringContainsString('Disallow: /newsletter/', $robots);
    }

    public function test_shop_pages_stay_indexable(): void
    {
        $this->assertNull($this->get('/')->headers->get('X-Robots-Tag'));
        $this->assertNull($this->get('/shop')->headers->get('X-Robots-Tag'));
    }
}
