<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\FacebookConversionLog;
use App\Models\LandingPage;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Admin → Live & Traffic → Meta events stream: filters, copyable event IDs, status, product links. */
class LiveFacebookEventsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private LandingPage $lp;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::set('two_factor_enabled', '0');
        Setting::set('facebook_pixel_id', '1124');
        Setting::set('facebook_capi_access_token', 'T');
        Setting::set('facebook_capi_enabled', '1');
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->lp = LandingPage::forceCreate(['title' => 'Collagen Offer', 'slug' => 'collagen', 'status' => 'published']);

        $log = fn (array $a) => FacebookConversionLog::create($a + ['pixel_id' => '1124', 'http_status' => 200, 'response' => '{"events_received":1}', 'status' => 'sent']);
        $log(['event_name' => 'ViewContent', 'event_id' => 'view-aaa']);
        $log(['event_name' => 'ViewContent', 'event_id' => 'view-bbb']);
        $log(['event_name' => 'Purchase', 'event_id' => 'purchase_135', 'landing_page_id' => $this->lp->id, 'value' => 3460]);
        $log(['event_name' => 'AddToCart', 'event_id' => 'cart-ccc', 'status' => 'failed', 'http_status' => 400, 'response' => '{"error":{"message":"Invalid OAuth access token"}}']);
        $log(['event_name' => 'ViewContent', 'event_id' => 'view-old', 'sent_at' => now()->subDays(3)]); // not today
    }

    private function stream(array $q = []): string
    {
        return $this->actingAs($this->admin)->getJson(route('admin.live-visitors.fb-events', $q))->assertOk()->json('html');
    }

    public function test_page_shows_the_stream_with_copyable_ids_and_status(): void
    {
        $page = $this->actingAs($this->admin)->get(route('admin.live-visitors.index'))->assertOk();

        $page->assertSee('Meta events stream')
            ->assertSee('Search event ID', false)
            ->assertSee('🚀 Collagen Offer', false)            // the source filter lists the landing page
            ->assertSee('Order #135')
            ->assertSee('✓ Sent', false)
            ->assertSee('✕ Failed', false)
            ->assertSee('Invalid OAuth access token')
            ->assertSee('aria-label="Copy event ID purchase_135"', false)
            ->assertSee("copy('purchase_135')", false)
            ->assertDontSee('view-old');                       // default = today
    }

    public function test_filters_by_event_id_event_source_status_and_time(): void
    {
        $this->assertStringContainsString('purchase_135', $html = $this->stream(['fb_q' => 'purchase_13']));
        $this->assertStringNotContainsString('view-aaa', $html);

        $html = $this->stream(['fb_event' => 'ViewContent']);
        $this->assertStringContainsString('view-aaa', $html);
        $this->assertStringNotContainsString('purchase_135', $html);

        $html = $this->stream(['fb_source' => 'lp:' . $this->lp->id]);
        $this->assertStringContainsString('purchase_135', $html);
        $this->assertStringNotContainsString('view-aaa', $html);

        $html = $this->stream(['fb_source' => 'main']);
        $this->assertStringContainsString('view-aaa', $html);
        $this->assertStringNotContainsString('purchase_135', $html);

        $html = $this->stream(['fb_status' => 'failed']);
        $this->assertStringContainsString('cart-ccc', $html);
        $this->assertStringNotContainsString('view-aaa', $html);

        $this->assertStringContainsString('view-old', $this->stream(['fb_range' => '7d']));
        $this->assertStringNotContainsString('view-aaa', $this->stream(['fb_range' => 'yesterday']));

        $from = now(setting('timezone', 'Asia/Dhaka'))->subDays(4)->format('Y-m-d\TH:i');
        $to = now(setting('timezone', 'Asia/Dhaka'))->subDays(2)->format('Y-m-d\TH:i');
        $html = $this->stream(['fb_range' => 'custom', 'fb_from' => $from, 'fb_to' => $to]);
        $this->assertStringContainsString('view-old', $html);
        $this->assertStringNotContainsString('view-aaa', $html);

        // a search that is not a LIKE pattern
        $this->assertStringContainsString('No events match', $this->stream(['fb_q' => '%']));
    }

    public function test_shows_five_and_opens_more_on_request(): void
    {
        foreach (range(1, 20) as $i) {
            FacebookConversionLog::create(['event_name' => 'ViewContent', 'event_id' => "bulk-$i", 'pixel_id' => '1124', 'status' => 'sent', 'http_status' => 200, 'response' => '{}']);
        }
        $count = fn (string $html) => substr_count($html, 'aria-label="Copy event ID');

        $html = $this->stream();
        $this->assertSame(5, $count($html));
        $this->assertStringContainsString('Show 10 more', $html);
        $this->assertStringContainsString('All 24 in logs', $html);
        $this->assertStringNotContainsString('Show less', $html);

        $html = $this->stream(['fb_limit' => 15]);
        $this->assertSame(15, $count($html));
        $this->assertStringContainsString('Show less', $html);

        $this->assertSame(5, $count($this->stream(['fb_limit' => 1])));     // never fewer than 5
        $this->assertSame(24, $count($this->stream(['fb_limit' => 9999]))); // capped at 100, all 24 here
    }

    /** Visitor column: IP, how many events, new / returning, network and place; click the IP to see only that visitor. */
    public function test_visitor_column_and_ip_filter(): void
    {
        config(['cache.default' => 'array']);
        $ip = '115.127.10.100';
        \Illuminate\Support\Facades\Cache::put('ip_intel:' . $ip, ['ok' => true, 'city' => 'Dhaka', 'country' => 'Bangladesh', 'cc' => 'BD', 'isp' => 'BRACNet Limited', 'org' => null, 'asn' => null, 'asname' => null, 'hosting' => false, 'proxy' => false, 'mobile' => false], 3600);
        $mk = fn ($id, $at = null) => FacebookConversionLog::create(['event_name' => 'ViewContent', 'event_id' => $id, 'pixel_id' => '1124', 'status' => 'sent', 'http_status' => 200, 'response' => '{}',
            'ip_address' => $ip, 'user_agent' => 'Mozilla/5.0 (Linux; Android 14) Chrome/130 Mobile', 'sent_at' => $at ?? now()]);
        $mk('ip-1'); $mk('ip-2'); $mk('ip-3');
        \Illuminate\Support\Facades\DB::table('activity_logs')->insert(['event' => 'page.view', 'description' => 'x', 'ip_address' => $ip, 'created_at' => now()->subDays(5)]);

        $html = $this->stream();
        $this->assertStringContainsString($ip, $html);
        $this->assertStringContainsString('3×', $html);
        $this->assertStringContainsString('Returning', $html);
        $this->assertStringContainsString('BRACNet', $html);
        $this->assertStringContainsString('Dhaka, Bangladesh', $html);
        $this->assertStringContainsString("q = '115.127.10.100'; apply()", $html);

        // the search box takes an IP too: only that visitor's events
        $html = $this->stream(['fb_q' => $ip]);
        $this->assertSame(3, substr_count($html, 'aria-label="Copy event ID'));
        $this->assertStringNotContainsString('view-aaa', $html);
    }

    public function test_job_records_the_visitor_ip_and_browser(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['events_received' => 1])]);
        (new \App\Jobs\SendFacebookConversionEvent('v21.0', '1124', 'T', null, [
            'event_name' => 'ViewContent', 'event_id' => 'ua1',
            'user_data' => ['client_ip_address' => '103.4.5.6', 'client_user_agent' => 'Mozilla/5.0 Test'],
        ]))->handle();

        $row = FacebookConversionLog::where('event_id', 'ua1')->sole();
        $this->assertSame(['103.4.5.6', 'Mozilla/5.0 Test'], [$row->ip_address, $row->user_agent]);
    }

    public function test_only_new_rows_glow_on_a_live_poll(): void
    {
        $top = FacebookConversionLog::max('id');
        $new = FacebookConversionLog::create(['event_name' => 'AddToCart', 'event_id' => 'cart-new', 'pixel_id' => '1124', 'status' => 'sent', 'http_status' => 200, 'response' => '{}']);

        $res = $this->actingAs($this->admin)->getJson(route('admin.live-visitors.fb-events', ['fb_after' => $top]))->assertOk();
        $this->assertSame($new->id, $res->json('top'));
        $this->assertSame(1, substr_count($res->json('html'), 'fbx-new'));
    }

    /** Each event shows its product and opens it in one click. */
    public function test_events_record_and_link_their_product(): void
    {
        $cat = Category::forceCreate(['name' => 'Hair', 'slug' => 'hair', 'is_active' => true]);
        $product = Product::forceCreate(['category_id' => $cat->id, 'name' => 'Biotin 5000mcg', 'slug' => 'biotin-5000',
            'price' => 1200, 'stock' => 5, 'is_active' => true, 'approval_status' => 'approved']);

        Http::fake(['graph.facebook.com/*' => Http::response(['events_received' => 1])]);
        (new \App\Jobs\SendFacebookConversionEvent('v21.0', '1124', 'T', null, [
            'event_name' => 'AddToCart', 'event_id' => 'x1', 'event_source_url' => url('/products/biotin-5000'),
            'custom_data' => ['content_ids' => [(string) $product->id, '99'], 'value' => 2400, 'currency' => 'BDT'],
        ]))->handle();

        $row = FacebookConversionLog::where('event_id', 'x1')->sole();
        $this->assertSame([$product->id, 2, '2400.00'], [$row->product_id, $row->items, (string) $row->value]);

        $html = $this->stream(['fb_q' => 'x1']);
        $this->assertStringContainsString('Biotin 5000mcg', $html);
        $this->assertStringContainsString('+1 more product', $html);
        $this->assertStringContainsString('৳2,400', $html);
        $this->assertStringContainsString('href="' . route('products.show', $product) . '"', $html);
    }
}
