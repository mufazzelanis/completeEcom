@extends('layouts.admin')
@section('title', 'Products')

@section('content')
<div class="mb-6 bg-white rounded-2xl shadow-sm p-4">
    <form action="{{ route('admin.products.index') }}" method="GET" id="admin-product-filter">
        <!-- Row 1: Search + primary filters -->
        <div class="flex flex-wrap items-center gap-2 mb-3">
            <!-- Search with auto-suggest -->
            <div class="relative" x-data="{
                query: '{{ addslashes(request('search', '')) }}',
                results: [],
                open: false,
                async fetchSuggestions() {
                    if (this.query.length < 2) { this.open = false; return; }
                    const res = await fetch('{{ route('admin.search.suggest') }}?q=' + encodeURIComponent(this.query));
                    const data = await res.json();
                    this.results = data.products;
                    this.open = this.results.length > 0;
                }
            }" @click.outside="open = false">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" name="search"
                        x-model="query"
                        @input.debounce.300ms="fetchSuggestions()"
                        @focus="query.length > 1 && fetchSuggestions()"
                        @keydown.escape="open = false"
                        placeholder="Search name, SKU, barcode..."
                        class="border border-gray-200 rounded-xl pl-9 pr-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 w-64"
                        autocomplete="off">
                </div>
                <div x-show="open" x-cloak
                     class="absolute top-full left-0 bg-white shadow-xl rounded-xl border border-gray-100 z-50 mt-1 w-80 overflow-hidden">
                    <template x-for="product in results" :key="product.url">
                        <a :href="product.url" @click="open = false"
                           class="flex items-center px-3 py-2.5 hover:bg-indigo-50 gap-3">
                            <div class="w-9 h-9 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0 flex items-center justify-center">
                                <img x-show="product.image" :src="product.image" class="w-full h-full object-cover">
                                <svg x-show="!product.image" class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800 truncate" x-text="product.name"></p>
                                <p class="text-xs text-gray-500" x-text="product.sku + ' · ' + product.price"></p>
                            </div>
                            <span :class="product.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                                  class="text-xs px-1.5 py-0.5 rounded-full flex-shrink-0"
                                  x-text="product.is_active ? 'Active' : 'Inactive'"></span>
                        </a>
                    </template>
                </div>
            </div>

            <select name="category" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @foreach($cat->children ?? [] as $child)
                        <option value="{{ $child->id }}" {{ request('category') == $child->id ? 'selected' : '' }}>&nbsp;&nbsp;↳ {{ $child->name }}</option>
                    @endforeach
                @endforeach
            </select>

            <select name="type" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">All Types</option>
                <option value="simple"   {{ request('type') === 'simple'   ? 'selected' : '' }}>Simple</option>
                <option value="variable" {{ request('type') === 'variable' ? 'selected' : '' }}>Variable</option>
                <option value="bundle"   {{ request('type') === 'bundle'   ? 'selected' : '' }}>Bundle</option>
                <option value="digital"  {{ request('type') === 'digital'  ? 'selected' : '' }}>Digital</option>
            </select>

            <select name="brand" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">All Brands</option>
                @foreach($brands as $brand)
                    <option value="{{ $brand->id }}" {{ request('brand') == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                @endforeach
            </select>

            <select name="status" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">Any Status</option>
                <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>

        <!-- Row 2: Secondary filters + sort + buttons -->
        <div class="flex flex-wrap items-center gap-2">
            <select name="stock_status" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">Any Stock</option>
                <option value="in"  {{ request('stock_status') === 'in'  ? 'selected' : '' }}>In Stock</option>
                <option value="low" {{ request('stock_status') === 'low' ? 'selected' : '' }}>Low Stock</option>
                <option value="out" {{ request('stock_status') === 'out' ? 'selected' : '' }}>Out of Stock</option>
            </select>

            <label class="flex items-center gap-1.5 border border-gray-200 rounded-xl px-3 py-2 cursor-pointer text-sm text-gray-700 hover:bg-gray-50 select-none">
                <input type="checkbox" name="on_sale" value="1" {{ request('on_sale') ? 'checked' : '' }} class="rounded text-indigo-600">
                On Sale
            </label>

            <label class="flex items-center gap-1.5 border border-gray-200 rounded-xl px-3 py-2 cursor-pointer text-sm text-gray-700 hover:bg-gray-50 select-none">
                <input type="checkbox" name="featured" value="1" {{ request('featured') ? 'checked' : '' }} class="rounded text-indigo-600">
                Featured
            </label>

            <select name="sort_by" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="latest"     {{ request('sort_by', 'latest') === 'latest'     ? 'selected' : '' }}>Newest First</option>
                <option value="oldest"     {{ request('sort_by') === 'oldest'     ? 'selected' : '' }}>Oldest First</option>
                <option value="name_asc"   {{ request('sort_by') === 'name_asc'   ? 'selected' : '' }}>Name A–Z</option>
                <option value="name_desc"  {{ request('sort_by') === 'name_desc'  ? 'selected' : '' }}>Name Z–A</option>
                <option value="price_asc"  {{ request('sort_by') === 'price_asc'  ? 'selected' : '' }}>Price Low–High</option>
                <option value="price_desc" {{ request('sort_by') === 'price_desc' ? 'selected' : '' }}>Price High–Low</option>
                <option value="stock_asc"  {{ request('sort_by') === 'stock_asc'  ? 'selected' : '' }}>Stock Low–High</option>
                <option value="stock_desc" {{ request('sort_by') === 'stock_desc' ? 'selected' : '' }}>Stock High–Low</option>
            </select>

            <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-gray-700 transition">
                Filter
            </button>
            @if(request()->hasAny(['search','category','type','brand','status','stock_status','on_sale','featured','sort_by']))
                <a href="{{ route('admin.products.index') }}" class="px-4 py-2 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50">Clear</a>
            @endif

            <div class="ml-auto">
                <a href="{{ route('admin.products.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-indigo-700 transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add Product
                </a>
            </div>
        </div>

        <!-- Active filter chips -->
        @php
            $adminActiveFilters = array_filter([
                request('search'), request('category'), request('type'), request('brand'),
                request('status'), request('stock_status'), request('on_sale'), request('featured'),
            ]);
        @endphp
        @if(count($adminActiveFilters) > 0)
        <div class="flex flex-wrap items-center gap-1.5 mt-3 pt-3 border-t border-gray-100">
            <span class="text-xs text-gray-400">Active filters:</span>
            @if(request('search'))<span class="inline-flex items-center gap-1 px-2 py-0.5 bg-indigo-50 text-indigo-700 rounded-full text-xs">"{{ request('search') }}"</span>@endif
            @if(request('status'))<span class="inline-flex items-center gap-1 px-2 py-0.5 bg-indigo-50 text-indigo-700 rounded-full text-xs capitalize">{{ request('status') }}</span>@endif
            @if(request('type'))<span class="inline-flex items-center gap-1 px-2 py-0.5 bg-purple-50 text-purple-700 rounded-full text-xs capitalize">{{ request('type') }}</span>@endif
            @if(request('stock_status'))<span class="inline-flex items-center gap-1 px-2 py-0.5 bg-yellow-50 text-yellow-700 rounded-full text-xs capitalize">{{ request('stock_status') }} stock</span>@endif
            @if(request('on_sale'))<span class="inline-flex items-center gap-1 px-2 py-0.5 bg-red-50 text-red-700 rounded-full text-xs">On Sale</span>@endif
            @if(request('featured'))<span class="inline-flex items-center gap-1 px-2 py-0.5 bg-yellow-50 text-yellow-700 rounded-full text-xs">Featured</span>@endif
            <span class="text-xs text-gray-400 ml-1">— {{ $products->total() }} result(s)</span>
        </div>
        @endif
    </form>
</div>

<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr class="text-xs text-gray-500 uppercase tracking-wider">
                <th class="px-6 py-3 text-left">Product</th>
                <th class="px-6 py-3 text-left">Category</th>
                <th class="px-6 py-3 text-right">Price</th>
                <th class="px-6 py-3 text-center">Stock</th>
                <th class="px-6 py-3 text-center">Status</th>
                <th class="px-6 py-3 text-center">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($products as $product)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-12 h-12 bg-gray-100 rounded-xl overflow-hidden flex-shrink-0">
                                @if($product->image)
                                    <img src="{{ Storage::url($product->image) }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-gray-300">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <p class="font-medium text-gray-800 text-sm truncate max-w-48">{{ $product->name }}</p>
                                <p class="text-xs text-gray-400">{{ $product->sku ?? 'No SKU' }}@if($product->barcode) · {{ $product->barcode }}@endif</p>
                                <div class="flex items-center gap-1 mt-0.5">
                                    <span class="text-xs px-1.5 py-0.5 rounded-full font-medium {{ $product->typeBadge() }}">{{ ucfirst($product->type ?? 'simple') }}</span>
                                    @if($product->brand)<span class="text-xs text-gray-400">{{ $product->brand->name }}</span>@endif
                                    @if($product->is_featured)<span class="text-xs bg-yellow-100 text-yellow-700 px-1.5 py-0.5 rounded-full">Featured</span>@endif
                                    @if($product->sale_price)<span class="text-xs bg-red-100 text-red-600 px-1.5 py-0.5 rounded-full">Sale</span>@endif
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        {{ $product->category->name }}
                        @if($product->subcategory)
                            <span class="text-gray-400 text-xs block">↳ {{ $product->subcategory->name }}</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right">
                        @if($product->sale_price)
                            <p class="font-semibold text-red-600 text-sm">৳{{ number_format($product->sale_price) }}</p>
                            <p class="text-xs text-gray-400 line-through">৳{{ number_format($product->price) }}</p>
                        @else
                            <p class="font-semibold text-gray-900 text-sm">৳{{ number_format($product->price) }}</p>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="{{ $product->stock === 0 ? 'text-red-600' : ($product->isLowStock() ? 'text-yellow-600' : 'text-gray-800') }} font-semibold text-sm">{{ $product->stock }}</span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="px-2 py-1 rounded-full text-xs font-medium {{ $product->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                            {{ $product->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <div class="flex items-center justify-center space-x-2">
                            <a href="{{ route('admin.products.edit', $product->id) }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Edit</a>
                            <form action="{{ route('admin.products.destroy', $product->id) }}" method="POST" onsubmit="return confirm('Delete this product?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-700 text-sm font-medium">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-400">No products found. <a href="{{ route('admin.products.create') }}" class="text-indigo-600">Add one</a>.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
        <span class="text-sm text-gray-500">{{ $products->total() }} product(s) total</span>
        {{ $products->links() }}
    </div>
</div>
@endsection
