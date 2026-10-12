@extends('layouts.app')
@php
    $siteName = setting('site_name', 'ShopVista');
    // Fallback title/description formulas for category & brand pages with no SEO fields
    // filled in — a bare "FOREIGN FOODS" title and the site-wide tagline as description
    // give Google nothing to rank or show. Admin-entered meta_title/description still win.
    $shopHeading = isset($category) ? Str::title(Str::lower($category->name)) : (isset($currentBrand) ? $currentBrand->name : 'Shop');
    // Admin text that's only the page's own name (category description "Baby Store") or too
    // short to be a real description (a brand whose description was set to a category name)
    // is ignored — see meaningful_seo(). Real descriptions show as intro copy and feed the
    // meta description; otherwise the formulas below take over.
    $ownNames    = array_filter([$category->name ?? null, $category->parent->name ?? null, $currentBrand->name ?? null]);
    $shopIntro   = meaningful_seo(isset($category) ? $category->description : ($currentBrand->description ?? null), $ownNames, 25)
        ? (isset($category) ? $category->description : $currentBrand->description) : null;
    $shopIntroText = $shopIntro ? Str::limit(meaningful_seo($shopIntro, $ownNames), 155, '…') : null;
    $shopFallbackDesc = isset($currentBrand)
        ? "Buy original {$currentBrand->name} products online in Bangladesh at {$siteName}. Best price, cash on delivery and fast delivery across Bangladesh."
        : "Shop {$shopHeading} online in Bangladesh at {$siteName}. Original products, best price, cash on delivery and fast delivery across Bangladesh.";
    $shopSeoTitle = isset($category)
        ? (meaningful_seo($category->meta_title, $ownNames) ?? "{$shopHeading} Price in Bangladesh – Buy Online | {$siteName}")
        : (isset($currentBrand) ? "{$currentBrand->name} Products Price in Bangladesh | {$siteName}" : 'Shop');
    $shopSeoDesc  = isset($category)
        ? (meaningful_seo($category->meta_description, $ownNames, 50) ?? (mb_strlen((string) $shopIntroText) >= 50 ? $shopIntroText : $shopFallbackDesc))
        : (isset($currentBrand) ? (mb_strlen((string) $shopIntroText) >= 50 ? $shopIntroText : $shopFallbackDesc) : null);
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
        /* request()->query(...) everywhere, not the plain request(...) helper — on
           /brand/{brand} the route parameter is also literally named "brand", and
           request('brand') resolves THAT (the bound Brand model) ahead of the query
           string when there's no ?brand= in the URL. Concatenating a model into a
           string trips its __toString() (Eloquent's JSON dump), rendered straight into
           the page as 'Brand: {"id":1,"name":"AVEENO",...}' — exactly what got
           reported. ->query() only ever reads the actual query string, same fix
           'category' already had (it sits on the same kind of route, /shop/category/
           {category}, but was written safely from the start). */
        $activeFilters = collect([
            'search'   => request()->query('search') ? 'Search: "'.request()->query('search').'"' : null,
            'category' => request()->query('category') ? 'Category: '.request()->query('category') : null,
            'brand'    => request()->query('brand') ? 'Brand: '.request()->query('brand') : null,
            'tag'      => request()->query('tag') ? 'Tag: '.request()->query('tag') : null,
            'min_price'=> request()->query('min_price') ? 'Min ৳'.request()->query('min_price') : null,
            'max_price'=> request()->query('max_price') ? 'Max ৳'.request()->query('max_price') : null,
            'featured' => request()->query('featured') ? 'Featured Only' : null,
            'in_stock' => request()->query('in_stock') ? 'In Stock Only' : null,
            'on_sale'  => request()->query('on_sale') ? 'On Sale Only' : null,
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
                @if(request()->query('search'))
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-orange-50 text-orange-700 rounded-full text-xs font-medium">
                        "{{ request()->query('search') }}"
                        <a href="{{ request()->fullUrlWithQuery(['search' => null, 'page' => null]) }}" class="hover:text-orange-900">&times;</a>
                    </span>
                @endif
                @if(request()->query('category'))
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-orange-50 text-orange-700 rounded-full text-xs font-medium">
                        {{ request()->query('category') }}
                        <a href="{{ request()->fullUrlWithQuery(['category' => null, 'page' => null]) }}" class="hover:text-orange-900">&times;</a>
                    </span>
                @endif
                {{-- request()->query(), not request() — see the comment above $activeFilters;
                     this is the exact chip that was rendering the raw Brand model as JSON on
                     /brand/{brand} pages. --}}
                @if(request()->query('brand'))
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-orange-50 text-orange-700 rounded-full text-xs font-medium">
                        Brand: {{ request()->query('brand') }}
                        <a href="{{ request()->fullUrlWithQuery(['brand' => null, 'page' => null]) }}" class="hover:text-orange-900">&times;</a>
                    </span>
                @endif
                @if(request()->query('tag'))
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-orange-50 text-orange-700 rounded-full text-xs font-medium">
                        #{{ request()->query('tag') }}
                        <a href="{{ request()->fullUrlWithQuery(['tag' => null, 'page' => null]) }}" class="hover:text-orange-900">&times;</a>
                    </span>
                @endif
                @if(request()->query('on_sale'))
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-red-50 text-red-600 rounded-full text-xs font-medium">
                        On Sale
                        <a href="{{ request()->fullUrlWithQuery(['on_sale' => null, 'page' => null]) }}" class="hover:text-red-800">&times;</a>
                    </span>
                @endif
                @if(request()->query('in_stock'))
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-green-50 text-green-600 rounded-full text-xs font-medium">
                        In Stock
                        <a href="{{ request()->fullUrlWithQuery(['in_stock' => null, 'page' => null]) }}" class="hover:text-green-800">&times;</a>
                    </span>
                @endif
                @if(request()->query('featured'))
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
