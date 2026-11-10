<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Observers\TelegramOrderObserver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/** Telegram order alerts: product photos, tap-to-copy titles, status changes, and never a lost alert. */
class TelegramOrderAlertTest extends TestCase
{
    use RefreshDatabase;

    private const TITLE = 'Aveeno Baby Calming Comfort Lotion -227g';

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['telegram_notifications_enabled' => '1', 'telegram_bot_token' => 'TOKEN', 'telegram_chat_id' => '42', 'two_factor_enabled' => '0'] as $k => $v) {
            Setting::set($k, $v);
        }
        Setting::bust();
        // Telegram only fetches photos from public https addresses
        URL::forceRootUrl('https://shop.test');
        URL::forceScheme('https');
        config(['filesystems.disks.public.url' => 'https://shop.test/storage']);
        \Illuminate\Support\Facades\Storage::forgetDisk('public');
        Http::fake(fn (Request $r) => $this->photosFail && str_ends_with($r->url(), '/sendPhoto')
            ? Http::response(['ok' => false, 'description' => 'wrong file identifier'], 400)
            : Http::response(['ok' => true, 'result' => ['message_id' => 7]]));
    }

    private bool $photosFail = false;

    protected function order(int $items = 1, array $attrs = []): Order
    {
        $category = Category::forceCreate(['name' => 'Baby', 'slug' => 'baby-' . uniqid()]);
        $order = Order::withoutEvents(fn () => Order::forceCreate($attrs + [
            'order_number' => 'ORD-TEST1', 'status' => 'pending', 'subtotal' => 100, 'discount' => 0, 'shipping' => 80, 'tax' => 0,
            'total' => 180, 'payment_method' => 'cod', 'payment_status' => 'pending', 'shipping_name' => 'Ajmeri Nodi',
            'shipping_phone' => '01344504924', 'shipping_address' => 'Puran Dhaka', 'shipping_country' => 'Bangladesh',
        ]));
        for ($i = 0; $i < $items; $i++) {
            $product = Product::forceCreate([
                'category_id' => $category->id, 'name' => $i ? "Item {$i}" : self::TITLE, 'slug' => 'p-' . uniqid(), 'price' => 100,
                'stock' => 5, 'is_active' => true, 'approval_status' => 'approved', 'image' => "products/p{$i}.jpg",
            ]);
            OrderItem::forceCreate(['order_id' => $order->id, 'product_id' => $product->id, 'product_name' => $product->name, 'price' => 100, 'quantity' => 1, 'subtotal' => 100]);
        }

        return $order->fresh();
    }

    private function calls(string $method): array
    {
        return collect(Http::recorded())->map(fn ($r) => $r[0])->filter(fn (Request $r) => str_ends_with($r->url(), '/' . $method))->values()->all();
    }

    private function copyTexts(Request $request): array
    {
        return collect($request['reply_markup']['inline_keyboard'] ?? [])->flatten(1)->pluck('copy_text.text')->filter()->values()->all();
    }

    public function test_a_one_item_order_arrives_as_one_photo_post_with_a_copyable_title(): void
    {
        (new TelegramOrderObserver())->sendNewOrder($this->order(), now: true);

        $photo = $this->calls('sendPhoto');
        $this->assertCount(1, $photo);
        $this->assertCount(0, $this->calls('sendMessage'));
        $this->assertStringStartsWith('https://shop.test/storage/', $photo[0]['photo']);
        $this->assertStringContainsString('<code>' . e(self::TITLE) . '</code>', $photo[0]['caption']);
        $this->assertContains(self::TITLE, $this->copyTexts($photo[0]));
    }

    public function test_several_items_come_as_an_album_then_the_details_with_copy_buttons(): void
    {
        (new TelegramOrderObserver())->sendNewOrder($this->order(3), now: true);

        $album = $this->calls('sendMediaGroup');
        $this->assertCount(1, $album);
        $this->assertCount(3, $album[0]['media']);
        $this->assertStringContainsString(e(self::TITLE), $album[0]['media'][0]['caption']);

        $message = $this->calls('sendMessage')[0];
        $this->assertSame(7, $message['reply_parameters']['message_id']);
        $copies = $this->copyTexts($message);
        $this->assertContains(self::TITLE, $copies);
        $this->assertContains('Item 2', $copies);
        $this->assertTrue(collect($copies)->contains(fn ($t) => str_contains($t, self::TITLE . ' × 1') && str_contains($t, 'Item 1 × 1')), 'all products button');
    }

    public function test_a_photo_telegram_cannot_fetch_never_costs_the_alert(): void
    {
        $this->photosFail = true;

        (new TelegramOrderObserver())->sendNewOrder($this->order(), now: true);

        $this->assertCount(1, $this->calls('sendMessage'));
        $this->assertDatabaseHas('notification_logs', ['channel' => 'telegram', 'status' => 'sent']);
    }

    public function test_without_a_public_https_address_it_sends_text_only(): void
    {
        URL::forceRootUrl('http://localhost');
        URL::forceScheme('http');
        config(['filesystems.disks.public.url' => 'http://localhost/storage']);
        \Illuminate\Support\Facades\Storage::forgetDisk('public');

        (new TelegramOrderObserver())->sendNewOrder($this->order(), now: true);

        $this->assertCount(0, $this->calls('sendPhoto'));
        $this->assertStringContainsString('<code>' . e(self::TITLE) . '</code>', $this->calls('sendMessage')[0]['text']);
    }

    public function test_photos_can_be_switched_off(): void
    {
        Setting::set('telegram_product_photos', '0');
        Setting::bust();

        (new TelegramOrderObserver())->sendNewOrder($this->order(2), now: true);

        $this->assertCount(0, $this->calls('sendMediaGroup'));
        $this->assertCount(1, $this->calls('sendMessage'));
    }

    public function test_a_status_change_is_announced_with_the_product(): void
    {
        $order = $this->order();
        $this->actingAs(User::factory()->create(['role' => 'admin', 'name' => 'Mufazzel']));

        $order->update(['status' => 'shipped']);
        $this->app->terminate(); // the alert goes out after the response

        $post = $this->calls('sendPhoto')[0];
        $this->assertStringContainsString('Order Shipped', $post['caption']);
        $this->assertStringContainsString('Pending → <b>Shipped</b>', $post['caption']);
        $this->assertStringContainsString('by Mufazzel', $post['caption']);
        $this->assertStringContainsString('<code>' . e(self::TITLE) . '</code>', $post['caption']);
        $this->assertContains(self::TITLE, $this->copyTexts($post));
    }

    public function test_status_alerts_can_be_switched_off_and_an_unrelated_edit_sends_nothing(): void
    {
        $order = $this->order();
        $order->update(['shipping_name' => 'Someone else']);
        Setting::set('telegram_notify_status', '0');
        Setting::bust();
        $order->update(['status' => 'delivered']);
        $this->app->terminate();

        $this->assertCount(0, Http::recorded());
    }

    public function test_a_long_order_keeps_every_title_in_the_details_message(): void
    {
        (new TelegramOrderObserver())->sendNewOrder($this->order(12), now: true);

        $this->assertCount(10, $this->calls('sendMediaGroup')[0]['media']); // Telegram's album limit
        $text = $this->calls('sendMessage')[0]['text'];
        $this->assertStringContainsString('<code>Item 11</code>', $text);
    }

    public function test_the_settings_button_sends_the_latest_order_as_a_sample(): void
    {
        $this->order();

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('admin.settings.test-telegram-order'))->assertSessionHas('success');

        $this->assertStringContainsString('Sample', $this->calls('sendPhoto')[0]['caption']);
    }
}
