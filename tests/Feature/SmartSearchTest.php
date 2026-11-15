<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\SearchQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmartSearchTest extends TestCase
{
    use RefreshDatabase;

    private function product(string $name, array $over = []): Product
    {
        $cat = Category::firstOrCreate(['slug' => 'skin-care'], ['name' => 'Skin care', 'is_active' => true]);

        return Product::forceCreate($over + ['category_id' => $cat->id, 'name' => $name, 'slug' => \Illuminate\Support\Str::slug($name),
            'price' => 2000, 'stock' => 5, 'is_active' => true, 'approval_status' => 'approved', 'image' => 'products/x.webp']);
    }

    public function test_trending_lists_top_searches_and_most_viewed_products(): void
    {
        $this->product('CeraVe Moisturizing Cream 454g', ['views' => 900, 'sale_price' => 1500]);
        $this->product('Aveeno Baby Lotion', ['views' => 500]);
        foreach (['cerave', 'cerave', 'Cerave ', 'biotin', 'biotin', 'once only'] as $i => $q) {
            SearchQuery::create(['query' => $q, 'session_id' => "s$i", 'searched_at' => now()->subDays(2)]);
        }

        $json = $this->getJson('/search/trending')->assertOk()->json();

        $this->assertSame(['cerave', 'biotin'], $json['searches']); // counted case-insensitively, one-offs left out
        $this->assertSame('CeraVe Moisturizing Cream 454g', $json['products'][0]['name']);
        $this->assertSame('৳1,500', $json['products'][0]['price']);
        $this->assertSame('৳2,000', $json['products'][0]['was']);
        $this->assertSame(25, $json['products'][0]['off']);
        $this->assertContains('cerave', $json['placeholders']);
    }

    public function test_suggestions_carry_the_regular_price_for_sale_items(): void
    {
        $this->product('CeraVe Hydrating Cleanser', ['sale_price' => 1800]);

        $row = $this->getJson('/search/suggest?q=cerave')->assertOk()->json('products.0');
        $this->assertSame('৳1,800', $row['price']);
        $this->assertSame('৳2,000', $row['was']);
    }

    public function test_storefront_renders_the_smart_search_boxes(): void
    {
        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('smartSearch(', $html);
        $this->assertStringContainsString('search\/trending', $html); // URLs inside @js() are JSON-escaped
        $this->assertGreaterThanOrEqual(2, substr_count($html, 'x-data="smartSearch(')); // header + mobile bar (+ drawer)
    }
}
