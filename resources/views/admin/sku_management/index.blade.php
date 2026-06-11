@extends('layouts.admin')
@section('title', 'SKU Management')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">SKU Management</h1>
        <p class="text-sm text-gray-500 mt-1">Bulk edit and generate SKUs for all products</p>
    </div>
    @if($noSkuCount > 0)
    <span class="bg-orange-100 text-orange-700 text-sm font-medium px-3 py-1.5 rounded-xl">
        {{ $noSkuCount }} products missing SKU
    </span>
    @endif
</div>

@if(session('success'))<div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">{{ session('success') }}</div>@endif

<div class="bg-white rounded-2xl shadow-sm p-4 mb-4">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or SKU…"
            class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 w-48">
        <select name="category" class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">All Categories</option>
            @foreach($categories as $cat)
            <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
            @endforeach
        </select>
        <select name="has_sku" class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">All Products</option>
            <option value="yes" {{ request('has_sku')==='yes' ? 'selected' : '' }}>Has SKU</option>
            <option value="no" {{ request('has_sku')==='no' ? 'selected' : '' }}>Missing SKU</option>
        </select>
        <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-xl text-sm">Filter</button>
        @if(request()->hasAny(['search','category','has_sku']))<a href="{{ route('admin.sku-management.index') }}" class="px-4 py-2 border border-gray-200 rounded-xl text-sm text-gray-600">Clear</a>@endif
    </form>
</div>

<form action="{{ route('admin.sku-management.update') }}" method="POST">
@csrf

<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <p class="text-sm text-gray-500">{{ $products->total() }} products</p>
        <button type="submit" class="bg-indigo-600 text-white px-5 py-2 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition">Save All SKUs</button>
    </div>

    <table class="w-full">
        <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider">
            <tr>
                <th class="px-6 py-3 text-left">Product</th>
                <th class="px-6 py-3 text-left">Category</th>
                <th class="px-6 py-3 text-center">Type</th>
                <th class="px-6 py-3 text-left">SKU</th>
                <th class="px-6 py-3 text-center">Auto-Generate</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($products as $product)
            <tr class="hover:bg-gray-50 transition {{ !$product->sku ? 'bg-orange-50' : '' }}">
                <td class="px-6 py-3">
                    <div class="flex items-center gap-3">
                        @if($product->image)
                        <img src="{{ Storage::url($product->image) }}" class="w-8 h-8 rounded-lg object-cover flex-shrink-0">
                        @else
                        <div class="w-8 h-8 bg-gray-100 rounded-lg flex-shrink-0"></div>
                        @endif
                        <p class="text-sm font-medium text-gray-800 max-w-52 truncate">{{ $product->name }}</p>
                    </div>
                </td>
                <td class="px-6 py-3 text-sm text-gray-500">{{ $product->category->name }}</td>
                <td class="px-6 py-3 text-center">
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $product->typeBadge() }}">{{ ucfirst($product->type ?? 'simple') }}</span>
                </td>
                <td class="px-6 py-3">
                    <input type="text" name="skus[{{ $product->id }}]" value="{{ old("skus.$product->id", $product->sku) }}"
                        placeholder="Enter SKU…"
                        class="w-full border {{ !$product->sku ? 'border-orange-200 bg-orange-50' : 'border-gray-200' }} rounded-xl px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </td>
                <td class="px-6 py-3 text-center">
                    @if(!$product->sku)
                    <form action="{{ route('admin.sku-management.generate') }}" method="POST">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        <button type="submit" class="text-xs bg-gray-100 text-gray-700 px-3 py-1.5 rounded-lg hover:bg-gray-200 transition">Auto</button>
                    </form>
                    @else
                    <span class="text-xs text-green-600">✓</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="px-6 py-12 text-center text-gray-400 text-sm">No products found.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if($products->hasPages())
    <div class="px-6 py-4 border-t border-gray-100">{{ $products->links() }}</div>
    @endif
</div>

</form>
@endsection
