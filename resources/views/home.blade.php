@extends('layouts.app')
@section('title', 'Home - ' . setting('site_name', 'ShopVista'))

@section('content')

{{-- ═══════════ HERO BANNER CAROUSEL ═══════════ --}}
<div class="max-w-[1200px] mx-auto px-4 pt-4" x-data="{
    current: 0,
    total: {{ max($banners->count(), 1) }},
    init() {
        @if($banners->count() > 1)
        setInterval(() => { this.current = (this.current + 1) % this.total }, 5000);
        @endif
    }
}">
    <div class="relative rounded-xl overflow-hidden bg-gray-200 aspect-[2/1] md:aspect-[5/1]">
        @if($banners->count() > 0)
            @foreach($banners as $i => $banner)
            <div x-show="current === {{ $i }}" x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="absolute inset-0">
                @if($banner->image)
                    <a href="{{ $banner->button_link ?: '#' }}">
                        <img src="{{ Storage::url($banner->image) }}" alt="{{ $banner->title }}" class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-gradient-to-r from-black/50 to-transparent"></div>
                        <div class="absolute inset-0 flex items-center px-5 md:px-14">
                            <div>
                                @if($banner->subtitle)<p class="text-white/80 text-sm font-medium mb-2">{{ $banner->subtitle }}</p>@endif
                                <h2 class="text-white text-xl md:text-4xl font-extrabold mb-2 leading-tight">{{ $banner->title }}</h2>
                                @if($banner->description)<p class="text-white/70 text-sm mb-4 hidden md:block max-w-md">{{ $banner->description }}</p>@endif
                                @if($banner->button_text)
                                    <span class="inline-block bg-white text-gray-900 px-6 py-2 rounded-full text-sm font-bold hover:bg-gray-100 transition">{{ $banner->button_text }}</span>
                                @endif
                            </div>
                        </div>
                    </a>
                @endif
            </div>
            @endforeach
        @else
            {{-- Default Hero --}}
            <div class="absolute inset-0 bg-gradient-to-r from-orange-500 via-red-500 to-pink-500">
                <div class="absolute inset-0 flex items-center px-5 md:px-14">
                    <div>
                        <p class="text-white/80 text-sm font-medium mb-2">Welcome to {{ setting('site_name', 'ShopVista') }}</p>
                        <h2 class="text-white text-xl md:text-5xl font-extrabold mb-3 leading-tight">Discover Amazing Deals</h2>
                        <p class="text-white/70 text-sm mb-5 hidden md:block">Shop thousands of products at unbeatable prices</p>
                        <a href="{{ route('shop.index') }}" class="inline-block bg-white text-gray-900 px-8 py-2.5 rounded-full text-sm font-bold hover:bg-gray-100 transition shadow-lg">Shop Now</a>
                    </div>
                </div>
            </div>
        @endif

        {{-- Navigation Arrows --}}
        @if($banners->count() > 1)
        <button @click="current = (current - 1 + total) % total" class="absolute left-2 md:left-3 top-1/2 -translate-y-1/2 w-10 h-10 md:w-9 md:h-9 bg-black/30 hover:bg-black/50 text-white rounded-full flex items-center justify-center transition backdrop-blur-sm" aria-label="Previous">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <button @click="current = (current + 1) % total" class="absolute right-2 md:right-3 top-1/2 -translate-y-1/2 w-10 h-10 md:w-9 md:h-9 bg-black/30 hover:bg-black/50 text-white rounded-full flex items-center justify-center transition backdrop-blur-sm" aria-label="Next">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </button>
        <div class="absolute bottom-3 left-1/2 -translate-x-1/2 flex gap-1.5">
            @foreach($banners as $i => $banner)
            <button @click="current = {{ $i }}" class="w-2.5 h-2.5 rounded-full transition-all duration-300" aria-label="Go to slide {{ $i + 1 }}"
                    :class="current === {{ $i }} ? 'bg-white w-5' : 'bg-white/50'"></button>
            @endforeach
        </div>
        @endif
    </div>
</div>

