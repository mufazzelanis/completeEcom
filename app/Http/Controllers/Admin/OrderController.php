<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\ActivityLogger;
use App\Services\AuditLogger;
use App\Services\CourierFraudCheckService;
use App\Services\FraudDetectionService;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\OrderStockService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public const STATUSES = ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];

    private const FILTER_MEMORY = 'admin.orders.filters';

    /** Everything but the status tab — so the tabs can show how many orders each status has. */
    private function filteredOrders(Request $request)
    {
        $query = Order::query();

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }
        if ($request->filled('fraud')) {
            $query->where('is_fraud_flagged', true);
        }
        if ($request->boolean('preorder')) {
            $query->where('is_preorder', true);
        }
        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }

        // date range — quick ranges or from/to
        $tz = setting('timezone', 'Asia/Dhaka');
        $today = now($tz)->startOfDay();
        [$from, $to] = match ($request->get('range')) {
            'today' => [$today, null],
            'yesterday' => [$today->copy()->subDay(), $today],
            '7d' => [$today->copy()->subDays(6), null],
            '30d' => [$today->copy()->subDays(29), null],
            'month' => [$today->copy()->startOfMonth(), null],
            default => [
                $request->filled('from') ? \Carbon\Carbon::parse($request->from, $tz)->startOfDay() : null,
                $request->filled('to') ? \Carbon\Carbon::parse($request->to, $tz)->addDay()->startOfDay() : null,
            ],
        };
        if ($from) {
            $query->where('created_at', '>=', $from->copy()->utc());
        }
        if ($to) {
            $query->where('created_at', '<', $to->copy()->utc());
        }

        // Search: order number, customer name, phone (any format: 01711…, +8801711…, 1711…), email
        if ($request->filled('search')) {
            $s = trim($request->search);
            $digits = preg_replace('/\D/', '', $s);
            $tail = strlen($digits) >= 6 ? substr($digits, -10) : null;
            $query->where(function ($q) use ($s, $tail) {
                $q->where('order_number', 'like', "%{$s}%")
                    ->orWhere('shipping_name', 'like', "%{$s}%")
                    ->orWhere('guest_email', 'like', "%{$s}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%"));
                if ($tail) {
                    $q->orWhere('shipping_phone', 'like', "%{$tail}%");
                }
                if (ctype_digit(ltrim($s, '#'))) {
                    $q->orWhere('id', (int) ltrim($s, '#'));
                }
            });
        }

        return $query;
    }

    private function applyStatusFilter($query, ?string $status)
    {
        if ($status) {
            $statuses = explode(',', $status);
            count($statuses) > 1 ? $query->whereIn('status', $statuses) : $query->where('status', $status);
        }

        return $query;
    }

    public function index(Request $request)
    {
        // Sticky filters: the list comes back as it was left after opening an order, changing a
        // status, or coming back from the sidebar — until "Clear" (?reset=1).
        if ($request->boolean('reset')) {
            session()->forget(self::FILTER_MEMORY);

            return redirect()->route('admin.orders.index');
        }
        // any query string at all (even "?status=") is an explicit choice; only a bare URL restores
        $params = array_filter($request->query(), fn ($v) => $v !== null && $v !== '');
        if ($request->query()) {
            $params ? session([self::FILTER_MEMORY => $params]) : session()->forget(self::FILTER_MEMORY);
        } elseif ($saved = session(self::FILTER_MEMORY)) {
            return redirect()->route('admin.orders.index', $saved);
        }

        $base = $this->filteredOrders($request);
        $statusCounts = (clone $base)->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');

        $query = $this->applyStatusFilter(clone $base, $request->status)
            ->with(['user:id,name,email,phone', 'payment', 'items:id,order_id,product_id,product_name,quantity', 'items.product:id,image'])
            ->withCount('items');

        match ($request->get('sort')) {
            'oldest' => $query->oldest(),
            'total_desc' => $query->orderByDesc('total'),
            'total_asc' => $query->orderBy('total'),
            default => $query->latest(),
        };

        $perPage = in_array((int) $request->per_page, [15, 30, 50, 100], true) ? (int) $request->per_page : 15;
        $orders = $query->paginate($perPage)->withQueryString();
        $flaggedCount = Order::where('is_fraud_flagged', true)->count();

        // today at a glance
        $tz = setting('timezone', 'Asia/Dhaka');
        $todayStart = now($tz)->startOfDay()->utc();
        $today = Order::where('created_at', '>=', $todayStart)->whereNotIn('status', ['cancelled', 'refunded'])
            ->selectRaw('COUNT(*) as c, COALESCE(SUM(total),0) as s')->first();
        $summary = [
            'today_orders' => (int) $today->c,
            'today_sales' => (float) $today->s,
            'pending' => Order::where('status', 'pending')->count(),
            'to_ship' => Order::where('status', 'processing')->count(),
            'unverified' => \App\Models\Payment::where('status', 'pending_verification')->count(),
        ];
        $paymentMethods = Order::query()->whereNotNull('payment_method')->distinct()->orderBy('payment_method')->pluck('payment_method');

        return view('admin.orders.index', compact('orders', 'flaggedCount', 'statusCounts', 'summary', 'paymentMethods'));
    }

    /**
     * The Orders list's quick view (eye icon): customer, their order history across every
     * order with the same phone/account, courier record, items, money, timeline and actions —
     * rendered as one panel so the list never has to be left.
     */
    public function quick(Order $order)
    {
        $order->load(['user', 'items.product:id,name,slug,image', 'payment', 'createdBy:id,name']);

        if (! $order->fraud_checked_at) {
            $fraud = app(FraudDetectionService::class)->analyze($order);
            $order->update([
                'fraud_score' => $fraud['score'],
                'fraud_flags' => $fraud['flags'],
                'is_fraud_flagged' => $fraud['score'] >= 50,
                'fraud_checked_at' => now(),
            ]);
            $order->refresh()->load(['user', 'items.product:id,name,slug,image', 'payment', 'createdBy:id,name']);
            $this->notifyIfFraudFlagged($order, $fraud);
        }

        // every order from this customer — same account or same phone (last 10 digits)
        $tail = substr(preg_replace('/\D/', '', (string) $order->shipping_phone), -10);
        $history = Order::query()
            ->where(function ($q) use ($order, $tail) {
                $q->whereRaw('1 = 0');
                if ($order->user_id) {
                    $q->orWhere('user_id', $order->user_id);
                }
                if (strlen($tail) >= 8) {
                    $q->orWhere('shipping_phone', 'like', "%{$tail}");
                }
            })
            ->latest()
            ->get(['id', 'order_number', 'status', 'total', 'created_at']);
        $stats = [
            'orders' => $history->count(),
            'delivered' => $history->where('status', 'delivered')->count(),
            'cancelled' => $history->whereIn('status', ['cancelled', 'refunded'])->count(),
            'spent' => (float) $history->where('status', 'delivered')->sum('total'),
            'first' => $history->last()?->created_at,
        ];
        $closed = $stats['delivered'] + $stats['cancelled'];
        $stats['rate'] = $closed ? (int) round($stats['delivered'] / $closed * 100) : null;

        $timeline = \App\Models\AuditLog::with('user:id,name')
            ->where('model_type', Order::class)->where('model_id', $order->id)
            ->latest()->limit(15)->get(['id', 'user_id', 'action', 'description', 'new_values', 'created_at']);

        $courierCheck = $order->shipping_phone
            ? app(CourierFraudCheckService::class)->lastCheck($order->shipping_phone)
            : null;

        ActivityLogger::log('order.view', "Admin viewed order #{$order->order_number} (quick view)", $order);

        return response()->json([
            'id' => $order->id,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'html' => view('admin.orders._quick', [
                'order' => $order,
                'stats' => $stats,
                'history' => $history->where('id', '!=', $order->id)->take(6),
                'timeline' => $timeline,
                'courierCheck' => $courierCheck,
            ])->render(),
        ]);
    }

    /** Internal staff note on the order (auto-saved from the quick view). */
    public function note(Request $request, Order $order)
    {
        $data = $request->validate(['admin_note' => 'nullable|string|max:2000']);
        $order->forceFill(['admin_note' => $data['admin_note'] ?? null])->saveQuietly();

        return response()->json(['message' => 'Note saved.']);
    }

    /** Status for many orders at once (list bulk bar) — same rules as a single update. */
    public function bulkStatus(Request $request)
    {
        $data = $request->validate([
            'ids' => 'required|array|min:1|max:200',
            'ids.*' => 'integer|exists:orders,id',
            'status' => 'required|in:' . implode(',', self::STATUSES),
        ]);

        $changed = 0;
        foreach (Order::with('user')->whereIn('id', $data['ids'])->get() as $order) {
            if ($order->status !== $data['status']) {
                $this->applyStatus($order, $data['status']);
                $changed++;
            }
        }

        return response()->json([
            'message' => $changed . ' order(s) marked ' . ucfirst($data['status']) . ($changed < count($data['ids']) ? ' (' . (count($data['ids']) - $changed) . ' already were).' : '.'),
            'status' => $data['status'],
        ]);
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

        // DB-only lookup (no API call) — just shows whatever was last checked for this
        // shipping phone, if anything. The "Check" / "Re-check" button on the page is what
        // actually calls the courier API.
        $courierCheck = $order->shipping_phone
            ? app(CourierFraudCheckService::class)->lastCheck($order->shipping_phone)
            : null;

        return view('admin.orders.show', compact('order', 'courierCheck'));
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
            'status' => 'required|in:' . implode(',', self::STATUSES),
        ]);

        $this->applyStatus($order, $request->status);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Order status updated.', 'status' => $order->status, 'payment_status' => $order->payment_status]);
        }

        return back()->with('success', 'Order status updated.');
    }

    /** One status change with all its side effects (refund sync, stock, audit, customer notice). */
    private function applyStatus(Order $order, string $status): void
    {
        $old = $order->status;

        $order->update(['status' => $status]);

        if ($status === 'refunded' && $order->payment_status !== 'refunded') {
            $order->update(['payment_status' => 'refunded']);
            $this->syncPaymentRecord($order, 'refunded');
        }

        if (in_array($status, ['cancelled', 'refunded'], true)) {
            OrderStockService::restoreIfNeeded($order, $status, auth()->id());
        }

        AuditLogger::log(
            'order.status_updated',
            "Order {$order->order_number} status changed from {$old} to {$status}",
            $order,
            ['status' => $old],
            ['status' => $status]
        );

        if ($order->user) {
            NotificationDispatcher::customer('order_status_changed', $order->user, [
                'order_number' => $order->order_number,
                'old_status' => ucfirst($old),
                'new_status' => ucfirst($status),
                '_order' => $order,
            ]);
        }
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

        // The public order-tracking link (routes/web.php: guest.order.track) only ever checks
        // the token, not who placed the order — so backfilling one here (for orders that were
        // never a guest checkout and so never got one) is enough to let the invoice's QR code
        // deep-link straight to this order's tracking page for every order, not just guests'.
        if (! $order->guest_token) {
            $order->forceFill(['guest_token' => \Illuminate\Support\Str::random(64)])->save();
        }

        $pdf = Pdf::loadView('admin.orders.invoice', [
            'order' => $order,
            'trackingQr' => $this->invoiceTrackingQr($order),
        ])->setPaper('a4', 'portrait');

        $this->registerInvoiceFont($pdf);

        return $pdf->download("invoice-{$order->order_number}.pdf");
    }

    /**
     * A QR code (embedded as an inline SVG data URI, so it needs no GD/Imagick extension
     * and no temp file) linking straight to this order's public tracking page. Wrapped in
     * a try/catch so a QR-library hiccup can never take down invoice downloads — the
     * invoice template simply omits the code when this comes back null.
     */
    private function invoiceTrackingQr(Order $order): ?string
    {
        if (setting('invoice_show_qr', '1') !== '1' || ! $order->guest_token) {
            return null;
        }
        try {
            $url = route('guest.order.track', ['order_number' => $order->order_number, 'token' => $order->guest_token]);
            $result = (new \Endroid\QrCode\Builder\Builder(
                writer: new \Endroid\QrCode\Writer\SvgWriter(),
                data: $url,
                errorCorrectionLevel: \Endroid\QrCode\ErrorCorrectionLevel::Low,
                size: 160,
                margin: 0,
            ))->build();

            return $result->getDataUri();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Invoice QR generation failed: ' . $e->getMessage());

            return null;
        }
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
