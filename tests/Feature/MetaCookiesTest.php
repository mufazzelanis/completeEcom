<?php

namespace Tests\Feature;

use App\Http\Middleware\MetaCookies;
use App\Jobs\SendFacebookConversionEvent;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/** Live Tracking showed "Browser ID –" on a visitor's first page: the pixel hadn't set _fbp yet. */
class MetaCookiesTest extends TestCase
{
    use RefreshDatabase;

    private const BROWSER = ['User-Agent' => 'Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36 Chrome/130.0 Mobile Safari/537.36', 'Accept-Language' => 'bn-BD', 'Sec-Fetch-Mode' => 'navigate'];

    protected function setUp(): void
    {
        parent::setUp();
        Setting::set('facebook_pixel_id', '1124293699946144');
    }

    public function test_first_page_view_sets_meta_cookies_in_meta_format(): void
    {
        $res = $this->withHeaders(self::BROWSER)->get('/?fbclid=IwAR_Abc-123xyz_789');

        $fbp = $res->getCookie('_fbp', false);
        $fbc = $res->getCookie('_fbc', false);
        $this->assertMatchesRegularExpression('/^fb\.1\.\d{13}\.\d{10}$/', $fbp->getValue());
        $this->assertMatchesRegularExpression('/^fb\.1\.\d{13}\.IwAR_Abc-123xyz_789$/', $fbc->getValue()); // fbclid exactly as sent
        $this->assertFalse($fbp->isHttpOnly()); // the pixel script must be able to read it
    }

    public function test_existing_cookies_are_kept_and_bots_get_none(): void
    {
        $this->withHeaders(self::BROWSER)->withUnencryptedCookie('_fbp', 'fb.1.1700000000000.1234567890')
            ->get('/')->assertCookieMissing('_fbp');

        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'])
            ->get('/?fbclid=IwAR_Abc-123xyz_789')->assertCookieMissing('_fbp')->assertCookieMissing('_fbc');
    }

    public function test_no_pixel_no_cookies(): void
    {
        Setting::set('facebook_pixel_id', '');
        $this->withHeaders(self::BROWSER)->get('/')->assertCookieMissing('_fbp');
    }

    public function test_the_first_server_event_already_has_the_browser_id(): void
    {
        Queue::fake();
        Setting::set('facebook_capi_access_token', 'T');
        Setting::set('facebook_capi_enabled', '1');
        $cat = Category::forceCreate(['name' => 'Hair', 'slug' => 'hair', 'is_active' => true]);
        $p = Product::forceCreate(['category_id' => $cat->id, 'name' => 'Biotin', 'slug' => 'biotin', 'price' => 900, 'stock' => 3, 'is_active' => true, 'approval_status' => 'approved']);

        $res = $this->withHeaders(self::BROWSER)->get(route('products.show', $p) . '?fbclid=IwAR_Abc-123xyz_789');
        $fbp = $res->getCookie('_fbp', false)->getValue();

        Queue::assertPushed(SendFacebookConversionEvent::class, function ($job) use ($fbp) {
            $e = tap((new \ReflectionObject($job))->getProperty('event'))->setAccessible(true)->getValue($job);
            return $e['event_name'] === 'ViewContent' && ($e['user_data']['fbp'] ?? null) === $fbp && str_ends_with($e['user_data']['fbc'] ?? '', '.IwAR_Abc-123xyz_789');
        });
    }

    public function test_cookie_domain_matches_the_pixel(): void
    {
        $this->assertSame('.mitavin.com', MetaCookies::cookieDomain('www.mitavin.com'));
        $this->assertSame('.mitavin.com', MetaCookies::cookieDomain('mitavin.com'));
        $this->assertNull(MetaCookies::cookieDomain('localhost'));
        $this->assertNull(MetaCookies::cookieDomain('127.0.0.1'));
    }
}