{{-- ═══════════ SERVICE HIGHLIGHTS BAR ═══════════ --}}
@php
    $freeShippingEnabled = setting('free_shipping_enabled', '0') == '1';
    $freeShippingMin = (float) setting('free_shipping_min', '999');
    $activeGateways = \App\Models\PaymentMethod::where('is_active', true)->orderBy('sort_order')->pluck('name');
    $waLink = setting('whatsapp_link', '');
    $mLink = setting('messenger_link', '');
    $supportLink = $waLink ?: ($mLink ?: route('contact'));
    $supportExternal = (bool) ($waLink ?: $mLink);

    $highlights = [
        [
            'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
            'text' => 'Free Shipping',
            'sub'  => $freeShippingEnabled ? 'On orders over ৳'.number_format($freeShippingMin) : 'On all products',
            'link' => route('shop.index'),
            'blank' => false,
        ],
        [
            'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
            'text' => 'Secure Payment',
            'sub'  => $activeGateways->isNotEmpty() ? $activeGateways->implode(' • ') : '100% protected',
            'link' => null,
            'blank' => false,
        ],
        [
            'icon' => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15',
            'text' => 'Easy Returns',
            'sub'  => '7-day return policy',
            'link' => route('pages.show', 'return-policy'),
            'blank' => false,
        ],
        [
            'icon' => 'M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z',
            'text' => '24/7 Support',
            'sub'  => $supportExternal ? 'Chat with us now' : 'Dedicated support',
            'link' => $supportLink,
            'blank' => $supportExternal,
        ],
    ];
@endphp
<div class="bg-white mt-4">
    <div class="max-w-[1200px] mx-auto px-4 py-3">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach($highlights as $f)
                @if($f['link'])
                    <a href="{{ $f['link'] }}" @if($f['blank']) target="_blank" rel="noopener" @endif
                       class="flex items-center gap-3 -m-1 p-1 rounded-lg hover:bg-orange-50 transition">
                        <div class="w-10 h-10 bg-orange-50 rounded-lg flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $f['icon'] }}"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="font-bold text-gray-800 text-xs">{{ $f['text'] }}</p>
                            <p class="text-gray-400 text-[10px] truncate">{{ $f['sub'] }}</p>
                        </div>
                    </a>
                @else
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-orange-50 rounded-lg flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $f['icon'] }}"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="font-bold text-gray-800 text-xs">{{ $f['text'] }}</p>
                            <p class="text-gray-400 text-[10px] truncate">{{ $f['sub'] }}</p>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
</div>

{{-- ═══════════ FLASH SALE ═══════════ --}}
@if($flashSale && $flashSaleProducts->count() > 0)
<div class="bg-white mt-4" x-data="{
    hours: 0, minutes: 0, seconds: 0,
    end: '{{ $flashSale->ends_at->toIso8601String() }}',
    init() {
        this.update();
        setInterval(() => this.update(), 1000);
    },
    update() {
        let diff = Math.max(0, Math.floor((new Date(this.end) - new Date()) / 1000));
        this.hours = Math.floor(diff / 3600);
        this.minutes = Math.floor((diff % 3600) / 60);
        this.seconds = diff % 60;
    }
}">
    <div class="max-w-[1200px] mx-auto px-4 py-5">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2">
                    <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    <h2 class="text-lg md:text-xl font-extrabold text-gray-900">Flash Sale</h2>
                </div>
                <div class="flex items-center gap-1.5">
                    <div class="bg-gray-900 text-white text-xs font-bold px-2 py-1 rounded min-w-[28px] text-center">
                        <span x-text="String(hours).padStart(2,'0')">00</span>
                    </div>
                    <span class="text-gray-900 font-bold text-xs">:</span>
                    <div class="bg-gray-900 text-white text-xs font-bold px-2 py-1 rounded min-w-[28px] text-center">
                        <span x-text="String(minutes).padStart(2,'0')">00</span>
                    </div>
                    <span class="text-gray-900 font-bold text-xs">:</span>
                    <div class="bg-gray-900 text-white text-xs font-bold px-2 py-1 rounded min-w-[28px] text-center">
                        <span x-text="String(seconds).padStart(2,'0')">00</span>
                    </div>
                </div>
            </div>
            <a href="{{ route('shop.index') }}?on_sale=1" class="text-orange-500 hover:text-orange-700 font-bold text-sm transition">SHOP ALL →</a>
        </div>
        <div class="flex gap-3 overflow-x-auto scrollbar-hide pb-2">
            @foreach($flashSaleProducts->take(10) as $fsp)
                @include('partials.flash-sale-card', ['fsp' => $fsp])
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- ═══════════ CATEGORIES GRID ═══════════ --}}
@if($categories->count() > 0)
<div class="bg-white mt-4">
    <div class="max-w-[1200px] mx-auto px-4 py-6">
        <div class="flex items-center justify-between mb-5">
            <h2 class="text-lg font-extrabold text-gray-900">Categories</h2>
            <a href="{{ route('categories.index') }}" class="text-orange-500 hover:text-orange-700 font-bold text-sm transition">VIEW ALL →</a>
        </div>
        <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-3">
            @foreach($categories as $category)
                <a href="{{ route('shop.category', $category->slug) }}"
                   class="group flex flex-col items-center p-3 rounded-xl hover:bg-orange-50 transition-all duration-200">
                    <div class="w-14 h-14 md:w-16 md:h-16 bg-gradient-to-br from-orange-100 to-orange-50 rounded-2xl flex items-center justify-center mb-2 group-hover:from-orange-200 group-hover:to-orange-100 transition-all group-hover:scale-110 duration-300 shadow-sm overflow-hidden">
                        @if($category->image)
                            <img src="{{ Storage::url($category->image) }}" alt="{{ $category->name }}" class="w-full h-full object-cover">
                        @else
                            <span class="text-orange-500 font-extrabold text-xl">{{ strtoupper(substr($category->name, 0, 2)) }}</span>
                        @endif
                    </div>
                    <p class="text-[10px] md:text-xs font-semibold text-gray-700 text-center leading-tight group-hover:text-orange-600 transition line-clamp-2">{{ $category->name }}</p>
                </a>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- ═══════════ PROMO BANNERS ═══════════ --}}
