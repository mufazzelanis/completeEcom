<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\BlogRead;
use App\Models\Setting;
use App\Models\User;
use App\Support\VisitorIntel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Blog analytics: every article view is recorded with who it really was (person, bot by name,
 * cloud server), only real interaction makes a reader, and the tracker endpoint can't be gamed.
 */
class BlogAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private const IPHONE = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1';

    private const GOOGLEBOT = 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';

    private BlogPost $post;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::set('two_factor_enabled', '0');
        Setting::bust();
        $this->post = BlogPost::create([
            'user_id' => User::factory()->create()->id, 'title' => 'Test post', 'slug' => 'test-post',
            'content' => '<p>Hello readers</p>', 'status' => 'published', 'published_at' => now()->subDay(),
        ]);
    }

    private function browser(string $ua = self::IPHONE, string $ip = '103.230.104.10'): static
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->withHeaders(['User-Agent' => $ua, 'Accept-Language' => 'en-US']);
    }

    private function openPost(string $ua = self::IPHONE, string $ip = '103.230.104.10'): ?BlogRead
    {
        $this->browser($ua, $ip)->get('/blog/test-post')->assertOk();

        return BlogRead::latest('id')->first();
    }

    private function beacon(BlogRead|string $read, array $extra = [], ?string $sig = null)
    {
        $key = $read instanceof BlogRead ? $read->read_key : $read;

        return $this->browser()->postJson('/blog-track', $extra + [
            'r' => $key, 'k' => $sig ?? BlogRead::sign($key, $this->post->id),
            'v' => 'visitor-1', 'p' => $this->post->id, 't' => 5, 's' => 20, 'e' => 'start',
        ]);
    }

    public function test_a_page_view_is_recorded_but_is_not_a_reader_until_a_real_interaction(): void
    {
        $read = $this->openPost();

        $this->assertNotNull($read);
        $this->assertNull($read->bot_name);
        $this->assertNull($read->engaged_at);
        $this->assertSame('103.230.104.10', $read->ip);

        $this->beacon($read)->assertNoContent();

        $read->refresh();
        $this->assertNotNull($read->engaged_at);
        $this->assertSame('visitor-1', $read->visitor_key);
        $this->assertSame(1, BlogRead::count(), 'the beacon continues the same row');
    }

    public function test_the_page_hands_its_tracker_a_signed_key(): void
    {
        $html = $this->browser()->get('/blog/test-post')->getContent();
        $read = BlogRead::latest('id')->first();

        $this->assertStringContainsString(BlogRead::sign($read->read_key, $this->post->id), $html);
    }

    public function test_googlebot_is_recorded_by_name_and_never_becomes_a_reader(): void
    {
        $read = $this->openPost(self::GOOGLEBOT, '66.249.66.1');

        $this->assertSame('Googlebot', $read->bot_name);
        $this->assertSame('search', $read->bot_kind);

        $this->beacon($read); // even if it ran the script and sent one
        $this->assertNull($read->refresh()->engaged_at);
    }

    public function test_a_script_without_browser_headers_is_a_scraper(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '3.89.78.219'])
            ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36'])
            ->get('/blog/test-post')->assertOk();

        $this->assertSame('Scraper posing as a browser', BlogRead::latest('id')->value('bot_name'));
    }

    public function test_a_forged_or_made_up_read_key_is_rejected(): void
    {
        $read = $this->openPost();

        $this->beacon($read, [], str_repeat('a', 32))->assertNoContent();
        $this->assertNull($read->refresh()->engaged_at, 'wrong signature');

        $this->beacon('made-up-key-123', [], str_repeat('b', 32))->assertNoContent();
        $this->assertSame(1, BlogRead::count(), 'no row minted for an unknown key');

        // an old page without a signature: quiet 204, never a redirect back into the article
        $this->beacon($read, ['k' => null])->assertNoContent();
        $this->assertSame(1, BlogRead::count(), 'no phantom second view');
    }

    public function test_reading_time_cannot_exceed_the_time_since_the_page_was_served(): void
    {
        $read = $this->openPost();

        $this->beacon($read, ['t' => 3600, 's' => 100]);

        $this->assertLessThanOrEqual(45, $read->refresh()->active_seconds);
        $this->assertSame(100, $read->max_scroll);
    }

    public function test_product_clicks_are_capped_per_view(): void
    {
        $category = \App\Models\Category::forceCreate(['name' => 'Demo', 'slug' => 'demo']);
        $product = \App\Models\Product::forceCreate([
            'category_id' => $category->id, 'name' => 'Demo', 'slug' => 'demo', 'price' => 100, 'stock' => 5, 'is_active' => true,
        ]);
        $read = $this->openPost();
        $this->beacon($read);
        BlogRead::whereKey($read->id)->update(['product_clicks' => 30]);

        $this->beacon($read, ['e' => 'click', 'pid' => $product->id, 'a' => 'open', 'pl' => 'inline']);

        $this->assertSame(30, $read->refresh()->product_clicks);
        $this->assertSame(0, \App\Models\BlogProductClick::count());
    }

    public function test_staff_viewing_their_own_post_are_not_counted(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->browser()->get('/blog/test-post')->assertOk();

        $this->assertSame(0, BlogRead::count());
    }

    public function test_analytics_pages_are_admin_only(): void
    {
        foreach (['/admin/blog/analytics', '/admin/blog/analytics/visits', '/admin/blog/analytics/test-post'] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }

        $this->actingAs(User::factory()->create(['role' => 'customer']))->get('/admin/blog/analytics')->assertForbidden();
    }

    public function test_admin_sees_bots_and_servers_by_name(): void
    {
        Http::fake(['ip-api.com/*' => Http::response([
            ['status' => 'success', 'query' => '66.249.66.1', 'as' => 'AS15169 Google LLC', 'isp' => 'Google LLC', 'org' => 'Google LLC', 'hosting' => true, 'mobile' => false, 'proxy' => false, 'country' => 'United States', 'countryCode' => 'US', 'city' => 'Mountain View'],
            ['status' => 'success', 'query' => '3.89.78.219', 'as' => 'AS14618 Amazon.com, Inc.', 'isp' => 'Amazon.com, Inc.', 'org' => 'AWS EC2 (us-east-1)', 'hosting' => true, 'mobile' => false, 'proxy' => false, 'country' => 'United States', 'countryCode' => 'US', 'city' => 'Ashburn'],
        ])]);
        VisitorIntel::lookup(['66.249.66.1', '3.89.78.219']);

        $this->openPost(self::GOOGLEBOT, '66.249.66.1');     // the real Googlebot
        $this->openPost(self::GOOGLEBOT, '3.89.78.219');     // claims Googlebot, comes from AWS
        $this->openPost(self::IPHONE, '3.89.78.219');        // a server, never touched the page

        $this->openPost(self::IPHONE, '37.111.192.10');      // network not looked up yet

        // what visitors:resolve-ips does every minute
        $this->assertSame(3, VisitorIntel::stampBlogReads(VisitorIntel::pendingBlogIps()));
        $this->assertSame(['37.111.192.10'], VisitorIntel::pendingBlogIps()->all());
        $this->assertTrue(BlogRead::where('ip', '66.249.66.1')->value('bot_verified'));
        $this->assertFalse(BlogRead::where('ip', '3.89.78.219')->whereNotNull('bot_name')->value('bot_verified'));
        $this->assertSame('Amazon AWS', BlogRead::where('ip', '3.89.78.219')->value('net'));

        $admin = User::factory()->create(['role' => 'admin']);
        $html = $this->actingAs($admin)->get('/admin/blog/analytics/visits')->assertOk()->getContent();
        $this->assertStringContainsString('Fake Googlebot', $html);
        $this->assertStringContainsString('Amazon AWS', $html);

        $traffic = $this->actingAs($admin)->get('/admin/blog/analytics')->assertOk()->viewData('traffic');
        $this->assertSame(['human' => 0, 'bot' => 2, 'cloud' => 1, 'unknown' => 1], $traffic['types']);
        $this->assertSame(1, $traffic['pending']);
        $names = $traffic['names']->keyBy('name');
        $this->assertTrue($names['Googlebot']['verified']);
        $this->assertFalse($names['Fake Googlebot']['verified']);
        $this->assertSame(1, $names['Amazon AWS server']['views']);

        // the visitor log's tabs filter in SQL — every row on the page is of that kind
        $cloud = $this->actingAs($admin)->get('/admin/blog/analytics/visits?type=cloud')->viewData('visits');
        $this->assertSame(1, $cloud->total());
        $this->assertSame('cloud', $cloud->first()->verdict['type']);
        $this->assertSame(1, $this->actingAs($admin)->get('/admin/blog/analytics/visits?type=unknown')->viewData('visits')->total());
    }

    public function test_the_verdict_names_who_it_really_was(): void
    {
        $google = ['ok' => true, 'asn' => 15169, 'as' => 'AS15169 Google LLC', 'isp' => 'Google LLC', 'org' => 'Google LLC', 'hosting' => true, 'mobile' => false, 'proxy' => false];
        $aws = ['ok' => true, 'asn' => 14618, 'as' => 'AS14618 Amazon.com, Inc.', 'isp' => 'Amazon.com, Inc.', 'org' => 'AWS EC2', 'hosting' => true, 'mobile' => false, 'proxy' => false];
        $gp = ['ok' => true, 'asn' => 24389, 'as' => 'AS24389 Grameenphone Limited', 'isp' => 'Grameenphone', 'org' => 'Grameenphone', 'hosting' => false, 'mobile' => true, 'proxy' => false];

        $real = VisitorIntel::verdict(self::GOOGLEBOT, '66.249.66.1', null, null, $google);
        $this->assertSame(['bot', 'Googlebot', true], [$real['type'], $real['name'], $real['verified']]);

        $fake = VisitorIntel::verdict(self::GOOGLEBOT, '3.89.78.219', null, null, $aws);
        $this->assertSame('bot', $fake['type']);
        $this->assertStringContainsString('Fake', $fake['name']);
        $this->assertFalse($fake['verified']);

        $server = VisitorIntel::verdict(self::IPHONE, '3.89.78.219', false, null, $aws);
        $this->assertSame('cloud', $server['type']);
        $this->assertStringContainsString('Amazon AWS', $server['name']);

        $person = VisitorIntel::verdict(self::IPHONE, '37.111.192.10', true, null, $gp);
        $this->assertSame('human', $person['type']);
        $this->assertSame('Grameenphone', $person['network']);

        $this->assertSame('bot', VisitorIntel::verdict('python-requests/2.31.0', null, null, null, null)['type']);
        $this->assertSame('human', VisitorIntel::verdict('Mozilla/5.0 (Linux; Android 13; SM-A145F) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/119.0 Mobile Safari/537.36 Instagram 307.0.0.34.111 Android', null, true, null, null)['type']);
    }
}
