@extends('layouts.admin')
@section('title', 'Orders')

@section('content')
<div class="flex flex-wrap items-center gap-4 mb-6">
    <form action="{{ route('admin.orders.index') }}" method="GET" class="flex items-center space-x-3">
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
                        <a href="{{ route('admin.orders.show', $order->id) }}" class="font-medium text-indigo-600 text-sm hover:text-indigo-700">{{ $order->order_number }}</a>
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
                        <a href="{{ route('admin.orders.show', $order->id) }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">View</a>
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
