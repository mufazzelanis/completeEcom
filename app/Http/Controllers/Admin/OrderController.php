<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\AuditLogger;
use App\Services\FraudDetectionService;
use App\Services\Notifications\NotificationDispatcher;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with('user');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('fraud')) {
            $query->where('is_fraud_flagged', true);
        }

        if ($request->filled('search')) {
            $query->where('order_number', 'like', '%' . $request->search . '%')
                ->orWhereHas('user', fn($q) => $q->where('name', 'like', '%' . $request->search . '%'));
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
                'fraud_score'      => $fraud['score'],
                'fraud_flags'      => $fraud['flags'],
                'is_fraud_flagged' => $fraud['score'] >= 40,
                'fraud_checked_at' => now(),
            ]);
            $order->refresh();
        }

        return view('admin.orders.show', compact('order'));
    }

    public function update(Request $request, Order $order)
    {
        $request->validate([
            'payment_status' => 'nullable|in:pending,paid,failed,refunded',
        ]);

        $old = $order->payment_status;
        $order->update($request->only(['payment_status']));

        AuditLogger::log(
            'order.payment_updated',
            "Order {$order->order_number} payment status changed from {$old} to {$request->payment_status}",
            $order,
            ['payment_status' => $old],
            ['payment_status' => $request->payment_status]
        );

        return back()->with('success', 'Order updated.');
    }

    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled,refunded',
        ]);

        $old = $order->status;
        $order->update(['status' => $request->status]);

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
                'old_status'   => ucfirst($old),
                'new_status'   => ucfirst($request->status),
                'url'          => route('account.orders.show', $order),
            ]);
        }

        return back()->with('success', 'Order status updated.');
    }

    public function recheckFraud(Order $order)
    {
        $order->load('user', 'items');
        $fraud = app(FraudDetectionService::class)->analyze($order);
        $order->update([
            'fraud_score'      => $fraud['score'],
            'fraud_flags'      => $fraud['flags'],
            'is_fraud_flagged' => $fraud['score'] >= 40,
            'fraud_checked_at' => now(),
        ]);

        AuditLogger::log(
            'order.fraud_rechecked',
            "Fraud check re-run on order {$order->order_number} — score: {$fraud['score']} ({$fraud['risk_level']})",
            $order,
            [],
            ['score' => $fraud['score'], 'risk_level' => $fraud['risk_level'], 'flags' => $fraud['flags']]
        );

        return back()->with('success', 'Fraud check complete. Score: ' . $fraud['score'] . ' (' . ucfirst($fraud['risk_level']) . ')');
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
        return $pdf->download("invoice-{$order->order_number}.pdf");
    }

    public function create() {}
    public function store(Request $request) {}
    public function edit(string $id) {}
}
