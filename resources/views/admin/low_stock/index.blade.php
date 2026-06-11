@extends('layouts.admin')
@section('title', 'Low Stock Alerts')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Low Stock Alerts</h1>
        <p class="text-sm text-gray-500 mt-1">Products at or below their low stock threshold</p>
    </div>
    <a href="{{ route('admin.stock-management.index') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-indigo-700 transition">Update Stock</a>
</div>

{{-- Stats --}}
<div class="grid grid-cols-3 gap-4 mb-6">
    <a href="{{ route('admin.low-stock.index', ['filter'=>'out']) }}"
        class="bg-white rounded-2xl shadow-sm p-5 flex items-center gap-4 hover:shadow-md transition {{ $filter === 'out' ? 'ring-2 ring-red-400' : '' }}">
        <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center flex-shrink-0">
            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </div>
        <div><p class="text-2xl font-bold text-gray-900">{{ $outCount }}</p><p class="text-sm text-gray-500">Out of Stock</p></div>
    </a>
    <a href="{{ route('admin.low-stock.index', ['filter'=>'low']) }}"
        class="bg-white rounded-2xl shadow-sm p-5 flex items-center gap-4 hover:shadow-md transition {{ $filter === 'low' ? 'ring-2 ring-yellow-400' : '' }}">
        <div class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center flex-shrink-0">
            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        </div>
        <div><p class="text-2xl font-bold text-gray-900">{{ $lowCount }}</p><p class="text-sm text-gray-500">Low Stock</p></div>
    </a>
    <a href="{{ route('admin.low-stock.index', ['filter'=>'all']) }}"
        class="bg-white rounded-2xl shadow-sm p-5 flex items-center gap-4 hover:shadow-md transition {{ $filter === 'all' ? 'ring-2 ring-indigo-400' : '' }}">
        <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center flex-shrink-0">
            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
        </div>
        <div><p class="text-2xl font-bold text-gray-900">{{ $outCount + $lowCount }}</p><p class="text-sm text-gray-500">Total Alerts</p></div>
    </a>
</div>

@if(session('success'))<div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">{{ session('success') }}</div>@endif

<div class="bg-white rounded-2xl shadow-sm p-4 mb-4">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <input type="hidden" name="filter" value="{{ $filter }}">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search…"
            class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 w-48">
        <select name="category" class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">All Categories</option>
            @foreach($categories as $cat)
            <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-xl text-sm">Filter</button>
    </form>
</div>

<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-100 text-xs text-gray-500 uppercase tracking-wider">
            <tr>
                <th class="px-6 py-3 text-left">Product</th>
                <th class="px-6 py-3 text-left">Category</th>
                <th class="px-6 py-3 text-center">Current Stock</th>
                <th class="px-6 py-3 text-center">Threshold</th>
                <th class="px-6 py-3 text-center">Status</th>
                <th class="px-6 py-3 text-right">Update Threshold</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($products as $product)
            <tr class="hover:bg-gray-50 transition {{ $product->stock == 0 ? 'bg-red-50' : '' }}">
                <td class="px-6 py-3">
                    <div class="flex items-center gap-3">
                        @if($product->image)
                        <img src="{{ Storage::url($product->image) }}" class="w-9 h-9 rounded-xl object-cover flex-shrink-0">
                        @else
                        <div class="w-9 h-9 bg-gray-100 rounded-xl flex-shrink-0"></div>
                        @endif
                        <div>
                            <p class="text-sm font-medium text-gray-800">{{ $product->name }}</p>
                            <p class="text-xs font-mono text-gray-400">{{ $product->sku ?? 'No SKU' }}</p>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-3 text-sm text-gray-500">{{ $product->category->name }}</td>
                <td class="px-6 py-3 text-center">
                    <span class="text-lg font-bold {{ $product->stock == 0 ? 'text-red-600' : 'text-yellow-600' }}">{{ $product->stock }}</span>
                </td>
                <td class="px-6 py-3 text-center text-sm text-gray-500">{{ $product->low_stock_threshold }}</td>
                <td class="px-6 py-3 text-center">
                    @if($product->stock == 0)
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">Out of Stock</span>
                    @else
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">Low Stock</span>
                    @endif
                </td>
                <td class="px-6 py-3 text-right">
                    <form action="{{ route('admin.low-stock.threshold', $product) }}" method="POST" class="flex items-center justify-end gap-2">
                        @csrf @method('PATCH')
                        <input type="number" name="threshold" value="{{ $product->low_stock_threshold }}" min="0"
                            class="w-16 border border-gray-200 rounded-lg px-2 py-1.5 text-sm text-center focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <button class="text-xs bg-gray-700 text-white px-3 py-1.5 rounded-lg hover:bg-gray-800">Set</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-6 py-16 text-center">
                <p class="text-green-600 font-medium">All products are well-stocked!</p>
            </td></tr>
            @endforelse
        </tbody>
    </table>
    @if($products->hasPages())<div class="px-6 py-4 border-t border-gray-100">{{ $products->links() }}</div>@endif
</div>
@endsection
