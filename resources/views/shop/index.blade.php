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

    <div class="flex flex-col lg:flex-row gap-8">
        <!-- Sidebar Filters -->
        <aside class="lg:w-64 flex-shrink-0">
            <div class="bg-white rounded-2xl shadow-sm p-6 sticky top-24">
                <h3 class="font-bold text-gray-900 mb-4">Filters</h3>

                <form action="{{ route('shop.index') }}" method="GET">
                    <!-- Search -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search products..."
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <!-- Category -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                        <div class="space-y-2 max-h-48 overflow-y-auto">
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input type="radio" name="category" value="" {{ !request('category') ? 'checked' : '' }} class="text-indigo-600">
                                <span class="text-sm text-gray-600">All Categories</span>
                            </label>
                            @foreach($categories as $cat)
                                <label class="flex items-center space-x-2 cursor-pointer">
                                    <input type="radio" name="category" value="{{ $cat->slug }}"
                                        {{ request('category') === $cat->slug ? 'checked' : '' }} class="text-indigo-600">
                                    <span class="text-sm text-gray-600">{{ $cat->name }} ({{ $cat->products_count }})</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Price Range -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Price Range</label>
                        <div class="flex space-x-2">
                            <input type="number" name="min_price" value="{{ request('min_price') }}" placeholder="Min"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <input type="number" name="max_price" value="{{ request('max_price') }}" placeholder="Max"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>

                    <!-- Sort -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Sort By</label>
                        <select name="sort" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="latest" {{ request('sort') === 'latest' ? 'selected' : '' }}>Latest</option>
                            <option value="price_low" {{ request('sort') === 'price_low' ? 'selected' : '' }}>Price: Low to High</option>
                            <option value="price_high" {{ request('sort') === 'price_high' ? 'selected' : '' }}>Price: High to Low</option>
                            <option value="name" {{ request('sort') === 'name' ? 'selected' : '' }}>Name A-Z</option>
                        </select>
                    </div>

                    <button type="submit" class="w-full bg-indigo-600 text-white py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 transition">
                        Apply Filters
                    </button>
                    <a href="{{ route('shop.index') }}" class="block text-center text-sm text-gray-500 mt-2 hover:text-gray-700">Clear Filters</a>
                </form>
            </div>
        </aside>

        <!-- Products Grid -->
        <div class="flex-1">
            <div class="flex items-center justify-between mb-6">
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
                    <p class="text-gray-500 text-sm">Try adjusting your search or filter criteria</p>
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
