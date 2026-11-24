<?php

namespace Tests\Feature;

use App\Models\FacebookConversionLog;
use App\Models\LandingPage;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Admin → Live & Traffic shows the Conversions API events next to the visitors, live. */
class LiveFacebookEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_live_page_and_feed_show_todays_facebook_events(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Setting::set('two_factor_enabled', '0');
        Setting::set('facebook_pixel_id', '1124');
        Setting::set('facebook_capi_access_token', 'T');
        Setting::set('facebook_capi_enabled', '1');
        $lp = LandingPage::forceCreate(['title' => 'Collagen Offer', 'slug' => 'collagen', 'status' => 'published']);

        $log = fn (array $a) => FacebookConversionLog::create($a + ['pixel_id' => '1124', 'http_status' => 200, 'response' => '{"events_received":1}', 'status' => 'sent']);
        $log(['event_name' => 'ViewContent', 'event_id' => 'a']);
        $log(['event_name' => 'ViewContent', 'event_id' => 'b']);
        $log(['event_name' => 'Purchase', 'event_id' => 'purchase_135', 'landing_page_id' => $lp->id]);
        $log(['event_name' => 'AddToCart', 'event_id' => 'c', 'status' => 'failed', 'http_status' => 400, 'response' => '{"error":{"message":"Invalid OAuth access token"}}']);
        $log(['event_name' => 'ViewContent', 'event_id' => 'old', 'sent_at' => now()->subDays(3)]); // not today

        $page = $this->actingAs($admin)->get(route('admin.live-visitors.index'))->assertOk();
        $page->assertSee('Facebook events')
            ->assertSee('Viewed product')
            ->assertSee('1 failed')
            ->assertSee('Order #135')
            ->assertSee('Collagen Offer')
            ->assertSee('🏬 Main site', false)
            ->assertSee('Invalid OAuth access token');
        $this->assertMatchesRegularExpression('/Viewed product<\/p>\s*<p[^>]*>\s*2\b/', $page->getContent()); // today only

        // the 5-second live refresh carries it too
        $this->actingAs($admin)->getJson(route('admin.live-visitors.feed'))->assertOk()
            ->assertJsonPath('html', fn ($html) => str_contains($html, 'Facebook events') && str_contains($html, 'Order #135'));
    }

    /** Each event shows its product and opens it in one click. */
    public function test_events_record_and_link_their_product(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Setting::set('two_factor_enabled', '0');
        $cat = \App\Models\Category::forceCreate(['name' => 'Hair', 'slug' => 'hair', 'is_active' => true]);
        $product = \App\Models\Product::forceCreate(['category_id' => $cat->id, 'name' => 'Biotin 5000mcg', 'slug' => 'biotin-5000',
            'price' => 1200, 'stock' => 5, 'is_active' => true, 'approval_status' => 'approved']);

        \Illuminate\Support\Facades\Http::fake(['graph.facebook.com/*' => \Illuminate\Support\Facades\Http::response(['events_received' => 1])]);
        (new \App\Jobs\SendFacebookConversionEvent('v21.0', '1124', 'T', null, [
            'event_name' => 'AddToCart', 'event_id' => 'x1', 'event_source_url' => url('/products/biotin-5000'),
            'custom_data' => ['content_ids' => [(string) $product->id, '99'], 'value' => 2400, 'currency' => 'BDT'],
        ]))->handle();

        $row = FacebookConversionLog::sole();
        $this->assertSame([$product->id, 2, '2400.00'], [$row->product_id, $row->items, (string) $row->value]);

        $this->actingAs($admin)->get(route('admin.live-visitors.index'))->assertOk()
            ->assertSee('Biotin 5000mcg')
            ->assertSee('+1 more')
            ->assertSee('৳2,400')
            ->assertSee('href="' . route('products.show', $product) . '"', false);
    }
}
