@extends('layouts.app')
@php
    $siteName = setting('site_name', 'ShopVista');
    // Fallback title/description formulas for category & brand pages with no SEO fields
    // filled in — a bare "FOREIGN FOODS" title and the site-wide tagline as description
    // give Google nothing to rank or show. Admin-entered meta_title/description still win.
    $shopHeading = isset($category) ? Str::title(Str::lower($category->name)) : (isset($currentBrand) ? $currentBrand->name : 'Shop');
    $shopIntro   = isset($category) ? $category->description : (isset($currentBrand) ? $currentBrand->description : null);
    $shopIntroText = trim(Str::limit(preg_replace('/\s+/', ' ', strip_tags((string) $shopIntro)), 155, '…'));
    $shopFallbackDesc = isset($currentBrand)
        ? "Buy original {$currentBrand->name} products online in Bangladesh at {$siteName}. Best price, cash on delivery and fast delivery across Bangladesh."
        : "Shop {$shopHeading} online in Bangladesh at {$siteName}. Original products, best price, cash on delivery and fast delivery across Bangladesh.";
    $shopSeoTitle = isset($category)
        ? ($category->meta_title ?: "{$shopHeading} Price in Bangladesh – Buy Online | {$siteName}")
        : (isset($currentBrand) ? "{$currentBrand->name} Products Price in Bangladesh | {$siteName}" : 'Shop');
    $shopSeoDesc  = isset($category)
        ? ($category->meta_description ?: ($shopIntroText ?: $shopFallbackDesc))
        : (isset($currentBrand) ? ($shopIntroText ?: $shopFallbackDesc) : null);
    $shopSeoImage = isset($category) ? ($category->og_image ? Storage::url($category->og_image) : ($category->image ? Storage::url($category->image) : null))
        : (isset($currentBrand) && $currentBrand->logo ? Storage::url($currentBrand->logo) : null);
    $shopCanonical = isset($category) ? ($category->canonical_url ?: route('shop.category', $category))
        : (isset($currentBrand) ? route('shop.brand', $currentBrand) : route('shop.index'));
    // Paginated pages get their own canonical (Google's guidance: don't point page 2 at page 1).
    if (!isset($category) || !$category->canonical_url) {
        if (request()->integer('page') > 1 && (isset($category) || isset($currentBrand))) {
            $shopCanonical .= '?page=' . request()->integer('page');
        }
    }

    // Same robots_meta + override-checkbox pattern as products/show.blade.php —
    // only applies when viewing a single category page (the plain /shop listing
    // always stays index,follow).
    if (isset($category)) {
        [$catRobotsIndex, $catRobotsFollow] = array_pad(explode(',', $category->robots_meta ?: 'index,follow'), 2, null);
        $shopRobotsParts = [
            $category->noindex ? 'noindex' : (trim($catRobotsIndex ?? '') ?: 'index'),
            $category->nofollow ? 'nofollow' : (trim($catRobotsFollow ?? '') ?: 'follow'),
        ];
        if ($category->nosnippet) $shopRobotsParts[] = 'nosnippet';
        if ($category->noimageindex) $shopRobotsParts[] = 'noimageindex';
        $shopRobots = implode(', ', $shopRobotsParts);
    }
@endphp
@section('title', $shopSeoTitle)
@if($shopSeoDesc)@section('meta_description', $shopSeoDesc)@endif
@if(isset($category) && $category->meta_keywords)@section('meta_keywords', $category->meta_keywords)@endif
@section('canonical', $shopCanonical)
@if($shopSeoImage)@section('og_image', $shopSeoImage)@endif
@if(isset($shopRobots))@section('robots', $shopRobots)@endif

