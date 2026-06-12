@extends('layouts.admin')
@section('title', 'Orders')

@section('content')
<div class="flex flex-wrap items-center gap-4 mb-6">
    <form action="{{ route('admin.orders.index') }}" method="GET" class="flex items-center space-x-3 flex-wrap gap-2">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search order or customer..."
            class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 w-56">
        <select name="status" class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none">
            <option value="">All Status</option>
            @foreach(['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'] as $s)
                <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
        <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-xl text-sm hover:bg-gray-700 transition">Filter</button>
    </form>
    @if($flaggedCount > 0)
    <a href="{{ route('admin.orders.index', ['fraud' => 1]) }}"
       class="flex items-center gap-2 bg-red-50 border border-red-200 text-red-700 px-3 py-2 rounded-xl text-sm font-medium hover:bg-red-100 transition {{ request('fraud') ? 'ring-2 ring-red-400' : '' }}">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
        Fraud Flagged <span class="bg-red-600 text-white text-xs px-1.5 py-0.5 rounded-full font-bold">{{ $flaggedCount }}</span>
    </a>
    @endif
</div>

<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr class="text-xs text-gray-500 uppercase tracking-wider">
                <th class="px-6 py-3 text-left">Order</th>
                <th class="px-6 py-3 text-left">Customer</th>
                <th class="px-6 py-3 text-right">Total</th>
                <th class="px-6 py-3 text-center">Payment</th>
                <th class="px-6 py-3 text-center">Status</th>
                <th class="px-6 py-3 text-center">Date</th>
                <th class="px-6 py-3 text-center">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($orders as $order)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('admin.orders.show', $order->id) }}" class="font-medium text-indigo-600 text-sm hover:text-indigo-700">{{ $order->order_number }}</a>
                            @if($order->is_fraud_flagged)
                            <span title="Fraud Flagged — Score: {{ $order->fraud_score }}">
                                <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                            </span>
                            @elseif($order->fraud_checked_at && $order->fraud_score >= 20)
                            <span class="text-xs text-yellow-500 font-medium" title="Medium fraud risk — Score: {{ $order->fraud_score }}">⚠</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-700">{{ $order->user->name }}</td>
                    <td class="px-6 py-4 text-right font-semibold text-gray-900 text-sm">৳{{ number_format($order->total) }}</td>
                    <td class="px-6 py-4 text-center">
                        <span class="px-2 py-1 rounded-full text-xs font-medium capitalize {{ $order->payment_status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                            {{ $order->payment_status }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="px-2 py-1 rounded-full text-xs font-medium capitalize {{ $order->status_badge }}">{{ $order->status }}</span>
                    </td>
                    <td class="px-6 py-4 text-center text-xs text-gray-500">{{ $order->created_at->format('M d, Y') }}</td>
                    <td class="px-6 py-4 text-center">
                        <div class="flex items-center justify-center gap-3">
                            <a href="{{ route('admin.orders.show', $order->id) }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">View</a>
                            <a href="{{ route('admin.orders.invoice', $order->id) }}" class="text-red-500 hover:text-red-700 text-sm font-medium flex items-center gap-1" title="Download Invoice PDF">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                PDF
                            </a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-6 py-12 text-center text-gray-400">No orders found.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-6 py-4 border-t border-gray-100">{{ $orders->links() }}</div>
</div>
@endsection
