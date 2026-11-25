<?php

namespace Tests\Feature;

use App\Jobs\SendFacebookConversionEvent;
use App\Models\Setting;
use App\Services\Facebook\ConversionsApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/** Main site and each landing page can run their own pixel + Conversions API token. */
class ConversionsApiPixelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Setting::set('facebook_pixel_id', '1124');
        Setting::set('facebook_capi_access_token', 'SITE-TOKEN');
        Setting::set('facebook_capi_enabled', '1');
    }

    private function sentTo(): array
    {
        $out = [];
        Queue::assertPushed(SendFacebookConversionEvent::class, function ($job) use (&$out) {
            $r = new \ReflectionObject($job);
            $get = fn ($p) => tap($r->getProperty($p))->setAccessible(true)->getValue($job);
            $out[] = $get('pixelId') . ':' . $get('accessToken');
            return true;
        });
        return $out;
    }

    public function test_main_site_uses_the_site_pixel_and_token(): void
    {
        ConversionsApi::track('ViewContent', 'e1');
        $this->assertSame(['1124:SITE-TOKEN'], $this->sentTo());
    }

    public function test_landing_page_uses_its_own_pixel_and_token(): void
    {
        ConversionsApi::track('Purchase', 'e2', pixelIdOverride: '1484', accessTokenOverride: 'LP-TOKEN');
        $this->assertSame(['1484:LP-TOKEN'], $this->sentTo());
    }

    /** Live: the DB default clock (US server) put every logged event 5 hours early. */
    public function test_log_time_comes_from_the_app_clock(): void
    {
        $this->travelTo(now()->setDateTime(2026, 11, 24, 7, 50, 0));
        $log = \App\Models\FacebookConversionLog::create(['event_name' => 'ViewContent', 'event_id' => 'x', 'pixel_id' => '1124', 'status' => 'sent', 'http_status' => 200, 'response' => '']);

        $this->assertSame('2026-11-24 07:50:00', $log->fresh()->sent_at->format('Y-m-d H:i:s'));
    }

    private function userDataFor(\Illuminate\Http\Request $request): array
    {
        $data = null;
        ConversionsApi::track('ViewContent', 'e9', request: $request);
        Queue::assertPushed(SendFacebookConversionEvent::class, function ($job) use (&$data) {
            $data = tap((new \ReflectionObject($job))->getProperty('event'))->setAccessible(true)->getValue($job)['user_data'];
            return true;
        });
        return $data;
    }

    /** Meta Diagnostics: fbp never sent / fbc rebuilt — Laravel couldn't read the pixel's own (unencrypted) cookies. */
    public function test_pixel_cookies_are_not_encrypted_and_go_to_meta_as_is(): void
    {
        $enc = $this->app->make(\Illuminate\Cookie\Middleware\EncryptCookies::class);
        $this->assertTrue($enc->isDisabled('_fbp'));
        $this->assertTrue($enc->isDisabled('_fbc'));

        $req = \Illuminate\Http\Request::create('/products/x', 'GET', [], ['_fbp' => 'fb.1.1700000000000.1234567890', '_fbc' => 'fb.1.1700000000000.IwAR_Abc-123xyz']);
        $user = $this->userDataFor($req);
        $this->assertSame('fb.1.1700000000000.1234567890', $user['fbp']);
        $this->assertSame('fb.1.1700000000000.IwAR_Abc-123xyz', $user['fbc']); // the browser's value, untouched
    }

    public function test_fbc_from_the_ad_click_keeps_fbclid_exactly_and_skips_mangled_ones(): void
    {
        $user = $this->userDataFor(\Illuminate\Http\Request::create('/products/x?fbclid=IwAR3xYz_AbC-dEf123'));
        $this->assertMatchesRegularExpression('/^fb\.1\.\d{13}\.IwAR3xYz_AbC-dEf123$/', $user['fbc']); // case kept, nothing cut

        Queue::fake();
        $user = $this->userDataFor(\Illuminate\Http\Request::create('/products/x?fbclid=' . urlencode('IwAR bad value')));
        $this->assertArrayNotHasKey('fbc', $user);
        $this->assertArrayNotHasKey('fbp', $user);
    }

    /** Meta Diagnostics: a guest's events had only IP + browser ("Missing user data parameters"). */
    public function test_guest_events_carry_an_anonymous_external_id_and_country(): void
    {
        config(['cache.default' => 'array']);
        \Illuminate\Support\Facades\Cache::put('ip_intel:103.4.5.6', ['ok' => true, 'cc' => 'BD', 'city' => 'Dhaka', 'country' => 'Bangladesh'], 3600);
        $req = \Illuminate\Http\Request::create('/products/x', 'GET', [], [], [], ['REMOTE_ADDR' => '103.4.5.6']);
        $req->attributes->set('mv_vid', 'abcdefghijklmnopqrstuvwxyz');

        $user = $this->userDataFor($req);
        $this->assertSame(hash('sha256', 'v:abcdefghijklmnopqrstuvwxyz'), $user['external_id']);
        $this->assertSame(hash('sha256', 'bd'), $user['country']);
    }

    public function test_every_storefront_visit_gets_the_anonymous_visitor_cookie(): void
    {
        $this->get('/')->assertCookie('mv_vid');
        $this->withCookie('mv_vid', 'keepthisidabcdefghijklmnop')->get('/')->assertCookieMissing('mv_vid'); // set once, then kept
    }

    public function test_landing_pixel_without_its_own_token_never_borrows_the_site_token(): void
    {
        ConversionsApi::track('Purchase', 'e3', pixelIdOverride: '1484');
        Queue::assertNothingPushed();
    }
}
