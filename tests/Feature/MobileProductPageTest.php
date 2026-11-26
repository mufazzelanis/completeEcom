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

    /** The product page's Add to Cart now stays on the page — the JSON answer carries what the AddToCart tracking needs. */
    public function test_in_place_add_to_cart_returns_count_and_tracking(): void
    {
        $product = $this->product();

        $this->postJson(route('cart.add'), ['product_id' => $product->id, 'quantity' => 2])->assertOk()
            ->assertJsonPath('status', 'added')
            ->assertJsonPath('cart_count', 2)
            ->assertJsonPath('track.id', $product->id)
            ->assertJsonPath('track.quantity', 2)
            ->assertJsonStructure(['fb_event_id', 'track' => ['name', 'price', 'fb_event_id', 'ga']]);
    }

    /** Cart + checkout are "focus mode": no pinned search, menu or chat over them; a sticky Checkout bar instead. */
    public function test_cart_is_in_focus_mode_with_a_checkout_bar(): void
    {
        $product = $this->product();
        $this->actingAs(\App\Models\User::factory()->create()); // a user's cart survives between test requests
        $this->postJson(route('cart.add'), ['product_id' => $product->id, 'quantity' => 1])->assertOk();

        $this->get(route('cart.index'))->assertOk()
            ->assertSee('class="shop-focus ', false)
            ->assertSee('id="ct-bar"', false)
            ->assertSee('Proceed to Checkout')
            ->assertDontSee('$store.mobileSearchBar.visible', false);
        $this->get('/')->assertOk()->assertDontSee('class="shop-focus ', false);
    }

    public function test_the_bottom_dock_and_its_cart_orb(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('class="bn-dock"', false)
            ->assertSee('data-cart-orb', false)
            ->assertSee('id="bottom-nav-cart-count"', false)
            ->assertSee('--bn-active: 0', false); // Home highlighted
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
