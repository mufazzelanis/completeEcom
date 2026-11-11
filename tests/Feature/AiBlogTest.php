<?php

namespace Tests\Feature;

use App\Models\AiBlogRun;
use App\Models\AiBlogTopic;
use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\AiBlog\Autopilot;
use App\Services\AiBlog\Settings;
use App\Services\AiBlog\Writer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AiBlogTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Cache::flush();
        Setting::set('two_factor_enabled', '0');
        Setting::set('ai_blog_words', '600');
        Settings::saveApiKey('anthropic', 'sk-ant-test-key-123456');
        $this->admin = User::factory()->create(['role' => 'admin']);
        $cat = Category::forceCreate(['name' => 'Moisturizer', 'slug' => 'moisturizer', 'is_active' => true]);
        $this->product = Product::forceCreate(['category_id' => $cat->id, 'name' => 'CeraVe Moisturizing Cream 340g', 'slug' => 'cerave-moisturizing-cream',
            'price' => 1800, 'stock' => 10, 'is_active' => true, 'approval_status' => 'approved',
            'description' => '<p>Cream with three essential ceramides and hyaluronic acid for dry skin.</p>']);
    }

    /** A fake Claude answer (structured output = JSON in a text block). */
    private function claude(array $json, string $model = 'claude-opus-5-5'): array
    {
        return ['id' => 'msg_1', 'type' => 'message', 'role' => 'assistant', 'model' => $model, 'stop_reason' => 'end_turn',
            'content' => [['type' => 'text', 'text' => json_encode($json, JSON_UNESCAPED_UNICODE)]],
            'usage' => ['input_tokens' => 3000, 'output_tokens' => 6000]];
    }

    private function article(string $lang, bool $main = true, string $extra = ''): array
    {
        $id = $this->product->id;
        $para = $lang === 'bn'
            ? str_repeat('শীতে ত্বক শুষ্ক হয়ে যায়, তাই ভালো ময়েশ্চারাইজার দরকার। ', 60)
            : str_repeat('Winter dries the skin, so a good moisturizer helps a lot. ', 60);
        $a = [
            'title'            => $lang === 'bn' ? 'শীতে শুষ্ক ত্বকের যত্ন: সেরা ময়েশ্চারাইজার' : 'Winter Dry Skin Care: The Best Moisturizer',
            'meta_title'       => $lang === 'bn' ? 'শীতে শুষ্ক ত্বকের যত্ন' : 'Winter dry skin care',
            'meta_description' => $lang === 'bn' ? 'শীতে শুষ্ক ত্বকের জন্য কোন ময়েশ্চারাইজার ভালো, কীভাবে ব্যবহার করবেন — সহজ গাইড।' : 'Which moisturizer suits dry skin in winter and how to use it — a simple guide for Bangladesh.',
            'excerpt'          => $lang === 'bn' ? 'শীতের শুষ্ক ত্বকের সহজ সমাধান।' : 'A simple answer to winter dryness.',
            'content_html'     => "<h1>Should be removed</h1><h2>Why</h2><p>{$para}{$extra}</p><p>[[product:{$id}]]</p><p>[[product:999999]]</p>",
            'faq'              => [['question' => $lang === 'bn' ? 'দিনে কতবার?' : 'How often?', 'answer' => $lang === 'bn' ? 'দিনে দুবার।' : 'Twice a day.']],
        ];
        if ($main) {
            $a['slug'] = 'winter dry skin moisturizer';
            $a['tags'] = ['winter care', 'dry skin'];
        }

        return $a;
    }

    /** One bilingual post's worth of answers (Bangla, then English) per $extras entry. */
    private function fakeClaude(string ...$extras): void
    {
        $seq = Http::sequence();
        foreach ($extras ?: [''] as $extra) {
            $seq->push($this->claude($this->article('bn', true, $extra)))->push($this->claude($this->article('en', false, $extra)));
        }
        Http::fake(['api.anthropic.com/*' => $seq]);
    }

    private function topic(array $over = []): AiBlogTopic
    {
        return AiBlogTopic::create($over + ['title' => 'Winter moisturizer guide', 'keyword' => 'dry skin moisturizer', 'post_type' => 'guide',
            'product_ids' => [$this->product->id], 'note' => 'Our best seller every winter; customers ask if it works under makeup — yes.', 'status' => 'queued']);
    }

    public function test_writer_creates_a_bilingual_draft_from_real_product_data(): void
    {
        $this->fakeClaude();
        $post = (new Writer())->write($this->topic());

        $this->assertSame('draft', $post->status); // draft mode is the default
        $this->assertTrue($post->ai_generated);
        $this->assertSame('bn', $post->lang);
        $this->assertSame('winter-dry-skin-moisturizer', $post->slug);
        $this->assertSame('Winter Dry Skin Care: The Best Moisturizer', $post->alt_title);
        $this->assertStringNotContainsString('<h1', $post->content);
        $this->assertStringContainsString('bp-ids-' . $this->product->id, $post->content); // product card
        $this->assertStringNotContainsString('999999', $post->content);                     // unknown product dropped
        $this->assertStringContainsString('সাধারণ জিজ্ঞাসা', $post->content);              // FAQ
        $this->assertStringContainsString('Frequently asked questions', $post->alt_content);
        $this->assertStringContainsString('ডাক্তার', $post->content);                        // disclaimer
        $this->assertSame([$this->product->id], $post->products()->pluck('products.id')->all());
        $this->assertCount(2, $post->tags);
        $this->assertNull($post->review_notes);

        // the brief carried the real product data and the shop's note; the key went in the header
        Http::assertSent(fn ($r) => $r->hasHeader('x-api-key', 'sk-ant-test-key-123456')
            && $r['output_config']['format']['type'] === 'json_schema'
            && str_contains($r['messages'][0]['content'], 'three essential ceramides')
            && str_contains($r['messages'][0]['content'], 'Our best seller every winter'));

        // cost logged: 3000 in × $4 + 6000 out × $20 per 1M = $0.132 per call
        $this->assertEqualsWithDelta(0.264, AiBlogRun::monthSpend(), 0.0001);
    }

    public function test_auto_publish_only_when_every_check_passes(): void
    {
        Setting::set('ai_blog_publish_mode', 'auto');

        $this->fakeClaude('', ' It cures your eczema with no side effect.', '');
        $this->assertSame('published', (new Writer())->write($this->topic())->status);
        BlogPost::query()->update(['title' => 'Something else entirely']); // not a near-duplicate title for the next ones

        // a medical promise keeps it a draft, with the reason for the reviewer
        $post = (new Writer())->write($this->topic(['title' => 'Eczema cream guide']));
        $this->assertSame('draft', $post->status);
        $this->assertStringContainsString('Risky claim', $post->review_notes);
        BlogPost::query()->update(['title' => 'Something else entirely']);

        // no Mitavin Note → draft
        $post = (new Writer())->write($this->topic(['title' => 'Another angle entirely', 'note' => null]));
        $this->assertSame('draft', $post->status);
        $this->assertStringContainsString('Mitavin Note', $post->review_notes);
    }

    public function test_autopilot_writes_once_per_posting_time(): void
    {
        Setting::set('ai_blog_enabled', '1');
        Setting::set('ai_blog_posts_per_day', '1');
        Setting::set('ai_blog_slots', '10:00');
        $this->topic();
        $this->topic(['title' => 'Second topic about sunscreen']);

        $this->travelTo(now('Asia/Dhaka')->setTime(9, 50));
        $this->assertSame('nothing due', (new Autopilot())->tick());

        $this->fakeClaude();
        $this->travelTo(now('Asia/Dhaka')->setTime(10, 1));
        $this->assertStringStartsWith('wrote post', (new Autopilot())->tick());
        $this->assertSame('nothing due', (new Autopilot())->tick()); // slot used
        $this->assertSame(1, BlogPost::count());
        $this->assertSame(1, AiBlogTopic::where('status', 'queued')->count());
    }

    public function test_write_now_works_with_autopilot_off_and_budget_stops_everything(): void
    {
        $topic = $this->topic(['run_now' => true]);
        $this->fakeClaude();
        $this->assertStringStartsWith('wrote post', (new Autopilot())->tick());
        $this->assertSame('done', $topic->fresh()->status);

        Setting::set('ai_blog_monthly_budget', '0.1'); // already spent $0.264
        $this->topic(['title' => 'Budget topic', 'run_now' => true]);
        $this->assertSame('monthly budget reached', (new Autopilot())->tick());
    }

    public function test_a_failed_call_keeps_the_topic_queued_with_the_reason(): void
    {
        Http::fake(['api.anthropic.com/*' => Http::response(['type' => 'error', 'error' => ['type' => 'authentication_error', 'message' => 'invalid x-api-key']], 401)]);
        $topic = $this->topic(['run_now' => true]);

        $this->assertStringStartsWith('failed', (new Autopilot())->tick());
        $topic->refresh();
        $this->assertSame('queued', $topic->status);
        $this->assertSame(1, $topic->attempts);
        $this->assertStringContainsString('API key was rejected', $topic->error);
        $this->assertSame(0, BlogPost::count());
    }

    public function test_both_language_versions_are_pages_with_hreflang_and_a_switch(): void
    {
        $this->fakeClaude();
        $post = (new Writer())->write($this->topic());

        $this->get(route('blog.show', $post))->assertNotFound(); // draft: hidden from the public

        $post->update(['status' => 'published', 'published_at' => now()->subMinute()]);
        $bn = $this->get(route('blog.show', $post))->assertOk();
        $bn->assertSee('<html lang="bn"', false)
            ->assertSee('শীতে শুষ্ক ত্বকের যত্ন: সেরা ময়েশ্চারাইজার')
            ->assertSee('hreflang="en" href="' . route('blog.show.lang', [$post, 'en']) . '"', false)
            ->assertSee('hreflang="x-default"', false)
            ->assertSee('data-blog-lang-pick="en"', false);

        $this->get(route('blog.show.lang', [$post, 'en']))->assertOk()
            ->assertSee('<html lang="en"', false)
            ->assertSee('Winter Dry Skin Care: The Best Moisturizer')
            ->assertSee('<link rel="canonical" href="' . route('blog.show.lang', [$post, 'en']) . '">', false);

        // the main language has only the plain URL
        $this->get(route('blog.show.lang', [$post, 'bn']))->assertRedirect(route('blog.show', $post))->assertStatus(301);

        $sitemap = $this->get('/sitemap.xml')->getContent();
        $this->assertStringContainsString(route('blog.show.lang', [$post, 'en']), $sitemap);
    }

    public function test_admins_can_preview_a_draft_but_it_is_noindex(): void
    {
        $post = BlogPost::create(['title' => 'Draft post', 'slug' => 'draft-post', 'content' => '<p>Hello</p>', 'status' => 'draft', 'user_id' => $this->admin->id]);
        $admin = $this->admin;

        $this->actingAs($admin)->get(route('blog.show', $post))->assertOk()
            ->assertSee('noindex, nofollow', false)->assertSee('Preview');
    }

    public function test_admin_pages_and_settings(): void
    {
        $admin = $this->admin;
        $this->actingAs($admin);

        foreach (['admin.ai-blog.index', 'admin.ai-blog.topics', 'admin.ai-blog.settings', 'admin.ai-blog.weak'] as $route) {
            $this->get(route($route))->assertOk();
        }

        $this->post(route('admin.ai-blog.settings.save'), [
            'ai_blog_enabled' => '1', 'ai_blog_provider' => 'anthropic', 'ai_blog_model' => 'claude-sonnet-5-5',
            'ai_blog_posts_per_day' => '2', 'ai_blog_slots' => '10:00, 18:30', 'ai_blog_default_lang' => 'bn',
            'ai_blog_bilingual' => '1', 'ai_blog_publish_mode' => 'draft', 'ai_blog_monthly_budget' => '15', 'ai_blog_words' => '1100',
            'anthropic_key' => 'sk-ant-new-secret-9999',
        ])->assertSessionHasNoErrors();

        $this->assertSame('sk-ant-new-secret-9999', Settings::apiKey('anthropic'));
        $this->assertStringNotContainsString('sk-ant-new-secret', (string) Setting::where('key', 'ai_blog_anthropic_key')->value('value')); // encrypted
        $this->assertSame(['10:00', '18:30'], Settings::slots());
        $this->assertSame('claude-sonnet-5-5', Settings::model());
        $this->get(route('admin.ai-blog.settings'))->assertDontSee('sk-ant-new-secret-9999');

        // a topic needs products (or a category/brand)
        $this->post(route('admin.ai-blog.topics.store'), ['title' => 'No products', 'post_type' => 'guide'])->assertSessionHasErrors('product_ids');
        $this->post(route('admin.ai-blog.topics.store'), ['title' => 'Winter care', 'post_type' => 'howto', 'product_ids' => [$this->product->id], 'note' => 'tip'])
            ->assertSessionHasNoErrors();
        $topic = AiBlogTopic::firstOrFail();
        $this->get(route('admin.ai-blog.topics.edit', $topic))->assertOk()->assertSee('Winter care');
        $this->post(route('admin.ai-blog.topics.run', $topic))->assertSessionHas('success');
        $this->assertTrue($topic->fresh()->run_now);
    }

    public function test_topic_suggestions_keep_only_real_products(): void
    {
        Http::fake(['api.anthropic.com/*' => Http::response($this->claude(['topics' => [
            ['title' => 'শীতে ত্বকের যত্ন', 'keyword' => 'winter skin care', 'post_type' => 'occasion', 'product_ids' => [$this->product->id, 424242], 'why' => 'Seasonal search'],
            ['title' => 'Made-up products only', 'keyword' => 'x', 'post_type' => 'guide', 'product_ids' => [424242], 'why' => 'x'],
        ]], 'claude-haiku-5-5'))]);

        $admin = $this->admin;
        $this->actingAs($admin)->post(route('admin.ai-blog.suggest'), ['count' => 6])->assertRedirect(route('admin.ai-blog.topics'));
        $ideas = session('ai_blog_suggestions');
        $this->assertCount(1, $ideas);
        $this->assertSame([$this->product->id], $ideas[0]['product_ids']);
        Http::assertSent(fn ($r) => $r['model'] === 'claude-haiku-5-5' && !isset($r['fallbacks']));

        $this->actingAs($admin)->post(route('admin.ai-blog.suggestions.add'), ['ideas' => [[
            'pick' => '1', 'title' => $ideas[0]['title'], 'keyword' => $ideas[0]['keyword'], 'post_type' => $ideas[0]['post_type'],
            'product_ids' => (string) $this->product->id, 'note' => 'Our tip',
        ]]])->assertSessionHasNoErrors();
        $this->assertSame(1, AiBlogTopic::where('note', 'Our tip')->count());
    }

    public function test_manual_posts_can_have_a_second_language(): void
    {
        $admin = $this->admin;
        $this->actingAs($admin)->post(route('admin.blog.posts.store'), [
            'title' => 'Sunscreen guide', 'content' => '<p>Use SPF daily.</p>', 'status' => 'published', 'lang' => 'en',
            'alt_title' => 'সানস্ক্রিন গাইড', 'alt_content' => '<p>প্রতিদিন SPF ব্যবহার করুন।</p>',
        ])->assertSessionHasNoErrors();

        $post = BlogPost::where('title', 'Sunscreen guide')->firstOrFail();
        $this->assertSame('bn', $post->altLang());
        $this->get(route('blog.show.lang', [$post, 'bn']))->assertOk()->assertSee('সানস্ক্রিন গাইড');
    }
}
