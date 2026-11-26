<?php

namespace Tests\Feature;

use App\Jobs\SendFacebookConversionEvent;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/** Events Manager: PageView match quality 6.2 — it went from the browser only, with IP / browser / fbp. */
class PageViewTrackingTest extends TestCase
{
    use RefreshDatabase;

    private const BROWSER = ['User-Agent' => 'Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36 Chrome/130.0 Mobile Safari/537.36', 'Accept-Language' => 'bn-BD', 'Sec-Fetch-Mode' => 'navigate'];

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        config(['cache.default' => 'array']);
        Setting::set('facebook_pixel_id', '1124293699946144');
        Setting::set('facebook_pixel_enabled', '1');
        Setting::set('facebook_advanced_matching_enabled', '1');
        Setting::set('facebook_capi_access_token', 'T');
        Setting::set('facebook_capi_enabled', '1');
    }

    private function sent(): array
    {
        $events = [];
        Queue::assertPushed(SendFacebookConversionEvent::class, function ($job) use (&$events) {
            $events[] = tap((new \ReflectionObject($job))->getProperty('event'))->setAccessible(true)->getValue($job);
            return true;
        });
        return $events;
    }

    public function test_browser_and_server_page_view_share_one_event_id(): void
    {
        $html = $this->withHeaders(self::BROWSER)->get('/?fbclid=IwAR_Abc-123xyz_789')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/data-pv="([0-9a-f-]{36})"/', $html);
        preg_match('/data-pv="([0-9a-f-]{36})"/', $html, $m);
        $this->assertStringContainsString("fbq('track','PageView',{},id ? {eventID:id} : undefined)", $html);
        $this->assertStringContainsString('fbq.disablePushState = true;', $html);       // no second, id-less PageView of the pixel's own on Turbo navigations
        $this->assertStringContainsString('fbq.allowDuplicatePageViews = true;', $html); // …and ours goes out on every page

        $pv = collect($this->sent())->firstWhere('event_name', 'PageView');
        $this->assertSame($m[1], $pv['event_id']);
        $this->assertArrayHasKey('external_id', $pv['user_data']);
        $this->assertArrayHasKey('fbp', $pv['user_data']);
        $this->assertArrayHasKey('fbc', $pv['user_data']); // from the ad click
    }

    public function test_no_server_page_view_for_a_hover_prefetch_or_a_bot(): void
    {
        $this->withHeaders(self::BROWSER + ['Sec-Purpose' => 'prefetch'])->get('/')->assertOk();
        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1)'])->get('/')->assertOk();

        Queue::assertNotPushed(SendFacebookConversionEvent::class);
    }

    public function test_the_pixel_gets_the_visitor_id_and_a_returning_customers_details(): void
    {
        Cache::put('capi_who:returningvisitor12345678', ['ph' => hash('sha256', '8801712345678'), 'fn' => hash('sha256', 'rahim')], 600);

        $html = $this->withHeaders(self::BROWSER)->withCookie('mv_vid', 'returningvisitor12345678')->get('/')->assertOk()->getContent();

        $this->assertStringContainsString(hash('sha256', 'v:returningvisitor12345678'), $html); // external_id in fbq('init')
        $this->assertStringContainsString(hash('sha256', '8801712345678'), $html);              // the remembered phone (hashed)
    }
}
