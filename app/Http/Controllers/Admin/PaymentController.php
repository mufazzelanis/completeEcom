<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with(['order', 'paymentMethod', 'verifiedBy']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('method')) {
            $query->where('payment_method_slug', $request->method);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('transaction_id', 'like', '%' . $request->search . '%')
                  ->orWhere('sender_number', 'like', '%' . $request->search . '%')
                  ->orWhereHas('order', fn($o) => $o->where('order_number', 'like', '%' . $request->search . '%'));
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $payments = $query->latest()->paginate(25);

        $methods = Payment::select('payment_method_slug', 'payment_method_name')
            ->distinct()->orderBy('payment_method_name')->get();

        $stats = [
            'pending_verification' => Payment::where('status', 'pending_verification')->count(),
            'completed_today'      => Payment::where('status', 'completed')->whereDate('verified_at', today())->count(),
            'total_today'          => Payment::whereDate('created_at', today())->sum('amount'),
        ];

        return view('admin.payments.index', compact('payments', 'methods', 'stats'));
    }

    public function show(Payment $payment)
    {
        $payment->load(['order.items.product', 'paymentMethod', 'verifiedBy']);
        return view('admin.payments.show', compact('payment'));
    }

    public function verify(Request $request, Payment $payment)
    {
        if (!in_array($payment->status, ['pending_verification', 'pending'])) {
            return back()->with('error', 'Payment is already ' . $payment->status . '.');
        }

        $request->validate([
            'admin_note' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($request, $payment) {
            $payment->update([
                'status'      => 'completed',
                'verified_by' => auth()->id(),
                'verified_at' => now(),
                'admin_note'  => $request->admin_note,
            ]);

            $payment->order->update(['payment_status' => 'paid']);
        });

        return back()->with('success', 'Payment verified and marked as completed.');
    }

    public function reject(Request $request, Payment $payment)
    {
        if (!in_array($payment->status, ['pending_verification', 'pending'])) {
            return back()->with('error', 'Payment cannot be rejected in its current state.');
        }

        $request->validate([
            'admin_note' => 'required|string|max:500',
        ]);

        DB::transaction(function () use ($request, $payment) {
            $payment->update([
                'status'      => 'failed',
                'verified_by' => auth()->id(),
                'verified_at' => now(),
                'admin_note'  => $request->admin_note,
            ]);

            $payment->order->update(['payment_status' => 'failed']);
        });

        return back()->with('success', 'Payment rejected.');
    }

    public function refund(Request $request, Payment $payment)
    {
        if ($payment->status !== 'completed') {
            return back()->with('error', 'Only completed payments can be refunded.');
        }

        $request->validate([
            'admin_note' => 'required|string|max:500',
        ]);

        DB::transaction(function () use ($request, $payment) {
            $payment->update([
                'status'      => 'refunded',
                'refunded_at' => now(),
                'admin_note'  => $request->admin_note,
            ]);

            $payment->order->update([
                'payment_status' => 'refunded',
                'status'         => 'refunded',
            ]);
        });

        return back()->with('success', 'Payment marked as refunded and order updated.');
    }
}
