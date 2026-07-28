@extends('layouts.seller')
@section('title', 'My Orders')
@section('pageTitle', 'My Orders')

@section('content')
<h1 class="text-xl font-bold text-gray-800 mb-6">Orders Containing My Products</h1>
<p class="text-sm text-gray-500 -mt-4 mb-6">Order status is managed by admin — this is a read-only view of your own items in each order.</p>

<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr class="text-xs text-gray-500 uppercase tracking-wider">
                <th class="px-5 py-3 text-left">Order #</th>
                <th class="px-5 py-3 text-left">Date</th>
                <th class="px-5 py-3 text-left">My Items</th>
                <th class="px-5 py-3 text-center">Order Status</th>
                <th class="px-5 py-3 text-center">Payout Status</th>
                <th class="px-5 py-3 text-right">My Total</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($orders as $order)
            <tr class="hover:bg-gray-50 transition align-top">
                <td class="px-5 py-3 font-mono text-xs text-gray-700">{{ $order->order_number }}</td>
                <td class="px-5 py-3 text-gray-500 text-xs">{{ $order->created_at->format('M d, Y') }}</td>
                <td class="px-5 py-3">
                    @foreach($order->items as $item)
                        <p class="text-gray-700">{{ $item->product_name }} <span class="text-gray-400">x{{ $item->quantity }}</span></p>
                    @endforeach
                </td>
                <td class="px-5 py-3 text-center">
                    @php
                        $statusColor = match($order->status) {
                            'delivered' => 'bg-green-100 text-green-700',
                            'cancelled', 'refunded' => 'bg-red-100 text-red-700',
                            default => 'bg-yellow-100 text-yellow-700',
                        };
                    @endphp
                    <span class="px-2 py-1 rounded-full text-xs font-medium {{ $statusColor }}">{{ ucfirst($order->status) }}</span>
                </td>
                <td class="px-5 py-3 text-center">
                    @foreach($order->items as $item)
                        @php
                            $ledgerStatus = $ledgerByItemId[$item->id] ?? 'hold';
                            $ledgerColor = match($ledgerStatus) {
                                'paid' => 'bg-green-100 text-green-700',
                                'available' => 'bg-blue-100 text-blue-700',
                                'cancelled' => 'bg-red-100 text-red-700',
                                default => 'bg-yellow-100 text-yellow-700',
                            };
                        @endphp
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $ledgerColor }} inline-block mb-1">{{ ucfirst($ledgerStatus) }}</span><br>
                    @endforeach
                </td>
                <td class="px-5 py-3 text-right font-semibold text-gray-800">৳{{ number_format($order->items->sum('subtotal')) }}</td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-5 py-16 text-center text-gray-400">No orders yet.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-5 py-4 border-t border-gray-100">{{ $orders->links() }}</div>
</div>
@endsection
