@extends('layouts.admin')
@section('title', 'Landing Orders')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-lg font-semibold text-gray-800">Landing Orders</h1>
        <p class="text-xs text-gray-400 mt-0.5">Orders placed through a landing page's own order form — separate from the regular storefront checkout, and not tied to any customer account.</p>
    </div>
    <a href="{{ route('admin.landing-pages.report') }}" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">Landing Report →</a>
</div>

<form action="{{ route('admin.landing-pages.orders') }}" method="GET" class="flex items-center flex-wrap gap-2 mb-6">
    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search order#, name or phone..."
        class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 w-64">
    <select name="landing_page_id" class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none">
        <option value="">All Landing Pages</option>
        @foreach($pages as $p)
            <option value="{{ $p->id }}" {{ (int) request('landing_page_id') === $p->id ? 'selected' : '' }}>{{ $p->title }}</option>
        @endforeach
    </select>
    <select name="status" class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none">
        <option value="">All Status</option>
        @foreach(['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'] as $s)
            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
        @endforeach
    </select>
    <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-xl text-sm hover:bg-gray-700 transition">Filter</button>
    @if(request()->anyFilled(['search', 'landing_page_id', 'status']))
        <a href="{{ route('admin.landing-pages.orders') }}" class="text-sm text-gray-500 hover:text-gray-700">Clear</a>
    @endif
</form>

<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr class="text-xs text-gray-500 uppercase tracking-wider">
                <th class="px-6 py-3 text-left">Order</th>
                <th class="px-6 py-3 text-left">Landing Page</th>
                <th class="px-6 py-3 text-left">Customer</th>
                <th class="px-6 py-3 text-left">Form Answers</th>
                <th class="px-6 py-3 text-right">Total</th>
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
                    <td class="px-6 py-4 text-sm text-gray-700">{{ $order->landingPage->title ?? '—' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-700">
                        <div>{{ $order->shipping_name }}</div>
                        <div class="text-xs text-gray-400">{{ $order->shipping_phone }}</div>
                        @if($order->shipping_address)
                            <div class="text-xs text-gray-400">{{ Str::limit($order->shipping_address, 40) }}</div>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-xs text-gray-500">
                        @php
                            $fieldLabels = collect($order->landingPage->order_form_fields ?? [])->keyBy('key');
                        @endphp
                        @forelse($order->landing_page_data ?? [] as $key => $value)
                            @if($value !== null && $value !== '')
                                <div><span class="text-gray-400">{{ $fieldLabels[$key]['label'] ?? $key }}:</span> {{ is_bool($value) ? ($value ? 'Yes' : 'No') : $value }}</div>
                            @endif
                        @empty
                            —
                        @endforelse
                    </td>
                    <td class="px-6 py-4 text-right font-semibold text-gray-900 text-sm">৳{{ number_format($order->total) }}</td>
                    <td class="px-6 py-4 text-center">
                        <span class="px-2 py-1 rounded-full text-xs font-medium capitalize {{ $order->status_badge }}">{{ $order->status }}</span>
                    </td>
                    <td class="px-6 py-4 text-center text-xs text-gray-500">{{ $order->created_at->format('M d, Y') }}</td>
                    <td class="px-6 py-4 text-center">
                        <a href="{{ route('admin.orders.show', $order->id) }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">View</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="px-6 py-12 text-center text-gray-400">No landing page orders yet.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-6 py-4 border-t border-gray-100">{{ $orders->links() }}</div>
</div>
@endsection
