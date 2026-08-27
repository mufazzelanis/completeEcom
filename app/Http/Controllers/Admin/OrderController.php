<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\ActivityLogger;
use App\Services\AuditLogger;
use App\Services\FraudDetectionService;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\OrderStockService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with('user');

        if ($request->filled('status')) {
            $statuses = explode(',', $request->status);
            count($statuses) > 1 ? $query->whereIn('status', $statuses) : $query->where('status', $request->status);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('fraud')) {
            $query->where('is_fraud_flagged', true);
        }

        if ($request->filled('search')) {
            $query->where('order_number', 'like', '%'.$request->search.'%')
                ->orWhereHas('user', fn ($q) => $q->where('name', 'like', '%'.$request->search.'%'));
        }

        $orders = $query->latest()->paginate(15);
        $flaggedCount = Order::where('is_fraud_flagged', true)->count();

        return view('admin.orders.index', compact('orders', 'flaggedCount'));
    }

    public function show(Order $order)
    {
        $order->load('user', 'items.product');

        // Run fraud check on first view
        if (! $order->fraud_checked_at) {
            $fraud = app(FraudDetectionService::class)->analyze($order);
            $order->update([
                'fraud_score' => $fraud['score'],
                'fraud_flags' => $fraud['flags'],
                'is_fraud_flagged' => $fraud['score'] >= 50,
                'fraud_checked_at' => now(),
            ]);
            $order->refresh();
            $this->notifyIfFraudFlagged($order, $fraud);
        }

        ActivityLogger::log('order.view', "Admin viewed order #{$order->order_number}", $order);

        return view('admin.orders.show', compact('order'));
    }

    public function update(Request $request, Order $order)
    {
        $request->validate([
            'payment_status' => 'nullable|in:pending,paid,failed,refunded',
        ]);

        $old = $order->payment_status;
        $order->update($request->only(['payment_status']));

        if ($request->payment_status) {
            $this->syncPaymentRecord($order, $request->payment_status);
        }

        if ($request->payment_status === 'refunded') {
            OrderStockService::restoreIfNeeded($order, 'refunded', auth()->id());
        }

        AuditLogger::log(
            'order.payment_updated',
            "Order {$order->order_number} payment status changed from {$old} to {$request->payment_status}",
            $order,
            ['payment_status' => $old],
            ['payment_status' => $request->payment_status]
        );

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Payment status updated.', 'payment_status' => $order->payment_status]);
        }

        return back()->with('success', 'Order updated.');
    }

    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled,refunded',
        ]);

        $old = $order->status;

        $order->update(['status' => $request->status]);

        if ($request->status === 'refunded' && $order->payment_status !== 'refunded') {
            $order->update(['payment_status' => 'refunded']);
            $this->syncPaymentRecord($order, 'refunded');
        }

        if (in_array($request->status, ['cancelled', 'refunded'], true)) {
            OrderStockService::restoreIfNeeded($order, $request->status, auth()->id());
        }

        AuditLogger::log(
            'order.status_updated',
            "Order {$order->order_number} status changed from {$old} to {$request->status}",
            $order,
            ['status' => $old],
            ['status' => $request->status]
        );

        if ($order->user) {
            NotificationDispatcher::customer('order_status_changed', $order->user, [
                'order_number' => $order->order_number,
                'old_status' => ucfirst($old),
                'new_status' => ucfirst($request->status),
                'url' => route('orders.show', $order),
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Order status updated.', 'status' => $order->status]);
        }

        return back()->with('success', 'Order status updated.');
    }

    private function syncPaymentRecord(Order $order, string $paymentStatus): void
    {
        $payment = $order->payment ?? $order->payments()->latest()->first();
        if (! $payment) {
            return;
        }

        $mapped = match ($paymentStatus) {
            'paid'     => 'completed',
            'failed'   => 'failed',
            'refunded' => 'refunded',
            default    => $payment->status,
        };

        if ($mapped !== $payment->status) {
            $payment->update([
                'status'      => $mapped,
                'verified_by' => $payment->verified_by ?? auth()->id(),
                'verified_at' => $payment->verified_at ?? now(),
                'refunded_at' => $mapped === 'refunded' ? now() : $payment->refunded_at,
            ]);
        }
    }

    public function recheckFraud(Order $order)
    {
        $order->load('user', 'items');
        $fraud = app(FraudDetectionService::class)->analyze($order);
        $order->update([
            'fraud_score' => $fraud['score'],
            'fraud_flags' => $fraud['flags'],
            'is_fraud_flagged' => $fraud['score'] >= 50,
            'fraud_checked_at' => now(),
        ]);

        AuditLogger::log(
            'order.fraud_rechecked',
            "Fraud check re-run on order {$order->order_number} — score: {$fraud['score']} ({$fraud['risk_level']})",
            $order,
            [],
            ['score' => $fraud['score'], 'risk_level' => $fraud['risk_level'], 'flags' => $fraud['flags']]
        );

        $this->notifyIfFraudFlagged($order, $fraud);

        return back()->with('success', 'Fraud check complete. Score: '.$fraud['score'].' ('.ucfirst($fraud['risk_level']).')');
    }

    private function notifyIfFraudFlagged(Order $order, array $fraud): void
    {
        if ($fraud['score'] < 50) {
            return;
        }

        NotificationDispatcher::admin('fraud_flagged', [
            'order_number' => $order->order_number,
            'score' => $fraud['score'],
            'flags' => implode(', ', $fraud['flags'] ?? []),
        ]);
    }

    public function destroy(Order $order)
    {
        AuditLogger::log(
            'order.deleted',
            "Order {$order->order_number} was deleted (total: ৳{$order->total})",
            null,
            ['order_number' => $order->order_number, 'total' => $order->total, 'user_id' => $order->user_id]
        );

        $order->delete();

        return redirect()->route('admin.orders.index')->with('success', 'Order deleted.');
    }

    public function invoice(Order $order)
    {
        $order->load('user', 'items.product');
        $pdf = Pdf::loadView('admin.orders.invoice', compact('order'))
            ->setPaper('a4', 'portrait');

        $this->registerInvoiceFont($pdf);

        return $pdf->download("invoice-{$order->order_number}.pdf");
    }

    /**
     * dompdf's bundled DejaVu Sans font has almost no Bengali glyph coverage, so
     * Bangla order notes/addresses/names render as blank boxes. Register Hind
     * Siliguri (storage/fonts) as a real font family before the PDF renders.
     */
    private function registerInvoiceFont($pdf): void
    {
        $fontMetrics = $pdf->getDomPDF()->getFontMetrics();
        $fontMetrics->registerFont(
            ['family' => 'Hind Siliguri', 'style' => 'normal', 'weight' => 'normal'],
            storage_path('fonts/HindSiliguri-Regular.ttf')
        );
        $fontMetrics->registerFont(
            ['family' => 'Hind Siliguri', 'style' => 'normal', 'weight' => 'bold'],
            storage_path('fonts/HindSiliguri-Bold.ttf')
        );
        $fontMetrics->registerFont(
            ['family' => 'Hind Siliguri', 'style' => 'italic', 'weight' => 'normal'],
            storage_path('fonts/HindSiliguri-Regular.ttf')
        );
        $fontMetrics->registerFont(
            ['family' => 'Hind Siliguri', 'style' => 'italic', 'weight' => 'bold'],
            storage_path('fonts/HindSiliguri-Bold.ttf')
        );
    }

    /**
     * Manual/phone order entry — for a customer who called or messaged in rather than
     * checking out on the website. Same Order/OrderItem shape as a normal web order (same
     * status pipeline, same invoice, same stock behavior on cancel via OrderStockService),
     * just entered by an admin instead of the customer, and flagged source='phone' so it's
     * distinguishable in the orders list from actual web traffic.
     */
    public function create()
    {
        // Simple products only — variants/bundles need their own combination/component
        // picker UI that this quick single-line-per-product form doesn't have room for
        // (the same scope boundary the landing page order flow already draws).
        $products = \App\Models\Product::query()
            ->where('is_active', true)
            ->where('type', 'simple')
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'price', 'sale_price', 'stock']);

        $paymentMethods = \App\Models\PaymentMethod::where('is_active', true)->orderBy('sort_order')->get(['name', 'slug']);

        return view('admin.orders.create', compact('products', 'paymentMethods'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id'      => 'nullable|exists:users,id',
            'shipping_name'    => 'required|string|max:255',
            'shipping_phone'   => 'required|string|max:30',
            'shipping_address' => 'nullable|string|max:500',
            'shipping_city'    => 'nullable|string|max:100',
            'product_id'       => 'required|array|min:1',
            'product_id.*'     => 'required|exists:products,id',
            'quantity'         => 'required|array|min:1',
            'quantity.*'       => 'required|integer|min:1|max:999',
            'price'            => 'required|array|min:1',
            'price.*'          => 'required|numeric|min:0',
            'shipping_charge'  => 'nullable|numeric|min:0',
            'discount'         => 'nullable|numeric|min:0',
            'payment_method'   => 'required|string|max:50',
            'payment_status'   => 'required|in:pending,paid',
            'status'           => 'required|in:pending,processing,shipped,delivered,cancelled',
            'notes'            => 'nullable|string|max:1000',
        ]);

        // Stock check up front — same reasoning as the landing page order flow: fail with
        // a clear message before touching the database, rather than decrementing some
        // lines and not others if one product runs short partway through the loop below.
        $products = \App\Models\Product::whereIn('id', $validated['product_id'])->get()->keyBy('id');
        foreach ($validated['product_id'] as $i => $productId) {
            $product = $products->get($productId);
            $qty = (int) $validated['quantity'][$i];
            if (! $product || $qty > $product->available_stock) {
                return back()->withInput()->withErrors([
                    "quantity.$i" => ($product?->name ?? 'This product') . ' — only ' . max(0, $product?->available_stock ?? 0) . ' in stock.',
                ]);
            }
        }

        $shippingCharge = (float) ($validated['shipping_charge'] ?? 0);
        $discount = (float) ($validated['discount'] ?? 0);

        $order = \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $products, $shippingCharge, $discount, $request) {
            $subtotal = 0;
            $lines = [];
            foreach ($validated['product_id'] as $i => $productId) {
                $qty = (int) $validated['quantity'][$i];
                $unitPrice = (float) $validated['price'][$i];
                $lineSubtotal = round($unitPrice * $qty, 2);
                $subtotal += $lineSubtotal;
                $lines[] = ['product' => $products->get($productId), 'qty' => $qty, 'price' => $unitPrice, 'subtotal' => $lineSubtotal];
            }
            $total = max(0, round($subtotal + $shippingCharge - $discount, 2));

            $order = Order::create([
                'user_id'          => $validated['customer_id'] ?? null,
                'order_number'     => Order::generateOrderNumber(),
                'status'           => $validated['status'],
                'subtotal'         => $subtotal,
                'discount'         => $discount,
                'shipping'         => $shippingCharge,
                'tax'              => 0,
                'total'            => $total,
                'payment_method'   => $validated['payment_method'],
                'payment_status'   => $validated['payment_status'],
                'shipping_name'    => $validated['shipping_name'],
                'shipping_phone'   => $validated['shipping_phone'],
                'shipping_address' => $validated['shipping_address'] ?? null,
                'shipping_city'    => $validated['shipping_city'] ?? null,
                'notes'            => $validated['notes'] ?? null,
                'source'           => 'phone',
                'created_by'       => $request->user()->id,
            ]);

            foreach ($lines as $line) {
                \App\Models\OrderItem::create([
                    'order_id'     => $order->id,
                    'product_id'   => $line['product']->id,
                    'product_name' => $line['product']->name,
                    'price'        => $line['price'],
                    'quantity'     => $line['qty'],
                    'subtotal'     => $line['subtotal'],
                ]);
                $line['product']->decrement('stock', $line['qty']);
            }

            return $order;
        });

        AuditLogger::log('order.manual_create', "Manually created order #{$order->order_number} ({$order->shipping_name}, {$order->shipping_phone})", $order, [
            'total' => $order->total, 'items' => count($validated['product_id']),
        ]);

        return redirect()->route('admin.orders.show', $order)->with('success', 'Order created.');
    }

    public function edit(string $id) {}
}
