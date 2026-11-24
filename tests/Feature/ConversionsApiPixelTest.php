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

    public function test_landing_pixel_without_its_own_token_never_borrows_the_site_token(): void
    {
        ConversionsApi::track('Purchase', 'e3', pixelIdOverride: '1484');
        Queue::assertNothingPushed();
    }
}