@if($promoBanners->count() > 0)
<div class="max-w-[1200px] mx-auto px-4 mt-4">
    <div class="grid grid-cols-1 md:grid-cols-{{ min($promoBanners->count(), 4) }} gap-3">
        @foreach($promoBanners as $banner)
            @if($banner->image)
            <a href="{{ $banner->button_link ?: '#' }}" class="relative rounded-xl overflow-hidden group block aspect-[2/1]">
                <img src="{{ Storage::url($banner->image) }}" alt="{{ $banner->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                <div class="absolute bottom-0 left-0 p-4">
                    <h3 class="text-white font-bold text-sm md:text-base">{{ $banner->title }}</h3>
                    @if($banner->button_text)<span class="text-white/80 text-xs">{{ $banner->button_text }} →</span>@endif
                </div>
            </a>
            @endif
        @endforeach
    </div>
</div>
@endif

{{-- ═══════════ HOMEPAGE PRODUCT SECTIONS (admin-managed) ═══════════ --}}
@foreach($homeSections as $entry)
    @php $sec = $entry['section']; @endphp
    <div class="mt-4 {{ $sec->theme === 'sale' ? 'bg-gradient-to-r from-red-500 to-orange-500' : 'bg-white' }}">
        <div class="max-w-[1200px] mx-auto px-4 py-6">
            <div class="flex items-center justify-between mb-5">
                <div class="flex items-center gap-3">
                    @if($sec->theme === 'sale')
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                    @endif
                    <div>
                        <h2 class="text-lg font-extrabold {{ $sec->theme === 'sale' ? 'text-white' : 'text-gray-900' }}">{{ $sec->title }}</h2>
                        @if($sec->subtitle)<p class="{{ $sec->theme === 'sale' ? 'text-white/80' : 'text-gray-400' }} text-xs mt-0.5">{{ $sec->subtitle }}</p>@endif
                    </div>
                </div>
                <a href="{{ $sec->getViewAllUrl() }}"
                   class="{{ $sec->theme === 'sale' ? 'text-white/80 hover:text-white' : 'text-orange-500 hover:text-orange-700' }} font-bold text-sm transition">{{ $sec->getViewAllLabelText() }} →</a>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                @foreach($entry['products'] as $product)
                    @include('partials.product-card', ['product' => $product])
                @endforeach
            </div>
        </div>
    </div>
@endforeach

