<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * A real order through the shop: add to cart, place the order. The Telegram alert went out with
 * "Items (0)" and no photo, because the order's empty items list was read before its items were saved.
 */
class TelegramCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private const TITLE = 'Aveeno Baby Daily Moisture Lotion 227ml';

    public function test_a_placed_order_reaches_telegram_with_its_products_titles_and_photo(): void
    {
        foreach (['telegram_notifications_enabled' => '1', 'telegram_bot_token' => 'TOKEN', 'telegram_chat_id' => '42',
            'two_factor_enabled' => '0', 'phone_check' => 'off'] as $k => $v) {
            Setting::set($k, $v);
        }
        Setting::bust();
        URL::forceRootUrl('https://shop.test');
        URL::forceScheme('https');
        config(['filesystems.disks.public.url' => 'https://shop.test/storage']);
        Storage::forgetDisk('public');
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 5]])]);

        PaymentMethod::where('slug', 'cod')->update(['is_active' => true]); // seeded by a migration
        $category = Category::forceCreate(['name' => 'Baby', 'slug' => 'baby']);
        $product = Product::forceCreate([
            'category_id' => $category->id, 'name' => self::TITLE, 'slug' => 'aveeno-lotion',
            'price' => 1590, 'stock' => 10, 'is_active' => true, 'approval_status' => 'approved', 'image' => 'products/aveeno.jpg',
        ]);

        $this->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 2])->assertSessionHasNoErrors();
        // the cart belongs to the visitor's session: keep the same one, as a browser would
        $this->withCookie(config('session.cookie'), session()->getId());
        $this->post(route('checkout.store'), [
            'shipping_name' => 'Nusaiba Haque', 'shipping_phone' => '01518303867', 'shipping_address' => 'Plot-45, Gulshan-1', 'shipping_city' => 'Dhaka',
            'shipping_zone' => 'outside_dhaka', 'payment_method' => 'cod',
        ]);
        $this->assertSame([], array_intersect_key(session()->all(), array_flip(['errors', 'error'])), 'checkout accepted the order');

        $order = Order::with('items')->latest('id')->first();
        $this->assertNotNull($order, 'the order was placed');
        $this->assertCount(1, $order->items);

        $sent = collect(Http::recorded())->map(fn ($r) => $r[0])->filter(fn (Request $r) => str_contains($r->url(), 'api.telegram.org'))->values();
        $this->assertCount(1, $sent, 'one Telegram post');
        $post = $sent->first();
        $this->assertStringEndsWith('/sendPhoto', $post->url());
        $this->assertStringContainsString('Items (2)', $post['caption']);
        $this->assertStringContainsString('<code>' . self::TITLE . '</code>', $post['caption']);
        $this->assertStringContainsString('https://shop.test/storage/products/aveeno.jpg', $post['photo']);
        $copies = collect($post['reply_markup']['inline_keyboard'])->flatten(1)->pluck('copy_text.text')->filter()->all();
        $this->assertContains(self::TITLE, $copies);
    }
}
