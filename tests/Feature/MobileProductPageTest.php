<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Phone product page: nothing between the shopper and the product / buy buttons. */
class MobileProductPageTest extends TestCase
{
    use RefreshDatabase;

    private function product(): Product
    {
        $cat = Category::forceCreate(['name' => 'Hair care', 'slug' => 'hair-care', 'is_active' => true]);

        return Product::forceCreate(['category_id' => $cat->id, 'name' => 'Biotin 5000mcg', 'slug' => 'biotin-5000', 'price' => 2350,
            'stock' => 5, 'is_active' => true, 'approval_status' => 'approved', 'image' => 'products/x.webp']);
    }

    public function test_offer_bar_only_on_the_home_page(): void
    {
        $product = $this->product();
        Setting::set('announcement_enabled', '1');
        Setting::set('announcement_text', 'Sale! Use code SAVE02');

        $this->get('/')->assertOk()->assertSee('Sale! Use code SAVE02');
        $this->get(route('products.show', $product))->assertOk()->assertDontSee('Sale! Use code SAVE02');
        $this->get('/shop')->assertOk()->assertDontSee('Sale! Use code SAVE02');
    }

    public function test_product_page_buy_bar_has_cart_and_no_pinned_search_bar(): void
    {
        $product = $this->product();

        $this->get(route('products.show', $product))->assertOk()
            ->assertSee('data-sb-cart', false)                                  // 🛒 in the buy bar
            ->assertSee('[data-fab], [data-to-top] { display: none !important; }', false)
            ->assertDontSee('$store.mobileSearchBar.visible', false);         // no pinned search bar here

        $this->get('/shop')->assertOk()->assertSee('$store.mobileSearchBar.visible', false); // still on other pages
    }
}