{{-- ═══════════ CUSTOMER REVIEWS (auto-scroll marquee) ═══════════ --}}
@if($testimonials->count() > 0)
@php
$reviewThemes = [
    ['bg' => 'from-pink-50 to-rose-100', 'ring' => 'ring-pink-200', 'avatar' => 'from-pink-500 to-rose-500', 'quote' => 'text-pink-300', 'star' => 'text-pink-500', 'bar' => 'from-pink-400 to-rose-500'],
    ['bg' => 'from-indigo-50 to-blue-100', 'ring' => 'ring-indigo-200', 'avatar' => 'from-indigo-500 to-blue-500', 'quote' => 'text-indigo-300', 'star' => 'text-indigo-500', 'bar' => 'from-indigo-400 to-blue-500'],
    ['bg' => 'from-emerald-50 to-teal-100', 'ring' => 'ring-emerald-200', 'avatar' => 'from-emerald-500 to-teal-500', 'quote' => 'text-emerald-300', 'star' => 'text-emerald-500', 'bar' => 'from-emerald-400 to-teal-500'],
    ['bg' => 'from-amber-50 to-orange-100', 'ring' => 'ring-amber-200', 'avatar' => 'from-amber-500 to-orange-500', 'quote' => 'text-amber-300', 'star' => 'text-amber-500', 'bar' => 'from-amber-400 to-orange-500'],
    ['bg' => 'from-purple-50 to-fuchsia-100', 'ring' => 'ring-purple-200', 'avatar' => 'from-purple-500 to-fuchsia-500', 'quote' => 'text-purple-300', 'star' => 'text-purple-500', 'bar' => 'from-purple-400 to-fuchsia-500'],
];
@endphp
<div class="mt-4 py-8 bg-gradient-to-r from-indigo-50 via-white to-orange-50 overflow-hidden">
    <div class="max-w-[1200px] mx-auto px-4 mb-6 text-center">
        <h2 class="text-lg md:text-2xl font-extrabold bg-gradient-to-r from-pink-500 via-orange-500 to-indigo-500 bg-clip-text text-transparent inline-block">What Our Customers Say</h2>
        <p class="text-gray-400 text-xs md:text-sm mt-1">Real reviews from real, happy buyers</p>
    </div>
    <div class="relative marquee-pause" style="-webkit-mask-image:linear-gradient(to right, transparent, black 5%, black 95%, transparent); mask-image:linear-gradient(to right, transparent, black 5%, black 95%, transparent);">
        <div class="flex gap-4 w-max animate-marquee">
            @foreach($testimonials->concat($testimonials) as $review)
                @php $theme = $reviewThemes[$loop->index % count($reviewThemes)]; @endphp
                <div class="flex-shrink-0 w-72 bg-gradient-to-br {{ $theme['bg'] }} rounded-2xl shadow-md ring-1 {{ $theme['ring'] }} overflow-hidden relative">
                    <div class="h-1.5 bg-gradient-to-r {{ $theme['bar'] }}"></div>
                    <div class="p-5 relative">
                        <svg class="w-9 h-9 {{ $theme['quote'] }} absolute top-3 right-4" fill="currentColor" viewBox="0 0 24 24"><path d="M7.17 6A5.17 5.17 0 002 11.17v6.66A2.17 2.17 0 004.17 20h4.66A2.17 2.17 0 0011 17.83v-4.66A2.17 2.17 0 008.83 11H5a3.17 3.17 0 013.17-3.17V6H7.17zm11 0A5.17 5.17 0 0013 11.17v6.66A2.17 2.17 0 0015.17 20h4.66A2.17 2.17 0 0022 17.83v-4.66A2.17 2.17 0 0019.83 11H16a3.17 3.17 0 013.17-3.17V6h-1z"/></svg>
                        <div class="flex mb-2">
                            @for($i = 1; $i <= 5; $i++)
                                <svg class="w-4 h-4 {{ $i <= $review->rating ? $theme['star'] : 'text-white/60' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.958a1 1 0 00.95.69h4.162c.969 0 1.371 1.24.588 1.81l-3.367 2.447a1 1 0 00-.363 1.118l1.287 3.957c.3.922-.755 1.688-1.539 1.118l-3.367-2.446a1 1 0 00-1.176 0l-3.367 2.446c-.783.57-1.838-.196-1.539-1.118l1.286-3.957a1 1 0 00-.363-1.118L2.373 9.385c-.783-.57-.38-1.81.588-1.81h4.162a1 1 0 00.95-.69l1.286-3.958z"/></svg>
                            @endfor
                        </div>
                        <p class="text-gray-700 text-sm leading-relaxed line-clamp-4 mb-4 min-h-[4.5rem] font-medium">&ldquo;{{ $review->comment }}&rdquo;</p>
                        <div class="flex items-center gap-3 pt-3 border-t border-white/60">
                            <div class="w-9 h-9 bg-gradient-to-br {{ $theme['avatar'] }} rounded-full flex items-center justify-center flex-shrink-0 shadow-sm">
                                <span class="text-white font-bold text-xs">{{ strtoupper(substr($review->user->name, 0, 1)) }}</span>
                            </div>
                            <div class="min-w-0">
                                <p class="font-semibold text-gray-800 text-sm truncate">{{ $review->user->name }}</p>
                                @if($review->product)
                                    <p class="text-gray-500 text-[11px] truncate">on <a href="{{ route('products.show', $review->product->slug) }}" class="hover:text-orange-600 hover:underline transition">{{ $review->product->name }}</a></p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- ═══════════ BRANDS ═══════════ --}}
