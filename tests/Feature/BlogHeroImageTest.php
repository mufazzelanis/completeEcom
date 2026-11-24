<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Live: a blog post's 1.6 MB PNG featured image made LCP 11 s and shifted the article (CLS 0.22). */
class BlogHeroImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_featured_image_is_a_sized_responsive_webp_loaded_first(): void
    {
        Storage::fake('public');
        $img = imagecreatetruecolor(1600, 900);
        ob_start(); imagepng($img); Storage::disk('public')->put('blog/posts/hero.png', ob_get_clean());

        $post = BlogPost::create(['title' => 'Retargeting guide', 'slug' => 'retargeting-guide', 'content' => '<p>Body</p>', 'image' => 'blog/posts/hero.png',
            'status' => 'published', 'published_at' => now()->subDay(), 'user_id' => User::factory()->create(['role' => 'admin'])->id]);

        $this->get(route('blog.show', $post))->assertOk()
            ->assertSee('thumbs/400/blog/posts/hero.png.webp 400w', false)
            ->assertSee('thumbs/1200/blog/posts/hero.png.webp 1200w', false)
            ->assertSee('width="1600" height="900" fetchpriority="high"', false);
    }
}
