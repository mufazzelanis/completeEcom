<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\LiveVisitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Live Error Log: "Data too long for column 'platform'" — Facebook-ad visitors on Android were lost from Live Tracking. */
class LongUserAgentTest extends TestCase
{
    use RefreshDatabase;

    private const FB_ANDROID = 'Mozilla/5.0 (Linux; Android 15; SM-A736B Build/BP2A.250605.031.A3; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/131.0.6778.135 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/490.0.0.48.80;]';
    private const ANDROID_BUILD = 'Mozilla/5.0 (Linux; Android 14; SM-A736B Build/BP2A.250605.031.A3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Mobile Safari/537.36';

    public function test_the_firmware_build_is_dropped_from_the_phone_model(): void
    {
        [, , $platform] = ActivityLog::parseUserAgent(self::ANDROID_BUILD);
        $this->assertSame('Android (SM-A736B)', $platform);

        [, , $platform] = ActivityLog::parseUserAgent('Mozilla/5.0 (Linux; Android 14; ' . str_repeat('X', 80) . ') Chrome/130.0 Mobile');
        $this->assertLessThanOrEqual(40, mb_strlen($platform));
    }

    public function test_such_a_visitor_is_saved_in_live_tracking(): void
    {
        foreach ([self::ANDROID_BUILD, self::FB_ANDROID] as $i => $ua) {
            $this->withHeaders(['User-Agent' => $ua, 'Accept-Language' => 'bn-BD,bn;q=0.9', 'Sec-Fetch-Mode' => 'cors'])
                ->postJson(route('live.ping'), ['vk' => 'visitor-key-abcdef-' . $i, 'url' => url('/checkout'), 'title' => 'Checkout'])
                ->assertNoContent();
        }

        $this->assertSame(2, LiveVisitor::count());
        $this->assertSame('Android (SM-A736B)', LiveVisitor::where('visitor_key', 'visitor-key-abcdef-0')->value('platform'));
    }
}