@if($brands->count() > 0)
<div class="bg-white mt-4">
    <div class="max-w-[1200px] mx-auto px-4 py-6">
        <div class="flex items-center justify-between mb-5">
            <h2 class="text-lg font-extrabold text-gray-900">Top Brands</h2>
            <a href="{{ route('shop.index') }}" class="text-orange-500 hover:text-orange-700 font-bold text-sm transition">VIEW ALL →</a>
        </div>
        <div class="flex gap-3 overflow-x-auto scrollbar-hide pb-2">
            @foreach($brands as $brand)
                <a href="{{ route('shop.index') }}?brand={{ $brand->slug }}"
                   class="flex-shrink-0 w-32 h-20 bg-gray-50 border border-gray-100 rounded-xl flex items-center justify-center hover:border-orange-300 hover:shadow-md transition-all duration-200 group">
                    @if($brand->logo)
                        <img src="{{ Storage::url($brand->logo) }}" alt="{{ $brand->name }}" class="max-w-[80%] max-h-[60%] object-contain group-hover:scale-105 transition">
                    @else
                        <span class="text-gray-400 font-bold text-sm group-hover:text-orange-500 transition">{{ $brand->name }}</span>
                    @endif
                </a>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- ═══════════ JUST FOR YOU (personalized feel — overflow from the New Arrivals section) ═══════════ --}}
@if($justForYou->isNotEmpty())
<div class="bg-white mt-4">
    <div class="max-w-[1200px] mx-auto px-4 py-6">
        <div class="flex items-center justify-center mb-5">
            <div class="h-px bg-gray-200 flex-1"></div>
            <h2 class="text-lg font-extrabold text-gray-900 px-6">Just For You</h2>
            <div class="h-px bg-gray-200 flex-1"></div>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
            @foreach($justForYou as $product)
                @include('partials.product-card', ['product' => $product])
            @endforeach
        </div>
        <div class="text-center mt-6">
            <a href="{{ route('shop.index') }}" class="inline-block bg-orange-500 text-white px-10 py-2.5 rounded-lg font-bold text-sm hover:bg-orange-600 transition shadow-md">View More Products</a>
        </div>
    </div>
</div>
@endif

{{-- ═══════════ TRUST BANNER ═══════════ --}}
<div class="mt-4">
    <div class="max-w-[1200px] mx-auto px-4">
        <div class="bg-gradient-to-r from-gray-800 to-gray-900 rounded-xl p-6 md:p-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
                @foreach([
                    ['num' => '100%', 'label' => 'Genuine Products'],
                    ['num' => '7 Days', 'label' => 'Easy Returns'],
                    ['num' => '24/7', 'label' => 'Customer Support'],
                    ['num' => 'Secure', 'label' => 'Payment System'],
                ] as $stat)
                <div>
                    <p class="text-white text-lg md:text-2xl font-extrabold">{{ $stat['num'] }}</p>
                    <p class="text-gray-400 text-xs mt-1">{{ $stat['label'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

@endsection
