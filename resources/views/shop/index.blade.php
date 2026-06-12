@extends('layouts.app')
@section('title', isset($category) ? $category->name : 'Shop')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <!-- Breadcrumb -->
    <div class="flex items-center space-x-2 text-sm text-gray-500 mb-6">
        <a href="{{ route('home') }}" class="hover:text-indigo-600">Home</a>
        <span>/</span>
        @if(isset($category))
            <a href="{{ route('shop.index') }}" class="hover:text-indigo-600">Shop</a>
            <span>/</span>
            <span class="text-gray-900 font-medium">{{ $category->name }}</span>
        @else
            <span class="text-gray-900 font-medium">Shop</span>
        @endif
    </div>

    @php
        $activeFilters = collect([
            'search'   => request('search') ? 'Search: "'.request('search').'"' : null,
            'category' => request('category') ? 'Category: '.request('category') : null,
            'brand'    => request('brand') ? 'Brand: '.request('brand') : null,
            'tag'      => request('tag') ? 'Tag: '.request('tag') : null,
            'min_price'=> request('min_price') ? 'Min ৳'.request('min_price') : null,
            'max_price'=> request('max_price') ? 'Max ৳'.request('max_price') : null,
            'featured' => request('featured') ? 'Featured Only' : null,
            'in_stock' => request('in_stock') ? 'In Stock Only' : null,
            'on_sale'  => request('on_sale') ? 'On Sale Only' : null,
        ])->filter()->count();
    @endphp

    <div class="flex flex-col lg:flex-row gap-8">
        <!-- Sidebar Filters -->
        <aside class="lg:w-64 flex-shrink-0">
            <div class="bg-white rounded-2xl shadow-sm p-5 sticky top-24">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-gray-900">Filters</h3>
                    @if($activeFilters > 0)
                        <a href="{{ route('shop.index') }}" class="text-xs text-red-500 hover:text-red-700 font-medium">Clear all ({{ $activeFilters }})</a>
                    @endif
                </div>

                <form action="{{ route('shop.index') }}" method="GET" id="filter-form">

                    <!-- Search -->
                    <div class="mb-5">
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Search</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search products..."
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <!-- Quick Filters -->
                    <div class="mb-5">
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Quick Filters</label>
                        <div class="space-y-2">
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input type="checkbox" name="in_stock" value="1" {{ request('in_stock') ? 'checked' : '' }}
                                    class="rounded text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm text-gray-700">In Stock Only</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input type="checkbox" name="on_sale" value="1" {{ request('on_sale') ? 'checked' : '' }}
                                    class="rounded text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm text-gray-700">On Sale</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input type="checkbox" name="featured" value="1" {{ request('featured') ? 'checked' : '' }}
                                    class="rounded text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm text-gray-700">Featured</span>
                            </label>
                        </div>
                    </div>

                    <!-- Category -->
                    <div class="mb-5" x-data="{ expanded: true }">
                        <button type="button" @click="expanded = !expanded"
                            class="flex items-center justify-between w-full text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                            <span>Category</span>
                            <svg class="w-3.5 h-3.5 transition-transform" :class="expanded ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="expanded" class="space-y-1.5 max-h-56 overflow-y-auto pr-1">
                            <label class="flex items-center gap-2 cursor-pointer py-0.5">
                                <input type="radio" name="category" value="" {{ !request('category') ? 'checked' : '' }} class="text-indigo-600">
                                <span class="text-sm text-gray-600">All Categories</span>
                            </label>
                            @foreach($categories as $cat)
                                <label class="flex items-center gap-2 cursor-pointer py-0.5">
                                    <input type="radio" name="category" value="{{ $cat->slug }}"
                                        {{ request('category') === $cat->slug ? 'checked' : '' }} class="text-indigo-600">
                                    <span class="text-sm text-gray-600">{{ $cat->name }} <span class="text-gray-400 text-xs">({{ $cat->products_count }})</span></span>
                                </label>
                                @foreach($cat->children as $child)
                                    <label class="flex items-center gap-2 cursor-pointer py-0.5 pl-4">
                                        <input type="radio" name="category" value="{{ $child->slug }}"
                                            {{ request('category') === $child->slug ? 'checked' : '' }} class="text-indigo-600">
                                        <span class="text-sm text-gray-500">{{ $child->name }} <span class="text-gray-400 text-xs">({{ $child->products_count }})</span></span>
                                    </label>
                                @endforeach
                            @endforeach
                        </div>
                    </div>

                    <!-- Brand -->
                    @if($brands->count() > 0)
                    <div class="mb-5" x-data="{ expanded: true }">
                        <button type="button" @click="expanded = !expanded"
                            class="flex items-center justify-between w-full text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                            <span>Brand</span>
                            <svg class="w-3.5 h-3.5 transition-transform" :class="expanded ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="expanded" class="space-y-1.5 max-h-40 overflow-y-auto pr-1">
                            <label class="flex items-center gap-2 cursor-pointer py-0.5">
                                <input type="radio" name="brand" value="" {{ !request('brand') ? 'checked' : '' }} class="text-indigo-600">
                                <span class="text-sm text-gray-600">All Brands</span>
                            </label>
                            @foreach($brands as $brand)
                                <label class="flex items-center gap-2 cursor-pointer py-0.5">
                                    <input type="radio" name="brand" value="{{ $brand->slug }}"
                                        {{ request('brand') === $brand->slug ? 'checked' : '' }} class="text-indigo-600">
                                    <span class="text-sm text-gray-600">{{ $brand->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Price Range -->
                    <div class="mb-5">
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Price Range (৳)</label>
                        <div class="flex items-center gap-2">
                            <input type="number" name="min_price" value="{{ request('min_price') }}" placeholder="Min"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <span class="text-gray-400 text-sm">–</span>
                            <input type="number" name="max_price" value="{{ request('max_price') }}" placeholder="Max"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>

                    <!-- Tags -->
                    @if($tags->count() > 0)
                    <div class="mb-5" x-data="{ expanded: false }">
                        <button type="button" @click="expanded = !expanded"
                            class="flex items-center justify-between w-full text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                            <span>Tags</span>
                            <svg class="w-3.5 h-3.5 transition-transform" :class="expanded ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="expanded" class="flex flex-wrap gap-1.5">
                            @foreach($tags->take(20) as $tag)
                                <label class="cursor-pointer">
                                    <input type="radio" name="tag" value="{{ $tag->slug }}"
                                        {{ request('tag') === $tag->slug ? 'checked' : '' }} class="sr-only peer">
                                    <span class="inline-block px-2.5 py-1 rounded-full text-xs border border-gray-200 text-gray-600 peer-checked:bg-indigo-600 peer-checked:text-white peer-checked:border-indigo-600 hover:border-indigo-400 transition cursor-pointer">{{ $tag->name }}</span>
                                </label>
                            @endforeach
                            <label class="cursor-pointer">
                                <input type="radio" name="tag" value="" {{ !request('tag') ? 'checked' : '' }} class="sr-only peer">
                                <span class="inline-block px-2.5 py-1 rounded-full text-xs border border-gray-200 text-gray-500 peer-checked:bg-gray-700 peer-checked:text-white peer-checked:border-gray-700 hover:border-gray-400 transition cursor-pointer">All</span>
                            </label>
                        </div>
                    </div>
                    @endif

                    <!-- Sort -->
                    <div class="mb-5">
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Sort By</label>
                        <select name="sort" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="latest"     {{ request('sort', 'latest') === 'latest'     ? 'selected' : '' }}>Latest</option>
                            <option value="popular"    {{ request('sort') === 'popular'    ? 'selected' : '' }}>Most Popular</option>
                            <option value="price_low"  {{ request('sort') === 'price_low'  ? 'selected' : '' }}>Price: Low to High</option>
                            <option value="price_high" {{ request('sort') === 'price_high' ? 'selected' : '' }}>Price: High to Low</option>
                            <option value="name"       {{ request('sort') === 'name'       ? 'selected' : '' }}>Name A–Z</option>
                        </select>
                    </div>

                    <button type="submit" class="w-full bg-indigo-600 text-white py-2.5 rounded-lg text-sm font-medium hover:bg-indigo-700 transition">
                        Apply Filters
                    </button>
                    @if($activeFilters > 0)
                        <a href="{{ route('shop.index') }}" class="block text-center text-sm text-gray-500 mt-2 hover:text-gray-700">Clear All Filters</a>
                    @endif
                </form>
            </div>
        </aside>

        <!-- Products Grid -->
        <div class="flex-1 min-w-0">
            <!-- Active filter chips -->
            @if($activeFilters > 0)
            <div class="flex flex-wrap items-center gap-2 mb-4">
                <span class="text-xs text-gray-500 font-medium">Active:</span>
                @if(request('search'))
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-indigo-50 text-indigo-700 rounded-full text-xs font-medium">
                        "{{ request('search') }}"
                        <a href="{{ request()->fullUrlWithQuery(['search' => null, 'page' => null]) }}" class="hover:text-indigo-900">&times;</a>
                    </span>
                @endif
                @if(request('category'))
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-indigo-50 text-indigo-700 rounded-full text-xs font-medium">
                        {{ request('category') }}
                        <a href="{{ request()->fullUrlWithQuery(['category' => null, 'page' => null]) }}" class="hover:text-indigo-900">&times;</a>
                    </span>
                @endif
                @if(request('brand'))
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-indigo-50 text-indigo-700 rounded-full text-xs font-medium">
                        Brand: {{ request('brand') }}
                        <a href="{{ request()->fullUrlWithQuery(['brand' => null, 'page' => null]) }}" class="hover:text-indigo-900">&times;</a>
                    </span>
                @endif
                @if(request('tag'))
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-indigo-50 text-indigo-700 rounded-full text-xs font-medium">
                        #{{ request('tag') }}
                        <a href="{{ request()->fullUrlWithQuery(['tag' => null, 'page' => null]) }}" class="hover:text-indigo-900">&times;</a>
                    </span>
                @endif
                @if(request('on_sale'))
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-red-50 text-red-600 rounded-full text-xs font-medium">
                        On Sale
                        <a href="{{ request()->fullUrlWithQuery(['on_sale' => null, 'page' => null]) }}" class="hover:text-red-800">&times;</a>
                    </span>
                @endif
                @if(request('in_stock'))
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-green-50 text-green-600 rounded-full text-xs font-medium">
                        In Stock
                        <a href="{{ request()->fullUrlWithQuery(['in_stock' => null, 'page' => null]) }}" class="hover:text-green-800">&times;</a>
                    </span>
                @endif
                @if(request('featured'))
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-yellow-50 text-yellow-700 rounded-full text-xs font-medium">
                        Featured
                        <a href="{{ request()->fullUrlWithQuery(['featured' => null, 'page' => null]) }}" class="hover:text-yellow-900">&times;</a>
                    </span>
                @endif
            </div>
            @endif

            <div class="flex items-center justify-between mb-4">
                <p class="text-gray-600 text-sm">
                    Showing <span class="font-semibold text-gray-900">{{ $products->firstItem() ?? 0 }}</span>–<span class="font-semibold text-gray-900">{{ $products->lastItem() ?? 0 }}</span>
                    of <span class="font-semibold text-gray-900">{{ $products->total() }}</span> results
                </p>
            </div>

            @if($products->isEmpty())
                <div class="bg-white rounded-2xl shadow-sm p-16 text-center">
                    <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-700 mb-2">No products found</h3>
                    <p class="text-gray-500 text-sm mb-4">Try adjusting your search or filter criteria</p>
                    <a href="{{ route('shop.index') }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Clear all filters</a>
                </div>
            @else
                <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">
                    @foreach($products as $product)
                        @include('partials.product-card', ['product' => $product])
                    @endforeach
                </div>

                <div class="mt-8">
                    {{ $products->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
