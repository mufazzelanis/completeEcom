@extends('layouts.admin')
@section('title', 'Stock Management')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Stock Management</h1>
        <p class="text-sm text-gray-500 mt-1">Update product stock levels and view history</p>
    </div>
    <a href="{{ route('admin.stock-adjustments.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
        View Stock History →
    </a>
</div>

@if(session('success'))
<div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">{{ session('success') }}</div>
@endif

{{-- Filters --}}
<div class="bg-white rounded-2xl shadow-sm p-4 mb-4">
    <form method="GET" class="flex flex-wrap gap-3 items-center">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Product name or SKU…"
            class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 w-56">
        <select name="stock_filter" class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">All Stock Levels</option>
            <option value="out"  {{ request('stock_filter') === 'out'  ? 'selected' : '' }}>Out of Stock (0)</option>
            <option value="low"  {{ request('stock_filter') === 'low'  ? 'selected' : '' }}>Low Stock (≤5)</option>
            <option value="ok"   {{ request('stock_filter') === 'ok'   ? 'selected' : '' }}>In Stock (>5)</option>
        </select>
        <select name="category" class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">All Categories</option>
            @foreach($categories as $cat)
            <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm hover:bg-indigo-700 transition">Filter</button>
        @if(request()->hasAny(['search','stock_filter','category']))
        <a href="{{ route('admin.stock-management.index') }}" class="px-4 py-2 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50 transition">Clear</a>
        @endif
    </form>
</div>

<form action="{{ route('admin.stock-management.update') }}" method="POST" id="bulkStockForm">
@csrf
<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <p class="text-sm text-gray-500">Edit stock quantities inline, then click <strong>Save All Changes</strong>.</p>
        <button type="submit" class="bg-indigo-600 text-white px-5 py-2 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition">
            Save All Changes
        </button>
    </div>
    <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr class="text-xs text-gray-500 uppercase tracking-wider">
                <th class="px-6 py-3 text-left">Product</th>
                <th class="px-6 py-3 text-left">SKU</th>
                <th class="px-6 py-3 text-center w-28">Current</th>
                <th class="px-6 py-3 text-center w-36">New Stock</th>
                <th class="px-6 py-3 text-left">Reason</th>
                <th class="px-6 py-3 text-right w-24">Quick Set</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50" x-data>
            @forelse($products as $index => $product)
            <tr class="hover:bg-gray-50/50 transition" x-data="{ stock: {{ $product->stock }} }">
                <input type="hidden" name="products[{{ $index }}][id]" value="{{ $product->id }}">
                <td class="px-6 py-3">
                    <p class="text-sm font-medium text-gray-900">{{ $product->name }}</p>
                    <p class="text-xs text-gray-400">{{ $product->category->name ?? '—' }}</p>
                </td>
                <td class="px-6 py-3 text-xs font-mono text-gray-500">{{ $product->sku ?? '—' }}</td>
                <td class="px-6 py-3 text-center">
                    <span class="text-sm font-bold {{ $product->stock === 0 ? 'text-red-600' : ($product->stock <= 5 ? 'text-orange-500' : 'text-gray-900') }}">
                        {{ $product->stock }}
                    </span>
                    @if($product->stock === 0)
                    <span class="ml-1 text-xs bg-red-100 text-red-700 px-1.5 py-0.5 rounded-full">Out</span>
                    @elseif($product->stock <= 5)
                    <span class="ml-1 text-xs bg-orange-100 text-orange-700 px-1.5 py-0.5 rounded-full">Low</span>
                    @endif
                </td>
                <td class="px-6 py-3 text-center">
                    <input type="number" name="products[{{ $index }}][stock]" min="0"
                        x-model="stock"
                        class="w-24 text-center border border-gray-200 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                        :class="stock != {{ $product->stock }} ? 'border-indigo-400 bg-indigo-50' : ''">
                </td>
                <td class="px-6 py-3">
                    <input type="text" name="products[{{ $index }}][reason]" placeholder="Optional note…"
                        class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 text-gray-700">
                </td>
                <td class="px-6 py-3 text-right">
                    <div class="flex justify-end gap-1">
                        <button type="button" @click="stock = Math.max(0, stock - 1)"
                            class="w-7 h-7 flex items-center justify-center border border-gray-200 rounded-lg text-gray-500 hover:bg-gray-100 text-sm font-bold">−</button>
                        <button type="button" @click="stock = stock + 1"
                            class="w-7 h-7 flex items-center justify-center border border-gray-200 rounded-lg text-gray-500 hover:bg-gray-100 text-sm font-bold">+</button>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-6 py-12 text-center text-gray-400 text-sm">No products found.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($products->hasPages())
    <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
        <div>{{ $products->withQueryString()->links() }}</div>
        <button type="submit" class="bg-indigo-600 text-white px-5 py-2 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition">
            Save All Changes
        </button>
    </div>
    @endif
</div>
</form>
@endsection
