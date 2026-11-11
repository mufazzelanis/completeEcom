<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\LandingPage;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Support\AddressCheck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** A landing page order needs a name, a reachable mobile number and a full address — like the store's checkout. */
class LandingOrderFieldsTest extends TestCase
{
    use RefreshDatabase;

    private LandingPage $lp;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::set('phone_check', 'off'); // even with the phone check switched off
        Setting::bust();
        $category = Category::forceCreate(['name' => 'Baby', 'slug' => 'baby']);
        $product = Product::forceCreate(['category_id' => $category->id, 'name' => 'Aveeno Lotion', 'slug' => 'aveeno-lotion', 'price' => 1590, 'stock' => 10, 'is_active' => true, 'approval_status' => 'approved']);
        // a page saved before the change, with the old "don't collect the address" switches
        $this->lp = LandingPage::forceCreate([
            'title' => 'Aveeno', 'slug' => 'aveeno', 'product_id' => $product->id, 'status' => 'published',
            'order_button_text' => 'অর্ডার করুন', 'thank_you_heading' => 'ধন্যবাদ', 'collect_address' => false, 'require_address' => false,
            'delivery_mode' => 'none', // delivery areas: DefaultDeliveryAreaTest
        ]);
    }

    private function order(array $over = [])
    {
        return $this->post('/aveeno', $over + ['name' => 'Nusaiba Haque', 'phone' => '01518303867', 'address' => 'House 12, Road 5, Dhanmondi, Dhaka', 'quantity' => 1]);
    }

    public function test_a_complete_order_goes_through_with_its_address(): void
    {
        $this->order()->assertSessionHasNoErrors();

        $this->assertSame('House 12, Road 5, Dhanmondi, Dhaka', Order::first()->shipping_address);
    }

    public function test_an_order_without_an_address_is_refused_even_when_the_page_said_not_to_ask(): void
    {
        $this->order(['address' => ''])->assertSessionHasErrors('address');
        $this->post('/aveeno', ['name' => 'Nusaiba', 'phone' => '01518303867'])->assertSessionHasErrors('address');

        $this->assertSame(0, Order::count());
    }

    public function test_addresses_a_courier_could_not_find_are_refused(): void
    {
        foreach (['Dhaka', 'ঢাকা', 'Mirpur', 'aaaaaaaaaaaa', '01518303867', '..........', '12345 67890', 'ok ok ok ok'] as $bad) {
            $this->order(['address' => $bad])->assertSessionHasErrors('address');
        }
        $this->assertSame(0, Order::count());
    }

    public function test_real_addresses_in_bangla_and_english_are_accepted(): void
    {
        foreach (['বাসা ১২, রোড ৫, ধানমন্ডি, ঢাকা', 'Mirpur 10, Dhaka', 'Gulshan-1, Dhaka', 'গ্রাম: চরপাড়া, থানা: সদর, জেলা: ময়মনসিংহ', 'Agrabad, Chattogram'] as $good) {
            $this->assertTrue(AddressCheck::looksReal($good), $good);
        }
    }

    public function test_name_and_phone_are_required_and_must_be_real(): void
    {
        $this->order(['name' => ''])->assertSessionHasErrors('name');
        $this->order(['name' => '12345'])->assertSessionHasErrors('name');
        $this->order(['phone' => ''])->assertSessionHasErrors('phone');
        $this->order(['phone' => '12345'])->assertSessionHasErrors('phone');   // not a BD mobile, though the check is off
        $this->order(['phone' => '+8801518303867'])->assertSessionHasNoErrors();

        $this->assertSame(1, Order::count());
    }

    public function test_the_messages_are_in_bangla(): void
    {
        $this->order(['address' => 'Dhaka'])->assertSessionHasErrors(['address' => AddressCheck::MESSAGE]);
    }

    public function test_every_design_asks_for_the_address(): void
    {
        foreach (\App\Support\LandingThemes::keys() as $theme) {
            $this->lp->update(['theme' => $theme]);
            $this->get('/aveeno')->assertOk()
                ->assertSee('name="address"', false)
                ->assertSee('required minlength="10"', false);
        }
    }

    public function test_the_admin_no_longer_offers_to_skip_the_address(): void
    {
        Setting::set('two_factor_enabled', '0');
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.landing-pages.edit', $this->lp))->assertOk()
            ->assertDontSee('name="require_address"', false)->assertSee('always required');

        $this->actingAs($admin)->put(route('admin.landing-pages.update', $this->lp), ['title' => 'Aveeno', 'slug' => 'aveeno', 'status' => 'published', 'collect_address' => '0', 'require_address' => '0']);
        $this->assertTrue((bool) $this->lp->fresh()->require_address);
    }
}
