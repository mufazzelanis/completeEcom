@extends('layouts.app')
@section('title', 'Home')

@section('content')

<!-- Hero Banner -->
<div class="bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-500 text-white">
    <div class="max-w-7xl mx-auto px-4 py-20 flex flex-col md:flex-row items-center justify-between">
        <div class="md:w-1/2 mb-8 md:mb-0">
            <p class="text-indigo-200 font-medium mb-2">New Arrivals 2026</p>
            <h1 class="text-4xl md:text-6xl font-bold leading-tight mb-6">
                Shop the <span class="text-yellow-300">Latest</span><br>Trends Today
            </h1>
            <p class="text-indigo-100 text-lg mb-8">Discover thousands of products at unbeatable prices. Free shipping on orders over ৳2000.</p>
            <div class="flex flex-wrap gap-4">
                <a href="{{ route('shop.index') }}" class="bg-white text-indigo-600 px-8 py-3 rounded-xl font-semibold hover:bg-indigo-50 transition shadow-lg">
                    Shop Now
                </a>
                <a href="{{ route('shop.index') }}?featured=1" class="border-2 border-white text-white px-8 py-3 rounded-xl font-semibold hover:bg-white hover:text-indigo-600 transition">
                    View Deals
                </a>
            </div>
        </div>
        <div class="md:w-1/2 flex justify-center">
            <div class="grid grid-cols-2 gap-4 max-w-sm">
                <div class="bg-white/20 rounded-2xl p-6 backdrop-blur-sm text-center">
                    <div class="text-3xl font-bold">12K+</div>
                    <div class="text-sm text-indigo-100">Products</div>
                </div>
                <div class="bg-white/20 rounded-2xl p-6 backdrop-blur-sm text-center">
                    <div class="text-3xl font-bold">50K+</div>
                    <div class="text-sm text-indigo-100">Customers</div>
                </div>
                <div class="bg-white/20 rounded-2xl p-6 backdrop-blur-sm text-center">
                    <div class="text-3xl font-bold">100+</div>
                    <div class="text-sm text-indigo-100">Brands</div>
                </div>
                <div class="bg-white/20 rounded-2xl p-6 backdrop-blur-sm text-center">
                    <div class="text-3xl font-bold">4.9★</div>
                    <div class="text-sm text-indigo-100">Rating</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Features -->
<div class="bg-white border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            @foreach([
                ['icon' => 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4', 'title' => 'Free Shipping', 'desc' => 'On orders over ৳2000'],
                ['icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', 'title' => 'Secure Payment', 'desc' => '100% secure payments'],
                ['icon' => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15', 'title' => 'Easy Returns', 'desc' => '7-day return policy'],
                ['icon' => 'M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z', 'title' => '24/7 Support', 'desc' => 'Dedicated support team'],
            ] as $feature)
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $feature['icon'] }}"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-800 text-sm">{{ $feature['title'] }}</p>
                        <p class="text-gray-500 text-xs">{{ $feature['desc'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<!-- Categories -->
<div class="max-w-7xl mx-auto px-4 py-12">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Shop by Category</h2>
            <p class="text-gray-500 text-sm mt-1">Find what you're looking for</p>
        </div>
        <a href="{{ route('shop.index') }}" class="text-indigo-600 hover:text-indigo-700 font-medium text-sm">View All →</a>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        @foreach($categories as $category)
            <a href="{{ route('shop.category', $category->slug) }}"
                class="group bg-white rounded-2xl p-6 text-center shadow-sm hover:shadow-md transition-all hover:-translate-y-1">
                <div class="w-12 h-12 bg-gradient-to-br from-indigo-400 to-purple-500 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
                    <span class="text-white font-bold text-lg">{{ strtoupper(substr($category->name, 0, 1)) }}</span>
                </div>
                <p class="font-semibold text-gray-800 text-sm">{{ $category->name }}</p>
                <p class="text-gray-400 text-xs mt-1">{{ $category->products_count }} items</p>
            </a>
        @endforeach
    </div>
</div>

<!-- Featured Products -->
@if($featuredProducts->isNotEmpty())
<div class="bg-gray-50 py-12">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Featured Products</h2>
                <p class="text-gray-500 text-sm mt-1">Handpicked just for you</p>
            </div>
            <a href="{{ route('shop.index') }}" class="text-indigo-600 hover:text-indigo-700 font-medium text-sm">View All →</a>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            @foreach($featuredProducts as $product)
                @include('partials.product-card', ['product' => $product])
            @endforeach
        </div>
    </div>
</div>
@endif

<!-- New Arrivals -->
<div class="max-w-7xl mx-auto px-4 py-12">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">New Arrivals</h2>
            <p class="text-gray-500 text-sm mt-1">Just in – fresh finds</p>
        </div>
        <a href="{{ route('shop.index') }}" class="text-indigo-600 hover:text-indigo-700 font-medium text-sm">View All →</a>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        @foreach($newArrivals as $product)
            @include('partials.product-card', ['product' => $product])
        @endforeach
    </div>
</div>

<!-- Promo Banner -->
<div class="max-w-7xl mx-auto px-4 pb-12">
    <div class="bg-gradient-to-r from-orange-500 to-pink-600 rounded-3xl p-8 md:p-12 text-white text-center">
        <p class="text-orange-100 font-medium mb-2">Limited Time Offer</p>
        <h3 class="text-3xl md:text-4xl font-bold mb-4">Get 10% off your first order</h3>
        <p class="text-orange-100 mb-6">Use code <span class="font-bold bg-white/20 px-3 py-1 rounded-lg">SAVE10</span> at checkout</p>
        <a href="{{ route('shop.index') }}" class="bg-white text-orange-600 px-8 py-3 rounded-xl font-semibold hover:bg-orange-50 transition inline-block">
            Shop Now
        </a>
    </div>
</div>

@endsection
