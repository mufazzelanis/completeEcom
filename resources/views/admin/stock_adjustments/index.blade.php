@extends('layouts.admin')
@section('title', 'Stock Adjustments')

@section('content')
<div class="flex items-center justify-between mb-6">
    <p class="text-sm text-gray-500">Returns, damage, and manual stock adjustments</p>
    <a href="{{ route('admin.stock-adjustments.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-indigo-700 transition flex items-center space-x-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        <span>New Adjustment</span>
    </a>
</div>

@if(session('success'))<div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">{{ session('success') }}</div>@endif

<div class="bg-white rounded-2xl shadow-sm p-4 mb-4">
    <form method="GET" class="flex flex-wrap gap-3">
        <select name="product" class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">All Products</option>
            @foreach($products as $p)
                <option value="{{ $p->id }}" {{ request('product') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
            @endforeach
        </select>
        <select name="type" class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">All Types</option>
            <option value="return_in"   {{ request('type')=='return_in'   ? 'selected':'' }}>Customer Return</option>
            <option value="damage_out"  {{ request('type')=='damage_out'  ? 'selected':'' }}>Damage / Loss</option>
            <option value="manual_in"   {{ request('type')=='manual_in'   ? 'selected':'' }}>Manual Stock In</option>
            <option value="manual_out"  {{ request('type')=='manual_out'  ? 'selected':'' }}>Manual Stock Out</option>
            <option value="purchase_in" {{ request('type')=='purchase_in' ? 'selected':'' }}>Purchase Received</option>
        </select>
        <input type="date" name="date_from" value="{{ request('date_from') }}"
            class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        <input type="date" name="date_to" value="{{ request('date_to') }}"
            class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm hover:bg-indigo-700 transition">Filter</button>
        @if(request()->hasAny(['product','type','date_from','date_to']))
            <a href="{{ route('admin.stock-adjustments.index') }}" class="px-4 py-2 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50 transition">Clear</a>
        @endif
    </form>
</div>

<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr class="text-xs text-gray-500 uppercase tracking-wider">
                <th class="px-6 py-3 text-left">Date</th>
                <th class="px-6 py-3 text-left">Product</th>
                <th class="px-6 py-3 text-center">Type</th>
                <th class="px-6 py-3 text-center">Qty Change</th>
                <th class="px-6 py-3 text-center">Before</th>
                <th class="px-6 py-3 text-center">After</th>
                <th class="px-6 py-3 text-left">Reason</th>
                <th class="px-6 py-3 text-left">By</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($adjustments as $adj)
            <tr class="hover:bg-gray-50 transition">
                <td class="px-6 py-3 text-xs text-gray-500 whitespace-nowrap">{{ $adj->created_at->format('d M Y H:i') }}</td>
                <td class="px-6 py-3">
                    <p class="text-sm font-medium text-gray-900">{{ $adj->product->name }}</p>
                    @if($adj->reference)<p class="text-xs text-gray-400">{{ $adj->reference }}</p>@endif
                </td>
                <td class="px-6 py-3 text-center">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $adj->typeBadge() }}">{{ $adj->typeLabel() }}</span>
                </td>
                <td class="px-6 py-3 text-center">
                    <span class="text-sm font-bold {{ $adj->quantity > 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $adj->quantity > 0 ? '+' : '' }}{{ $adj->quantity }}
                    </span>
                </td>
                <td class="px-6 py-3 text-center text-sm text-gray-500">{{ $adj->stock_before }}</td>
                <td class="px-6 py-3 text-center text-sm font-medium text-gray-800">{{ $adj->stock_after }}</td>
                <td class="px-6 py-3 text-sm text-gray-600 max-w-xs truncate" title="{{ $adj->reason }}">{{ $adj->reason }}</td>
                <td class="px-6 py-3 text-sm text-gray-500">{{ $adj->adjustedBy->name }}</td>
            </tr>
            @empty
            <tr><td colspan="8" class="px-6 py-12 text-center text-gray-400 text-sm">No adjustments found.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($adjustments->hasPages())
    <div class="px-6 py-4 border-t border-gray-100">{{ $adjustments->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
