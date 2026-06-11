@extends('layouts.admin')
@section('title', 'Multi-Warehouse Stock')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Multi-Warehouse Stock</h1>
        <p class="text-sm text-gray-500 mt-1">View and manage product stock per warehouse</p>
    </div>
    <a href="{{ route('admin.warehouses.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
        Manage Warehouses
    </a>
</div>

@if(session('success'))<div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">{{ session('success') }}</div>@endif

{{-- Filters --}}
<div class="bg-white rounded-2xl shadow-sm p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Warehouse *</label>
            <select name="warehouse_id" onchange="this.form.submit()"
                class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">Select Warehouse…</option>
                @foreach($warehouses as $wh)
                <option value="{{ $wh->id }}" {{ request('warehouse_id') == $wh->id ? 'selected' : '' }}>{{ $wh->name }} ({{ $wh->code }})</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Name or SKU…"
                class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 w-48">
        </div>
        @if(request('warehouse_id'))<input type="hidden" name="warehouse_id" value="{{ request('warehouse_id') }}">@endif
        <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-xl text-sm hover:bg-gray-700 transition">Search</button>
    </form>
</div>

@if($selectedWarehouse)
<form action="{{ route('admin.warehouse-stock.update') }}" method="POST">
    @csrf
    <input type="hidden" name="warehouse_id" value="{{ $selectedWarehouse->id }}">

    <div class="bg-white rounded-2xl shadow-sm overflow-hidden mb-4">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h3 class="font-semibold text-gray-800">{{ $selectedWarehouse->name }}</h3>
                <p class="text-xs text-gray-400">Code: <span class="font-mono">{{ $selectedWarehouse->code }}</span>@if($selectedWarehouse->city) · {{ $selectedWarehouse->city }}@endif</p>
            </div>
            <button type="submit" class="bg-indigo-600 text-white px-5 py-2 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition">
                Save All Changes
            </button>
        </div>

        <table class="w-full">
            <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider">
                <tr>
                    <th class="px-6 py-3 text-left">Product</th>
                    <th class="px-6 py-3 text-left">SKU</th>
                    <th class="px-6 py-3 text-center">Total Stock</th>
                    <th class="px-6 py-3 text-center">Warehouse Stock</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($products as $product)
                @php
                    $ws = $product->warehouseStock->first();
                    $wsQty = $ws ? $ws->stock : 0;
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3">
                        <div class="flex items-center gap-3">
                            @if($product->image)
                            <img src="{{ Storage::url($product->image) }}" class="w-8 h-8 rounded-lg object-cover flex-shrink-0">
                            @else
                            <div class="w-8 h-8 bg-gray-100 rounded-lg flex-shrink-0"></div>
                            @endif
                            <div>
                                <p class="text-sm font-medium text-gray-800">{{ $product->name }}</p>
                                <p class="text-xs text-gray-400">{{ $product->category->name }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-3 text-xs font-mono text-gray-500">{{ $product->sku ?? '—' }}</td>
                    <td class="px-6 py-3 text-center">
                        <span class="{{ $product->stock <= 5 ? 'text-red-600' : 'text-gray-700' }} font-semibold text-sm">{{ $product->stock }}</span>
                    </td>
                    <td class="px-6 py-3 text-center">
                        <input type="number" name="stocks[{{ $product->id }}]" value="{{ $wsQty }}" min="0"
                            class="w-24 border border-gray-200 rounded-lg px-3 py-1.5 text-sm text-center focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-6 py-12 text-center text-gray-400 text-sm">No products found.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($products->hasPages())<div class="px-6 py-4 border-t border-gray-100">{{ $products->links() }}</div>@endif
    </div>
</form>
@else
<div class="bg-white rounded-2xl shadow-sm p-16 text-center">
    <svg class="w-16 h-16 mx-auto text-gray-200 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
    <p class="text-gray-500">Select a warehouse above to view and edit its stock levels.</p>
    @if($warehouses->isEmpty())
    <a href="{{ route('admin.warehouses.create') }}" class="mt-4 inline-block bg-indigo-600 text-white px-5 py-2 rounded-xl text-sm font-semibold hover:bg-indigo-700">Create First Warehouse</a>
    @endif
</div>
@endif
@endsection
