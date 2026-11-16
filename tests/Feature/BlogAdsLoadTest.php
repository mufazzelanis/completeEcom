<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Live: blog post ads stayed empty after a Turbo click — ad scripts need a real page load. */
class BlogAdsLoadTest extends TestCase
{
    use RefreshDatabase;

    private function makePost(): BlogPost
    {
        $admin = User::factory()->create(['role' => 'admin']);

        return BlogPost::create(['title' => 'Sunscreen guide', 'slug' => 'sunscreen-guide', 'content' => '<p>Use SPF daily.</p>',
            'status' => 'published', 'published_at' => now()->subDay(), 'user_id' => $admin->id]);
    }

    public function test_blog_pages_with_ads_load_in_full(): void
    {
        $post = $this->makePost();
        Setting::set('adsterra_enabled', '1');
        Setting::set('adsterra_code_article', '<script>atOptions={key:"k"}</script>');

        $this->get(route('blog.show', $post))->assertOk()
            ->assertSee('<meta name="turbo-visit-control" content="reload">', false)
            ->assertSee('turbo:before-visit', false)
            ->assertSee('atOptions', false);
        $this->get(route('blog.index'))->assertOk()->assertSee('turbo-visit-control', false);

        // shop pages keep fast Turbo navigation; they only carry the small click hook
        $this->get('/')->assertOk()->assertDontSee('turbo-visit-control', false)->assertSee('turbo:before-visit', false);
    }

    /** Live: Adsterra sometimes sends nothing for a unit — an empty slot then runs the backup code. */
    public function test_post_slots_carry_a_backup_ad(): void
    {
        $post = $this->makePost();
        Setting::set('adsterra_enabled', '1');
        Setting::set('adsterra_code_infeed', '<script>atOptions={key:"in-feed-300"}</script>');
        Setting::set('adsterra_code_article', '<script>atOptions={key:"article-728"}</script>');

        $this->get(route('blog.show', $post))->assertOk()
            ->assertSee('data-adsterra>', false)
            ->assertSee('<template data-adsterra-backup><script>atOptions={key:"in-feed-300"}</script></template>', false);

        // an admin-chosen backup wins, and a slot never backs itself up with its own code
        Setting::set('adsterra_code_fallback', '<script>atOptions={key:"backup"}</script>');
        $this->get(route('blog.show', $post))->assertSee('{key:"backup"}', false)->assertDontSee('in-feed-300', false);

        Setting::set('adsterra_code_fallback', '<script>atOptions={key:"article-728"}</script>');
        $this->get(route('blog.show', $post))->assertDontSee('<template data-adsterra-backup>', false);
    }

    public function test_nothing_changes_without_ads(): void
    {
        $post = $this->makePost();
        Setting::bust(); // settings are cached for the process; start from none

        $this->get(route('blog.show', $post))->assertOk()
            ->assertDontSee('turbo-visit-control', false)
            ->assertDontSee('turbo:before-visit', false);
    }
}
