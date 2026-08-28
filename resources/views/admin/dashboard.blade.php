@extends('layouts.admin')
@section('title', 'Dashboard')

@section('content')

<!-- Stats Grid -->
@php
    $colorClasses = [
        'blue'   => ['bg' => 'bg-blue-100',   'text' => 'text-blue-600'],
        'green'  => ['bg' => 'bg-green-100',  'text' => 'text-green-600'],
        'purple' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-600'],
        'yellow' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-600'],
        'red'    => ['bg' => 'bg-red-100',    'text' => 'text-red-600'],
        'indigo' => ['bg' => 'bg-indigo-100', 'text' => 'text-indigo-600'],
        'teal'   => ['bg' => 'bg-teal-100',   'text' => 'text-teal-600'],
    ];
    $pendingPct = $stats['total_orders'] > 0 ? round($stats['pending_orders'] / $stats['total_orders'] * 100) : 0;
@endphp
<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6 mb-8">
    @foreach([
        ['label' => 'Total Orders', 'value' => $stats['total_orders'], 'icon' => 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z', 'color' => 'blue', 'sub' => $stats['today_orders'] . ' new today', 'href' => route('admin.orders.index')],
        ['label' => 'Pending Orders', 'value' => $stats['pending_orders'], 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'yellow', 'sub' => $pendingPct . '% of total', 'href' => route('admin.orders.index', ['status' => 'pending'])],
        ['label' => 'Paid Orders', 'value' => $stats['paid_orders'], 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'green', 'sub' => '৳' . number_format($stats['total_revenue']) . ' collected', 'href' => route('admin.orders.index', ['payment_status' => 'paid'])],
        ['label' => 'Cancelled / Refunded', 'value' => $stats['cancelled_orders'], 'icon' => 'M6 18L18 6M6 6l12 12', 'color' => 'red', 'sub' => '৳' . number_format($stats['cancelled_revenue']) . ' lost', 'href' => route('admin.orders.index', ['status' => 'cancelled,refunded'])],
        ['label' => 'Revenue', 'value' => '৳' . number_format($stats['total_revenue']), 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'indigo', 'sub' => 'Avg ৳' . number_format($stats['avg_order_value']) . '/order', 'href' => route('admin.reports.sales')],
        ['label' => 'Total Products', 'value' => $stats['total_products'], 'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4', 'color' => 'teal', 'sub' => $stats['total_categories'] . ' categories', 'href' => route('admin.products.index')],
        ['label' => 'Out of Stock', 'value' => $stats['out_of_stock'], 'icon' => 'M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0H4', 'color' => 'red', 'sub' => 'need restock', 'href' => route('admin.products.index', ['stock_status' => 'out'])],
        ['label' => 'Customers', 'value' => $stats['total_users'], 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z', 'color' => 'purple', 'sub' => $stats['new_customers'] . ' new this month', 'href' => route('admin.users.index', ['role' => 'customer'])],
    ] as $stat)
        <a href="{{ $stat['href'] }}" class="block bg-white rounded-2xl shadow-sm p-6 hover:shadow-md hover:-translate-y-0.5 transition transform">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center {{ $colorClasses[$stat['color']]['bg'] }}">
                    <svg class="w-6 h-6 {{ $colorClasses[$stat['color']]['text'] }}"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $stat['icon'] }}"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ $stat['value'] }}</p>
            <p class="text-sm text-gray-500 mt-1">{{ $stat['label'] }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ $stat['sub'] }}</p>
        </a>
    @endforeach
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Recent Orders -->
    <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm">
        <div class="flex items-center justify-between p-6 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">Recent Orders</h2>
            <a href="{{ route('admin.orders.index') }}" class="text-orange-600 text-sm hover:text-orange-700">View All</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="text-xs text-gray-500 uppercase tracking-wider border-b border-gray-100">
                        <th class="px-6 py-3 text-left">Order</th>
                        <th class="px-6 py-3 text-left">Customer</th>
                        <th class="px-6 py-3 text-left">Total</th>
                        <th class="px-6 py-3 text-left">Status</th>
                        <th class="px-6 py-3 text-left">Payment</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($recentOrders as $order)
                        <tr class="hover:bg-gray-50 transition cursor-pointer" onclick="window.location='{{ route('admin.orders.show', $order->id) }}'">
                            <td class="px-6 py-4">
                                <a href="{{ route('admin.orders.show', $order->id) }}" class="font-medium text-orange-600 text-sm hover:text-orange-700">{{ $order->order_number }}</a>
                                <p class="text-xs text-gray-400">{{ $order->created_at->diffForHumans() }}</p>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $order->user->name ?? 'Guest' }}</td>
                            <td class="px-6 py-4 text-sm font-semibold text-gray-900">৳{{ number_format($order->total) }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded-full text-xs font-medium capitalize {{ $order->status_badge }}">{{ $order->status }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded-full text-xs font-medium capitalize {{ $order->payment_status_badge }}">{{ $order->payment_status }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Top Products -->
    <div class="bg-white rounded-2xl shadow-sm">
        <div class="flex items-center justify-between p-6 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">Top Selling Products</h2>
                <a href="{{ route('admin.products.index') }}" class="text-orange-600 text-sm hover:text-orange-700">View All</a>
        </div>
        <div class="p-6 space-y-4">
            @forelse($topProducts as $item)
                <a href="{{ $item->product ? route('admin.products.edit', $item->product_id) : '#' }}"
                   class="flex items-center space-x-4 -m-2 p-2 rounded-xl hover:bg-gray-50 transition {{ $item->product ? '' : 'pointer-events-none' }}">
                    <div class="w-10 h-10 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0">
                        @if($item->product?->image)
                            <img src="{{ Storage::url($item->product->image) }}" class="w-full h-full object-cover">
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-800 truncate">{{ $item->product_name }}</p>
                        <p class="text-xs text-gray-400">
                            {{ $item->qty_sold }} sold ·
                            Price ৳{{ number_format($item->product?->sale_price ?? $item->product?->price ?? 0) }}
                        </p>
                    </div>
                    <span class="text-sm font-bold text-gray-900">৳{{ number_format($item->revenue) }}</span>
                </a>
            @empty
                <p class="text-sm text-gray-400 text-center py-4">No paid orders yet.</p>
            @endforelse
        </div>
    </div>
</div>

@endsection
