@extends('layouts.admin')
@section('title', 'Sale Products')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Sale Products</h1>
        <p class="text-sm text-gray-500 mt-1">
            <span class="font-semibold text-green-600">{{ $onSaleCount }}</span> product(s) currently on sale
        </p>
    </div>
    <a href="{{ route('admin.products.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition">
        + Add Product
    </a>
</div>

@if(session('success'))
<div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">{{ session('error') }}</div>
@endif
@if($errors->any())
<div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">{{ $errors->first() }}</div>
@endif

{{-- Filters --}}
<div class="bg-white rounded-2xl shadow-sm p-4 mb-4">
    <form method="GET" class="flex flex-wrap gap-3 items-center">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Product name or SKU…"
            class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 w-56">
        <select name="sale_filter" class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">All Products</option>
            <option value="on_sale"    {{ request('sale_filter') === 'on_sale'    ? 'selected' : '' }}>On Sale</option>
            <option value="no_sale"    {{ request('sale_filter') === 'no_sale'    ? 'selected' : '' }}>No Sale Price</option>
        </select>
        <select name="category" class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">All Categories</option>
            @foreach($categories as $cat)
            <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm hover:bg-indigo-700 transition">Filter</button>
        @if(request()->hasAny(['search','sale_filter','category']))
        <a href="{{ route('admin.sale-products.index') }}" class="px-4 py-2 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50 transition">Clear</a>
        @endif
    </form>
</div>

<div class="bg-white rounded-2xl shadow-sm overflow-hidden" x-data="{ selected: [] }">
    {{-- Bulk actions bar --}}
    <div class="px-6 py-3 border-b border-gray-100 flex items-center justify-between bg-gray-50"
         x-show="selected.length > 0" x-cloak>
        <p class="text-sm text-gray-600"><span x-text="selected.length"></span> product(s) selected</p>
        <form action="{{ route('admin.sale-products.clear-all') }}" method="POST"
              @submit.prevent="if(confirm('Clear sale prices for selected products?')) { $el.submit(); }">
            @csrf
            <template x-for="id in selected" :key="id">
                <input type="hidden" name="ids[]" :value="id">
            </template>
            <button type="submit" class="text-sm text-red-600 hover:text-red-800 font-medium">
                Clear Sale Prices
            </button>
        </form>
    </div>

    <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr class="text-xs text-gray-500 uppercase tracking-wider">
                <th class="px-4 py-3 w-8">
                    <input type="checkbox" class="rounded"
                        @change="selected = $event.target.checked ? {{ $products->pluck('id') }} : []">
                </th>
                <th class="px-6 py-3 text-left">Product</th>
                <th class="px-6 py-3 text-right">Regular Price</th>
                <th class="px-6 py-3 text-right">Sale Price</th>
                <th class="px-6 py-3 text-center">Discount</th>
                <th class="px-6 py-3 text-center">Status</th>
                <th class="px-6 py-3 text-right">Update</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($products as $product)
            <tr class="hover:bg-gray-50/50 transition" x-data="{ editing: false, salePrice: '{{ $product->sale_price ?? '' }}' }">
                <td class="px-4 py-3">
                    <input type="checkbox" class="rounded" :value="{{ $product->id }}"
                        @change="$event.target.checked ? selected.push({{ $product->id }}) : selected.splice(selected.indexOf({{ $product->id }}),1)">
                </td>
                <td class="px-6 py-3">
                    <div class="flex items-center space-x-3">
                        @if($product->image)
                        <img src="{{ asset('storage/'.$product->image) }}" class="w-9 h-9 rounded-lg object-cover flex-shrink-0">
                        @else
                        <div class="w-9 h-9 bg-gray-100 rounded-lg flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        @endif
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $product->name }}</p>
                            <p class="text-xs text-gray-400">{{ $product->category->name ?? '—' }} · {{ $product->sku ?? 'no SKU' }}</p>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-3 text-right text-sm font-medium text-gray-800">৳{{ number_format($product->price, 2) }}</td>
                <td class="px-6 py-3 text-right">
                    @if($product->sale_price)
                    <span class="text-sm font-bold text-green-600">৳{{ number_format($product->sale_price, 2) }}</span>
                    @else
                    <span class="text-xs text-gray-300">—</span>
                    @endif
                </td>
                <td class="px-6 py-3 text-center">
                    @if($product->sale_price)
                    @php $disc = round((($product->price - $product->sale_price) / $product->price) * 100) @endphp
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                        -{{ $disc }}%
                    </span>
                    @else
                    <span class="text-xs text-gray-300">—</span>
                    @endif
                </td>
                <td class="px-6 py-3 text-center">
                    @if($product->sale_price)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700">On Sale</span>
                    @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-500">Regular</span>
                    @endif
                </td>
                <td class="px-6 py-3 text-right">
                    <div x-show="!editing" class="flex justify-end gap-2">
                        <button type="button" @click="editing = true"
                            class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">Set Sale</button>
                        @if($product->sale_price)
                        <form action="{{ route('admin.sale-products.update', $product) }}" method="POST" class="inline"
                              onsubmit="return confirm('Remove sale price?')">
                            @csrf @method('PATCH')
                            <input type="hidden" name="sale_price" value="">
                            <button type="submit" class="text-xs text-red-500 hover:text-red-700 font-medium">Remove</button>
                        </form>
                        @endif
                    </div>
                    <form x-show="editing" x-cloak
                          action="{{ route('admin.sale-products.update', $product) }}" method="POST"
                          class="flex items-center justify-end gap-2">
                        @csrf @method('PATCH')
                        <span class="text-xs text-gray-400">৳</span>
                        <input type="number" name="sale_price" x-model="salePrice" min="0" step="0.01"
                            placeholder="{{ number_format($product->price * 0.9, 2) }}"
                            class="w-28 border border-indigo-300 rounded-lg px-2 py-1 text-sm text-right focus:outline-none focus:ring-2 focus:ring-indigo-400">
                        <button type="submit" class="text-xs bg-indigo-600 text-white px-2.5 py-1 rounded-lg hover:bg-indigo-700">Save</button>
                        <button type="button" @click="editing = false; salePrice = '{{ $product->sale_price ?? '' }}'"
                            class="text-xs text-gray-400 hover:text-gray-600">✕</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="px-6 py-12 text-center text-gray-400 text-sm">No products found.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($products->hasPages())
    <div class="px-6 py-4 border-t border-gray-100">{{ $products->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
