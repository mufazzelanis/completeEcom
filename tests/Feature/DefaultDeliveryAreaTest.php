<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\LandingPage;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\ShippingCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Settings → Shipping → Default Delivery Area, and where checkout, cart and landing pages take their delivery charge from. */
class DefaultDeliveryAreaTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->settings(['shipping_method' => 'zone', 'dhaka_charge' => '80', 'outside_dhaka_charge' => '150', 'free_shipping_enabled' => '0', 'phone_check' => 'off', 'two_factor_enabled' => '0']);
        $category = Category::forceCreate(['name' => 'Baby', 'slug' => 'baby']);
        $this->product = Product::forceCreate(['category_id' => $category->id, 'name' => 'Aveeno Lotion', 'slug' => 'aveeno-lotion', 'price' => 1000, 'stock' => 20, 'is_active' => true, 'approval_status' => 'approved']);
    }

    private function settings(array $values): void
    {
        foreach ($values as $k => $v) {
            Setting::set($k, $v);
        }
        Setting::bust();
    }

    private function page(array $over = []): LandingPage
    {
        return LandingPage::forceCreate($over + [
            'title' => 'Aveeno', 'slug' => 'aveeno', 'product_id' => $this->product->id, 'status' => 'published',
            'order_button_text' => 'অর্ডার করুন', 'thank_you_heading' => 'ধন্যবাদ', 'delivery_mode' => 'site',
        ]);
    }

    private function order(array $over = [])
    {
        return $this->post('/aveeno', $over + ['name' => 'Nusaiba Haque', 'phone' => '01518303867', 'address' => 'House 12, Road 5, Dhanmondi, Dhaka', 'quantity' => 1]);
    }

    public function test_the_setting_reads_back_safely(): void
    {
        $this->assertSame('outside_dhaka', ShippingCalculator::defaultZone()); // never set: as before
        $this->settings(['shipping_default_zone' => 'dhaka']);
        $this->assertSame('dhaka', ShippingCalculator::defaultZone());
        $this->settings(['shipping_default_zone' => 'ask']);
        $this->assertNull(ShippingCalculator::defaultZone());
        $this->assertSame('outside_dhaka', ShippingCalculator::estimateZone()); // an estimate never under-quotes
        $this->settings(['shipping_default_zone' => 'mars']);
        $this->assertSame('outside_dhaka', ShippingCalculator::defaultZone());
    }

    public function test_checkout_and_cart_start_on_the_default_area(): void
    {
        $this->settings(['shipping_default_zone' => 'dhaka']);
        $this->post(route('cart.add'), ['product_id' => $this->product->id, 'quantity' => 1]);
        $this->withCookie(config('session.cookie'), session()->getId());

        $this->get(route('checkout.index'))->assertOk()->assertSee("zone: 'dhaka'", false);
        $this->get(route('cart.index'))->assertOk()->assertSee('(Inside Dhaka · Outside Dhaka ৳150)')->assertSee('৳80');
    }

    public function test_checkout_preselects_nothing_when_the_customer_should_choose(): void
    {
        $this->settings(['shipping_default_zone' => 'ask']);
        $this->post(route('cart.add'), ['product_id' => $this->product->id, 'quantity' => 1]);
        $this->withCookie(config('session.cookie'), session()->getId());

        $this->get(route('checkout.index'))->assertOk()->assertSee("zone: ''", false)
            ->assertSee('x-model="zone" required', false)->assertSee('Choose your delivery area');
    }

    public function test_the_admin_sets_the_default_area(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.settings.show', 'shipping'))->assertOk()->assertSee('Default Delivery Area')->assertSee('Customer chooses');
        $this->actingAs($admin)->patch(route('admin.settings.update', 'shipping'), ['shipping_method' => 'zone', 'shipping_default_zone' => 'dhaka', 'dhaka_charge' => '80', 'outside_dhaka_charge' => '150']);
        Setting::bust();
        $this->assertSame('dhaka', ShippingCalculator::defaultZone());
    }

    public function test_a_landing_page_on_site_settings_offers_both_areas_and_starts_on_the_default(): void
    {
        $this->settings(['shipping_default_zone' => 'dhaka']);
        $lp = $this->page();

        $this->assertSame(0, $lp->defaultDeliveryIndex());
        foreach (\App\Support\LandingThemes::keys() as $theme) {
            $lp->update(['theme' => $theme]);
            $this->get('/aveeno')->assertOk()->assertSee('ঢাকার ভিতরে')->assertSee('ঢাকার বাইরে')->assertSee('zoneIndex: 0', false);
        }

        $this->order(['delivery_zone' => 1])->assertSessionHasNoErrors();
        $order = Order::first();
        $this->assertSame(150.0, (float) $order->shipping);
        $this->assertSame(1150.0, (float) $order->total);
        $this->assertSame('outside_dhaka', $order->shipping_zone);
    }

    public function test_a_landing_page_can_leave_the_area_for_the_customer_and_requires_it(): void
    {
        $this->settings(['shipping_default_zone' => 'ask']);
        $lp = $this->page();

        $this->assertNull($lp->defaultDeliveryIndex());
        $this->get('/aveeno')->assertSee('zoneIndex: null', false)->assertSee('এলাকা বেছে নিন');
        $this->order()->assertSessionHasErrors(['delivery_zone' => 'ডেলিভারি এলাকা বেছে নিন।']);
        $this->assertSame(0, Order::count());
    }

    public function test_the_free_delivery_threshold_applies_on_landing_pages_using_site_settings(): void
    {
        $this->settings(['free_shipping_enabled' => '1', 'free_shipping_min' => '2500']);
        $this->page();

        $this->order(['delivery_zone' => 1, 'quantity' => 2])->assertSessionHasNoErrors(); // 2000: charged
        $this->order(['delivery_zone' => 1, 'quantity' => 3, 'phone' => '01712398745'])->assertSessionHasNoErrors(); // 3000: free
        $this->assertSame([150.0, 0.0], Order::orderBy('id')->pluck('shipping')->map(fn ($v) => (float) $v)->all());
    }

    public function test_a_page_with_its_own_zones_preselects_the_one_matching_the_default(): void
    {
        $this->settings(['shipping_default_zone' => 'outside_dhaka']);
        $lp = $this->page(['delivery_mode' => 'custom', 'delivery_zones' => [['label' => 'ঢাকার ভিতরে', 'charge' => 50], ['label' => 'ঢাকার বাইরে', 'charge' => 99]]]);
        $this->assertSame(1, $lp->defaultDeliveryIndex());

        $this->settings(['shipping_default_zone' => 'dhaka']);
        $this->assertSame(0, $lp->fresh()->defaultDeliveryIndex());

        $this->order(['delivery_zone' => 1])->assertSessionHasNoErrors();
        $this->assertSame(99.0, (float) Order::first()->shipping); // the page's own price, not the site's
    }

    public function test_a_page_with_no_delivery_charge_has_no_picker(): void
    {
        $this->page(['delivery_mode' => 'none']);

        $this->get('/aveeno')->assertOk()->assertDontSee('ঢাকার বাইরে');
        $this->order()->assertSessionHasNoErrors();
        $this->assertSame(0.0, (float) Order::first()->shipping);
    }

    public function test_the_landing_admin_saves_the_delivery_choice(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $lp = $this->page(['delivery_mode' => 'none']);

        $this->actingAs($admin)->get(route('admin.landing-pages.edit', $lp))->assertOk()->assertSee('Shipping settings')->assertSee('No delivery charge');
        $this->actingAs($admin)->put(route('admin.landing-pages.update', $lp), ['title' => 'Aveeno', 'slug' => 'aveeno', 'status' => 'published', 'delivery_mode' => 'site'])->assertSessionHasNoErrors();
        $this->assertSame('site', $lp->fresh()->delivery_mode);
    }
}
