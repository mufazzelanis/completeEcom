@extends('layouts.admin')
@section('title', 'Payment — '.$payment->order->order_number)

@section('content')
<div class="max-w-4xl">
    <a href="{{ route('admin.payments.index') }}" class="text-indigo-600 hover:text-indigo-700 text-sm flex items-center space-x-2 mb-6">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        <span>Back to Payments</span>
    </a>

    @if(session('success'))<div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">{{ session('error') }}</div>@endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left: payment details --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Status card --}}
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-800">Payment Details</h3>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold {{ $payment->statusBadge() }}">{{ $payment->statusLabel() }}</span>
                </div>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-gray-500 text-xs mb-1">Payment Method</p>
                        <p class="font-medium text-gray-900">{{ $payment->payment_method_name }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-xs mb-1">Amount</p>
                        <p class="font-bold text-gray-900 text-lg">৳{{ number_format($payment->amount, 2) }}</p>
                        @if($payment->charge > 0)<p class="text-xs text-gray-400">incl. ৳{{ number_format($payment->charge,2) }} gateway fee</p>@endif
                    </div>
                    @if($payment->transaction_id)
                    <div>
                        <p class="text-gray-500 text-xs mb-1">Transaction ID</p>
                        <p class="font-mono font-medium text-gray-900 break-all">{{ $payment->transaction_id }}</p>
                    </div>
                    @endif
                    @if($payment->sender_number)
                    <div>
                        <p class="text-gray-500 text-xs mb-1">Sender Number</p>
                        <p class="font-medium text-gray-900">{{ $payment->sender_number }}</p>
                    </div>
                    @endif
                    <div>
                        <p class="text-gray-500 text-xs mb-1">Created</p>
                        <p class="text-gray-700">{{ $payment->created_at->format('d M Y H:i') }}</p>
                    </div>
                    @if($payment->verified_at)
                    <div>
                        <p class="text-gray-500 text-xs mb-1">Verified At</p>
                        <p class="text-gray-700">{{ $payment->verified_at->format('d M Y H:i') }}</p>
                        @if($payment->verifiedBy)<p class="text-xs text-gray-400">by {{ $payment->verifiedBy->name }}</p>@endif
                    </div>
                    @endif
                    <div>
                        <p class="text-gray-500 text-xs mb-1">IP Address</p>
                        <p class="text-gray-500 font-mono text-xs">{{ $payment->ip_address ?? '—' }}</p>
                    </div>
                </div>
                @if($payment->admin_note)
                <div class="mt-4 bg-gray-50 rounded-xl p-3">
                    <p class="text-xs text-gray-500 mb-1">Admin Note</p>
                    <p class="text-sm text-gray-700">{{ $payment->admin_note }}</p>
                </div>
                @endif
            </div>

            {{-- Order items --}}
            <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-800">Order Items — {{ $payment->order->order_number }}</h3>
                </div>
                <table class="w-full">
                    <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                        <tr>
                            <th class="px-6 py-3 text-left">Product</th>
                            <th class="px-6 py-3 text-center">Qty</th>
                            <th class="px-6 py-3 text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($payment->order->items as $item)
                        <tr>
                            <td class="px-6 py-3 text-sm text-gray-800">{{ $item->product_name }}</td>
                            <td class="px-6 py-3 text-center text-sm text-gray-600">{{ $item->quantity }}</td>
                            <td class="px-6 py-3 text-right text-sm font-medium text-gray-800">৳{{ number_format($item->subtotal, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50 text-sm">
                        <tr><td colspan="2" class="px-6 py-2 text-right text-gray-500">Subtotal</td><td class="px-6 py-2 text-right text-gray-700">৳{{ number_format($payment->order->subtotal,2) }}</td></tr>
                        @if($payment->order->discount > 0)
                        <tr><td colspan="2" class="px-6 py-2 text-right text-gray-500">Discount</td><td class="px-6 py-2 text-right text-green-600">-৳{{ number_format($payment->order->discount,2) }}</td></tr>
                        @endif
                        <tr><td colspan="2" class="px-6 py-2 text-right text-gray-500">Shipping</td><td class="px-6 py-2 text-right text-gray-700">৳{{ number_format($payment->order->shipping,2) }}</td></tr>
                        @if($payment->charge > 0)
                        <tr><td colspan="2" class="px-6 py-2 text-right text-gray-500">Payment Charge</td><td class="px-6 py-2 text-right text-gray-700">৳{{ number_format($payment->charge,2) }}</td></tr>
                        @endif
                        <tr class="font-bold"><td colspan="2" class="px-6 py-2 text-right text-gray-800">Total</td><td class="px-6 py-2 text-right text-gray-900">৳{{ number_format($payment->amount,2) }}</td></tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Right: actions --}}
        <div class="space-y-4">

            {{-- Verify / Reject --}}
            @if(in_array($payment->status, ['pending_verification', 'pending']))
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Verify Payment</h3>
                <p class="text-xs text-gray-500 mb-3">Customer provided transaction ID: <span class="font-mono font-medium text-gray-800">{{ $payment->transaction_id ?? 'N/A' }}</span></p>
                <form action="{{ route('admin.payments.verify', $payment) }}" method="POST" class="mb-3">
                    @csrf
                    <textarea name="admin_note" placeholder="Optional note (e.g. verified on bKash app)" rows="2"
                        class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm mb-3 focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
                    <button type="submit" class="w-full py-2.5 bg-green-600 text-white rounded-xl text-sm font-semibold hover:bg-green-700 transition">
                        ✓ Verify & Mark Paid
                    </button>
                </form>
                <form action="{{ route('admin.payments.reject', $payment) }}" method="POST" onsubmit="return confirm('Reject this payment?')">
                    @csrf
                    <textarea name="admin_note" placeholder="Reason for rejection (required)" rows="2" required
                        class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm mb-3 focus:outline-none focus:ring-2 focus:ring-red-400"></textarea>
                    <button type="submit" class="w-full py-2.5 border border-red-300 text-red-600 rounded-xl text-sm font-medium hover:bg-red-50 transition">
                        ✕ Reject Payment
                    </button>
                </form>
            </div>
            @endif

            {{-- Refund --}}
            @if($payment->status === 'completed')
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Refund</h3>
                <form action="{{ route('admin.payments.refund', $payment) }}" method="POST" onsubmit="return confirm('Issue refund for this payment?')">
                    @csrf
                    <textarea name="admin_note" placeholder="Reason for refund (required)" rows="2" required
                        class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm mb-3 focus:outline-none focus:ring-2 focus:ring-gray-400"></textarea>
                    <button type="submit" class="w-full py-2.5 border border-gray-300 text-gray-700 rounded-xl text-sm font-medium hover:bg-gray-50 transition">
                        Issue Refund
                    </button>
                </form>
            </div>
            @endif

            {{-- Order info --}}
            <div class="bg-white rounded-2xl shadow-sm p-6 space-y-3 text-sm">
                <h3 class="font-semibold text-gray-800">Customer</h3>
                <p class="font-medium text-gray-900">{{ $payment->order->user->name }}</p>
                <p class="text-gray-500">{{ $payment->order->user->email }}</p>
                <div class="border-t border-gray-100 pt-3">
                    <p class="text-gray-500 text-xs">Shipping To</p>
                    <p class="text-gray-800 mt-1">{{ $payment->order->shipping_name }}</p>
                    <p class="text-gray-600 text-xs">{{ $payment->order->shipping_address }}, {{ $payment->order->shipping_city }}</p>
                    <p class="text-gray-500 text-xs">{{ $payment->order->shipping_phone }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
