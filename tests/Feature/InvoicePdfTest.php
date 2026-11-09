<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CustomInvoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Downloaded invoices: every kind of order must produce a real PDF, with its figures adding up. */
class InvoicePdfTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::set('two_factor_enabled', '0');
        Setting::bust();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    private function order(array $attrs = [], int $items = 1): Order
    {
        $category = Category::forceCreate(['name' => 'Demo', 'slug' => 'demo-' . uniqid()]);
        $product = Product::forceCreate([
            'category_id' => $category->id, 'name' => 'Demo', 'slug' => 'demo-' . uniqid(), 'price' => 100,
            'stock' => 5, 'is_active' => true, 'approval_status' => 'approved',
        ]);
        $order = Order::forceCreate($attrs + [
            'order_number' => 'ORD-' . uniqid(), 'status' => 'shipped', 'subtotal' => 100, 'discount' => 0, 'shipping' => 80,
            'tax' => 0, 'total' => 180, 'payment_method' => 'cod', 'payment_status' => 'pending',
            'shipping_name' => 'আজমেরী নোদী', 'shipping_phone' => '01344504924',
            'shipping_address' => '৬৭/বি সুকলাল দাস লেন, পুরান ঢাকা', 'shipping_country' => 'Bangladesh',
        ]);
        for ($i = 0; $i < $items; $i++) {
            OrderItem::forceCreate(['order_id' => $order->id, 'product_id' => $product->id, 'product_name' => 'বেবি ক্রিম', 'price' => 100, 'quantity' => 1, 'subtotal' => 100]);
        }

        return $order;
    }

    private function assertPdf($response): void
    {
        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    private function html(Order $order): string
    {
        $order->load('user', 'items.product', 'payment');
        $render = new \ReflectionMethod(\App\Support\InvoicePdf::class, 'theme');
        $store = new \ReflectionMethod(\App\Support\InvoicePdf::class, 'store');
        $money = new \ReflectionMethod(\App\Support\InvoicePdf::class, 'moneyFormatter');

        return view('admin.orders.invoice', [
            'order' => $order, 'photos' => [], 'trackingQr' => null, 'air' => 0, 'push' => 0, 'compact' => false,
            'theme' => $render->invoke(null, null), 'store' => $store->invoke(null), 'money' => $money->invoke(null),
        ])->render();
    }

    public function test_an_order_downloads_as_a_pdf_with_a_named_file(): void
    {
        $order = $this->order();

        $response = $this->actingAs($this->admin)->get(route('admin.orders.invoice', $order));

        $this->assertPdf($response);
        $this->assertStringContainsString("invoice-{$order->order_number}.pdf", $response->headers->get('Content-Disposition'));
    }

    public function test_a_cancelled_order_and_a_very_long_order_still_produce_a_pdf(): void
    {
        $this->assertPdf($this->actingAs($this->admin)->get(route('admin.orders.invoice', $this->order(['status' => 'cancelled']))));
        $this->assertPdf($this->actingAs($this->admin)->get(route('admin.orders.invoice', $this->order([], 40))));
    }

    public function test_only_admins_can_download_an_invoice(): void
    {
        $order = $this->order();

        $this->get(route('admin.orders.invoice', $order))->assertRedirect();
        $this->actingAs(User::factory()->create(['role' => 'customer']))->get(route('admin.orders.invoice', $order))->assertForbidden();
    }

    public function test_every_charge_in_the_total_is_listed(): void
    {
        $html = $this->html($this->order(['payment_charge' => 25, 'total' => 205]));

        $this->assertStringContainsString('Payment Charge', $html);
        $this->assertStringContainsString('205.00', $html);
    }

    public function test_cash_on_delivery_shows_what_to_collect_and_a_paid_order_shows_nothing_due(): void
    {
        $cod = $this->html($this->order());
        $this->assertStringContainsString('Collect on Delivery', $cod);
        $this->assertStringNotContainsString('Paid in Full', $cod);

        $paid = $this->html($this->order(['payment_method' => 'bkash', 'payment_status' => 'paid']));
        $this->assertStringContainsString('Paid in Full', $paid);
        $this->assertStringNotContainsString('Collect on Delivery', $paid);
    }

    public function test_a_preorder_shows_the_advance_and_only_the_balance_as_due(): void
    {
        $html = $this->html($this->order(['is_preorder' => true, 'advance_amount' => 50, 'payment_status' => 'paid']));

        $this->assertStringContainsString('Advance Paid', $html);
        $this->assertStringContainsString('130.00', $html);
    }

    public function test_a_custom_invoice_downloads_as_a_pdf(): void
    {
        $invoice = CustomInvoice::forceCreate([
            'type' => 'quotation', 'number' => 'QT-2026-0001', 'status' => 'unpaid', 'issue_date' => now(), 'customer_name' => 'রহিম ট্রেডার্স',
            'items' => [['name' => 'Gel', 'qty' => 2, 'price' => 50, 'discount' => 0, 'total' => 100]],
            'subtotal' => 100, 'discount' => 0, 'tax' => 0, 'tax_rate' => 0, 'shipping' => 0, 'total' => 100, 'paid_amount' => 0,
        ]);

        $this->assertPdf($this->actingAs($this->admin)->get(route('admin.custom-invoices.pdf', $invoice)));
    }
}