@push('meta')
@if(isset($category) || isset($currentBrand))
<script type="application/ld+json">
{!! json_encode([
    '@@context' => 'https://schema.org',
    '@type' => 'CollectionPage',
    'name' => $shopSeoTitle,
    'description' => $shopSeoDesc,
    'url' => $shopCanonical,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
@endif
@endpush

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex items-center space-x-2 text-sm text-gray-500 mb-6">
        <a href="{{ route('home') }}" class="hover:text-orange-700">Home</a>
        <span>/</span>
        @if(isset($category))
            <a href="{{ route('shop.index') }}" class="hover:text-orange-700">Shop</a>
            <span>/</span>
            <span class="text-gray-900 font-medium">{{ $category->name }}</span>
        @elseif(isset($currentBrand))
            <a href="{{ route('brands.index') }}" class="hover:text-orange-700">Brands</a>
            <span>/</span>
            <span class="text-gray-900 font-medium">{{ $currentBrand->name }}</span>
        @else
            <span class="text-gray-900 font-medium">Shop</span>
        @endif
    </div>

    {{-- Every listing page gets a real H1 (Google reads it as the page's topic), plus the
         admin-written category/brand description as indexable intro copy. --}}
    <div class="mb-6">
        <h1 class="text-2xl md:text-3xl font-bold text-gray-900">{{ $shopHeading }}</h1>
        @if($shopIntro)
            <div class="prose prose-sm prose-gray dark:prose-invert max-w-none text-gray-600 mt-2">{!! nl2br(e(strip_tags($shopIntro))) !!}</div>
        @endif
    </div>

    @php
        $activeFilters = collect([
            'search'   => request('search') ? 'Search: "'.request('search').'"' : null,
            'category' => request()->query('category') ? 'Category: '.request()->query('category') : null,
            'brand'    => request('brand') ? 'Brand: '.request('brand') : null,
            'tag'      => request('tag') ? 'Tag: '.request('tag') : null,
            'min_price'=> request('min_price') ? 'Min ৳'.request('min_price') : null,
            'max_price'=> request('max_price') ? 'Max ৳'.request('max_price') : null,
            'featured' => request('featured') ? 'Featured Only' : null,
            'in_stock' => request('in_stock') ? 'In Stock Only' : null,
            'on_sale'  => request('on_sale') ? 'On Sale Only' : null,
        ])->filter()->count();
    @endphp

    {{-- Mobile filter toggle --}}
    <div class="lg:hidden mb-4" x-data="{ open: false }">
        <button @click="open = !open" class="w-full bg-white border border-gray-200 rounded-xl px-4 py-3 flex items-center justify-between shadow-sm">
            <span class="flex items-center gap-2 font-semibold text-gray-800 text-sm">
                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                Filters
                @if($activeFilters > 0)
                    <span class="bg-orange-500 text-white text-xs px-2 py-0.5 rounded-full">{{ $activeFilters }}</span>
                @endif
            </span>
            <svg class="w-5 h-5 text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div x-show="open" x-collapse x-cloak class="mt-2">
            @include('shop._filters', ['activeFilters' => $activeFilters])
        </div>
    </div>

    <div class="flex flex-col lg:flex-row gap-6 lg:gap-8">
        {{-- Desktop sidebar --}}
        <aside class="hidden lg:block lg:w-64 flex-shrink-0">
            <div class="bg-white rounded-2xl shadow-sm p-5 lg:sticky lg:top-24">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-gray-900">Filters</h3>
                    @if($activeFilters > 0)
                        <a href="{{ route('shop.index') }}" class="text-xs text-red-500 hover:text-red-700 font-medium">Clear all ({{ $activeFilters }})</a>
                    @endif
                </div>
                @include('shop._filters', ['activeFilters' => $activeFilters])
            </div>
        </aside>

        <div class="flex-1 min-w-0">
            @if($activeFilters > 0)
            <div class="flex flex-wrap items-center gap-2 mb-4">
                <span class="text-xs text-gray-500 font-medium">Active:</span>
                @if(request('search'))
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-orange-50 text-orange-700 rounded-full text-xs font-medium">
                        "{{ request('search') }}"
                        <a href="{{ request()->fullUrlWithQuery(['search' => null, 'page' => null]) }}" class="hover:text-orange-900">&times;</a>
                    </span>
                @endif
                @if(request()->query('category'))
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-orange-50 text-orange-700 rounded-full text-xs font-medium">
                        {{ request()->query('category') }}
                        <a href="{{ request()->fullUrlWithQuery(['category' => null, 'page' => null]) }}" class="hover:text-orange-900">&times;</a>
                    </span>
                @endif
                @if(request('brand'))
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-orange-50 text-orange-700 rounded-full text-xs font-medium">
                        Brand: {{ request('brand') }}
                        <a href="{{ request()->fullUrlWithQuery(['brand' => null, 'page' => null]) }}" class="hover:text-orange-900">&times;</a>
                    </span>
                @endif
                @if(request('tag'))
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-orange-50 text-orange-700 rounded-full text-xs font-medium">
                        #{{ request('tag') }}
                        <a href="{{ request()->fullUrlWithQuery(['tag' => null, 'page' => null]) }}" class="hover:text-orange-900">&times;</a>
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

            @if($products->isEmpty())
                <div class="bg-white rounded-2xl shadow-sm p-8 md:p-16 text-center">
                    <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-700 mb-2">No products found</h3>
                    <p class="text-gray-500 text-sm mb-4">Try adjusting your search or filter criteria</p>
                    <a href="{{ route('shop.index') }}" class="text-orange-700 hover:text-orange-800 text-sm font-medium">Clear all filters</a>
                </div>
            @else
                <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-4">
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
