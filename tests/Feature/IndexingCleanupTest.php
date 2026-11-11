<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Search Console "Page indexing" reasons this site can fix itself. */
class IndexingCleanupTest extends TestCase
{
    use RefreshDatabase;

    private function category(string $name, ?int $parent = null): Category
    {
        return Category::forceCreate(['name' => $name, 'slug' => \Illuminate\Support\Str::slug($name), 'parent_id' => $parent, 'is_active' => true]);
    }

    private function product(Category $category, string $slug, array $over = []): Product
    {
        return Product::forceCreate($over + ['category_id' => $category->id, 'name' => ucfirst($slug), 'slug' => $slug, 'price' => 500, 'stock' => 5, 'is_active' => true, 'approval_status' => 'approved']);
    }

    public function test_page_one_has_a_single_address(): void
    {
        $cat = $this->category('Face Care');
        $this->get('/shop/category/face-care?page=1')->assertRedirect('/shop/category/face-care')->assertStatus(301);
        $this->get('/shop/category/face-care?page=2')->assertOk();
    }

    public function test_pagination_links_to_page_one_are_plain(): void
    {
        $cat = $this->category('Face Care');
        foreach (range(1, 40) as $i) {
            $this->product($cat, "item-$i");
        }

        $html = $this->get('/shop/category/face-care?page=2')->assertOk()->getContent();
        $this->assertStringNotContainsString('page=1"', $html);
        $this->assertStringContainsString('page=3', $html);
    }

    public function test_old_theme_listing_parameters_go_to_the_clean_url(): void
    {
        $this->get('/shop?per_page=9&per_row=4&shop_view=grid')->assertRedirect('/shop')->assertStatus(301);
        $this->get('/cart?_wpnonce=1fe3')->assertRedirect('/cart');
    }

    public function test_robots_keeps_crawlers_off_non_pages(): void
    {
        $this->get('/robots.txt')->assertOk()
            ->assertSee('Disallow: /cgi-bin/')->assertSee('Disallow: /live/')->assertSee('Disallow: /cdn-cgi/');
    }

    public function test_an_empty_category_is_noindex_and_left_out_of_the_sitemap(): void
    {
        $empty = $this->category('Brand Mvn');
        $parent = $this->category('Baby');
        $child = $this->category('Lotion', $parent->id);
        $this->product($child, 'aveeno-lotion');

        $this->get('/shop/category/brand-mvn')->assertOk()->assertSee('<meta name="robots" content="noindex, follow">', false);
        $this->get('/shop/category/lotion')->assertOk()->assertDontSee('content="noindex', false);

        $sitemap = $this->get('/sitemap.xml')->getContent();
        $this->assertStringNotContainsString('/shop/category/brand-mvn<', $sitemap);
        $this->assertStringContainsString('/shop/category/lotion<', $sitemap);
        $this->assertStringContainsString('/shop/category/baby<', $sitemap); // stocked through its sub-category
    }

    public function test_an_empty_brand_page_is_noindex(): void
    {
        Brand::forceCreate(['name' => 'Nestle', 'slug' => 'nestle', 'is_active' => true]);

        $this->get('/brand/nestle')->assertOk()->assertSee('<meta name="robots" content="noindex, follow">', false);
    }
}
