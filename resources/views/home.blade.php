@extends('layouts.app')
@section('title', 'Home - ' . setting('site_name', 'ShopVista'))

@push('meta')
{{-- Organization + WebSite structured data — homepage-only signals for Google's
     knowledge panel and sitelinks search box, mirroring the per-product/category
     JSON-LD already used on products/show and shop/index. --}}
@php
    $orgSameAs = array_values(array_filter([
        setting('facebook_url'), setting('instagram_url'), setting('youtube_url'),
        setting('linkedin_url'), setting('twitter_url'),
    ]));
@endphp
<script type="application/ld+json">
{!! json_encode(array_filter([
    '@context' => 'https://schema.org',
    '@type' => 'Organization',
    'name' => setting('site_name', 'ShopVista'),
    'url' => route('home'),
    'logo' => setting_file_url('site_logo'),
    'sameAs' => $orgSameAs ?: null,
], fn ($v) => $v !== null && $v !== ''), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'WebSite',
    'name' => setting('site_name', 'ShopVista'),
    'url' => route('home'),
    'potentialAction' => [
        '@type' => 'SearchAction',
        'target' => route('shop.index') . '?search={search_term_string}',
        'query-input' => 'required name=search_term_string',
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
@endpush

@section('content')

{{-- ═══════════ HERO BANNER CAROUSEL ═══════════ --}}
{{-- The h-56 fixed mobile height (previous commit) made the hero bigger but forced
     object-cover to blow the image up ~2x to fill a box far taller than its native
     16:5 ratio, cropping/zooming it badly — exactly what was just reported. Back to
     one single aspect-[16/5] everywhere (zero crop, matches what admins are told to
     upload at) so the image is never distorted; "bigger" instead comes from going
     edge-to-edge on mobile only, which stays rounded (corners are still visible
     against the page background even flush to the screen edge) while keeping the
     exact same crop-free ratio as desktop. --}}
<div class="relative max-w-[1200px] mx-auto px-0 sm:px-4 pt-0 sm:pt-4">
    {{-- Soft ambient glow behind the hero card — purely decorative depth, clipped by the
         page's own overflow so it never creates a horizontal scrollbar. --}}
    <div class="pointer-events-none absolute -top-4 left-1/2 -translate-x-1/2 w-[90%] h-[80%] bg-gradient-to-r from-orange-300/30 via-pink-300/20 to-indigo-300/30 blur-3xl rounded-full"></div>

    <div class="relative" x-data="{
        current: 0,
        total: {{ max($banners->count(), 1) }},
        touchX: null,
        init() {
            @if($banners->count() > 1)
            setInterval(() => { this.current = (this.current + 1) % this.total }, 5000);
            @endif
        },
        onTouchStart(e) { this.touchX = e.changedTouches[0].clientX; },
        onTouchEnd(e) {
            if (this.touchX === null) return;
            const dx = e.changedTouches[0].clientX - this.touchX;
            this.touchX = null;
            if (Math.abs(dx) < 40) return;  // a tap/scroll, not a swipe
            this.current = dx < 0 ? (this.current + 1) % this.total : (this.current - 1 + this.total) % this.total;
        }
    }" @touchstart.passive="onTouchStart($event)" @touchend.passive="onTouchEnd($event)">
        {{-- One fixed 16:5 ratio at every breakpoint — matches the 1920×600 size admins
             are told to upload at (admin.banners.create/edit), so the image is always
             shown at its native crop, never stretched/zoomed to fill a mismatched box. --}}
        {{-- min-h is a floor, not a second ratio — 360px and up stays exactly 16:5; only
             below that (sub-iPhone-SE widths) does it stop shrinking, which would
             otherwise clip the subtitle/headline/button stack. --}}
        <div class="relative rounded-2xl overflow-hidden bg-gray-200 aspect-[16/5] min-h-[112px] sm:min-h-0 shadow-lg sm:shadow-xl shadow-orange-900/5">
            @if($banners->count() > 0)
                @foreach($banners as $i => $banner)
                <div x-show="current === {{ $i }}"
                     x-transition:enter="transition ease-[cubic-bezier(.16,1,.3,1)] duration-700" x-transition:enter-start="opacity-0 scale-105" x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-105"
                     class="absolute inset-0 overflow-hidden">
                    @if($banner->image)
                        <a href="{{ $banner->button_link ?: '#' }}" class="block w-full h-full">
                            {{-- Every slide is in the DOM at once (x-show just toggles visibility,
                                 not presence) — without hints, the browser fetches all of them
                                 immediately and the actual LCP image (slide 0, the only one visible
                                 on load) competes for bandwidth with slides nobody's looking at yet.
                                 The slow scale (Ken Burns) only plays while a slide is the active
                                 one, restarting fresh each time it comes back around. --}}
                            <img src="{{ Storage::url($banner->image) }}" alt="{{ $banner->title }}"
                                class="w-full h-full object-cover transition-transform duration-[6000ms] ease-linear"
                                :class="current === {{ $i }} ? 'scale-110' : 'scale-100'"
                                @if($i === 0) fetchpriority="high" @else loading="lazy" @endif>
                            <div class="absolute inset-0 bg-gradient-to-r from-black/65 via-black/25 to-transparent"></div>
                            <div class="absolute inset-0 flex items-center px-6 md:px-14">
                                <div class="animate-fade-in-up max-w-lg">
                                    @if($banner->subtitle)
                                        <span class="inline-block bg-white/15 backdrop-blur-sm border border-white/25 text-white/90 text-[11px] md:text-xs font-semibold uppercase tracking-wider px-3 py-1 rounded-full mb-3">{{ $banner->subtitle }}</span>
                                    @endif
                                    <h2 class="text-white text-2xl md:text-4xl font-extrabold mb-2 leading-tight [text-wrap:balance]">{{ $banner->title }}</h2>
                                    @if($banner->description)<p class="text-white/80 text-sm mb-4 hidden md:block max-w-md">{{ $banner->description }}</p>@endif
                                    @if($banner->button_text)
                                        <span class="btn-glow inline-flex items-center gap-1.5 bg-white text-gray-900 px-5 md:px-6 py-2 md:py-2.5 rounded-full text-sm font-bold hover:bg-gray-100 hover:scale-105 transition-all shadow-lg">
                                            {{ $banner->button_text }}
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </a>
                    @endif
                </div>
                @endforeach
            @else
                {{-- Default Hero --}}
                <div class="absolute inset-0 bg-gradient-to-r from-orange-500 via-red-500 to-pink-500 bg-[length:200%_200%] animate-[gradientPan_8s_ease_infinite]">
                    <div class="absolute inset-0 flex items-center px-6 md:px-14">
                        <div class="animate-fade-in-up">
                            <p class="text-white/80 text-sm font-medium mb-2">Welcome to {{ setting('site_name', 'ShopVista') }}</p>
                            <h2 class="text-white text-2xl md:text-5xl font-extrabold mb-3 leading-tight">Discover Amazing Deals</h2>
                            <p class="text-white/70 text-sm mb-5 hidden md:block">Shop thousands of products at unbeatable prices</p>
                            <a href="{{ route('shop.index') }}" class="btn-glow inline-block bg-white text-gray-900 px-6 md:px-8 py-2 md:py-2.5 rounded-full text-sm font-bold hover:bg-gray-100 hover:scale-105 transition-all shadow-lg">Shop Now</a>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Navigation Arrows --}}
            @if($banners->count() > 1)
            <button @click="current = (current - 1 + total) % total" class="absolute left-3 top-1/2 -translate-y-1/2 w-10 h-10 md:w-9 md:h-9 bg-white/10 hover:bg-white/25 text-white rounded-full flex items-center justify-center transition backdrop-blur-md ring-1 ring-white/30" aria-label="Previous">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </button>
            <button @click="current = (current + 1) % total" class="absolute right-3 top-1/2 -translate-y-1/2 w-10 h-10 md:w-9 md:h-9 bg-white/10 hover:bg-white/25 text-white rounded-full flex items-center justify-center transition backdrop-blur-md ring-1 ring-white/30" aria-label="Next">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>
            <div class="absolute bottom-3 left-1/2 -translate-x-1/2 flex gap-1.5">
                @foreach($banners as $i => $banner)
                <button @click="current = {{ $i }}" class="h-1.5 rounded-full transition-all duration-300" aria-label="Go to slide {{ $i + 1 }}"
                        :class="current === {{ $i }} ? 'bg-white w-7' : 'bg-white/50 w-1.5 hover:bg-white/75'"></button>
                @endforeach
            </div>
            @endif
        </div>
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
{{-- mx-3/rounded/shadow only below md — on mobile this floats as a distinct card on the
     gray page background instead of a flat, edge-to-edge white band butting up against a
     hard gray gap (what made the feed look "unfinished" rather than app-like); desktop's
     look is untouched (mx-0/rounded-none/shadow-none resets it back to full-bleed there). --}}
<div class="mx-3 md:mx-0 mt-3 md:mt-4 bg-white dark:bg-gray-900 rounded-2xl md:rounded-none shadow-sm md:shadow-none">
    <div class="max-w-[1200px] mx-auto px-4 py-3">
        @php
            $highlightGradients = ['from-orange-400 to-red-500', 'from-emerald-400 to-teal-500', 'from-indigo-400 to-blue-500', 'from-pink-400 to-fuchsia-500'];
        @endphp
        {{-- A single row of 4 on every screen size, not a 2x2 grid on mobile — with only 4
             short trust items that already all fit at once, a horizontal *scroller* would
             hide two of them behind a swipe, working against the whole point of a trust
             strip (reassure the customer instantly, not after an extra gesture). Mobile
             instead gets a more compact vertical icon-over-label tile (sub-text hidden —
             no room for it at 4-across on a phone); sm: and up keeps the original
             icon-beside-text layout with the sub-line. --}}
        <div class="grid grid-cols-4 gap-2 sm:gap-4 reveal-group">
            @foreach($highlights as $idx => $f)
                @if($f['link'])
                    <a href="{{ $f['link'] }}" @if($f['blank']) target="_blank" rel="noopener" @endif
                       class="group flex flex-col sm:flex-row items-center gap-1.5 sm:gap-3 text-center sm:text-left p-1.5 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800/60 sm:hover:-translate-y-0.5 transition-all duration-200">
                        {{-- No group-hover:scale here — a transform-based hover would fight the
                             continuous .icon-float animation over the same `transform` property
                             (they'd visibly stutter against each other). Shadow-only hover
                             feedback instead. --}}
                        <div class="icon-float icon-float-{{ $idx + 1 }} w-9 h-9 sm:w-11 sm:h-11 bg-gradient-to-br {{ $highlightGradients[$idx % 4] }} rounded-xl flex items-center justify-center flex-shrink-0 shadow-sm group-hover:shadow-md transition-shadow">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $f['icon'] }}"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="font-bold text-gray-800 dark:text-gray-100 text-[10px] sm:text-xs leading-tight">{{ $f['text'] }}</p>
                            <p class="hidden sm:block text-gray-500 dark:text-gray-400 text-[10px] truncate">{{ $f['sub'] }}</p>
                        </div>
                    </a>
                @else
                    <div class="flex flex-col sm:flex-row items-center gap-1.5 sm:gap-3 text-center sm:text-left p-1.5">
                        <div class="icon-float icon-float-{{ $idx + 1 }} w-9 h-9 sm:w-11 sm:h-11 bg-gradient-to-br {{ $highlightGradients[$idx % 4] }} rounded-xl flex items-center justify-center flex-shrink-0 shadow-sm">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $f['icon'] }}"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="font-bold text-gray-800 dark:text-gray-100 text-[10px] sm:text-xs leading-tight">{{ $f['text'] }}</p>
                            <p class="hidden sm:block text-gray-500 dark:text-gray-400 text-[10px] truncate">{{ $f['sub'] }}</p>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
</div>

{{-- ═══════════ FLASH SALE ═══════════ --}}
@if($flashSale && $flashSaleProducts->count() > 0)
<div class="mx-3 md:mx-0 mt-3 md:mt-4 bg-white dark:bg-gray-900 rounded-2xl md:rounded-none shadow-sm md:shadow-none" x-data="{
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
                    <span class="relative flex h-6 w-6 items-center justify-center">
                        <span class="absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-60 animate-ping"></span>
                        <svg class="relative w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M11.983 1.907a.75.75 0 00-1.292-.657l-8 9.5A.75.75 0 003.25 12H9v6.093a.75.75 0 001.292.657l8-9.5A.75.75 0 0018 8H12V1.907z" clip-rule="evenodd"/></svg>
                    </span>
                    <h2 class="text-lg md:text-xl font-extrabold text-gray-900 dark:text-white">Flash Sale</h2>
                </div>
                <div class="flex items-center gap-1.5">
                    <div class="bg-gradient-to-b from-red-500 to-red-600 text-white text-xs font-bold px-2 py-1 rounded-md min-w-[28px] text-center shadow-sm">
                        <span x-text="String(hours).padStart(2,'0')">00</span>
                    </div>
                    <span class="text-red-500 font-bold text-xs">:</span>
                    <div class="bg-gradient-to-b from-red-500 to-red-600 text-white text-xs font-bold px-2 py-1 rounded-md min-w-[28px] text-center shadow-sm">
                        <span x-text="String(minutes).padStart(2,'0')">00</span>
                    </div>
                    <span class="text-red-500 font-bold text-xs">:</span>
                    <div class="bg-gradient-to-b from-red-500 to-red-600 text-white text-xs font-bold px-2 py-1 rounded-md min-w-[28px] text-center shadow-sm">
                        <span x-text="String(seconds).padStart(2,'0')">00</span>
                    </div>
                </div>
            </div>
            <a href="{{ route('shop.index') }}?on_sale=1" class="group inline-flex items-center gap-1 bg-red-50 hover:bg-red-100 dark:bg-red-500/10 dark:hover:bg-red-500/20 text-red-600 dark:text-red-400 px-3.5 py-1.5 rounded-full font-bold text-xs transition">
                SHOP ALL
                <svg class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
            </a>
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
{{-- The gaps here (above the heading, under it, below the rings) were flagged
     twice — a softer gradient fill wasn't the fix, the gaps themselves were
     just too big on a card with this little content. Padding and the header's
     own margin are both cut back hard instead. --}}
<div class="mx-3 md:mx-0 mt-3 md:mt-4 relative overflow-hidden bg-gradient-to-b from-orange-50/70 via-white to-white dark:from-orange-500/5 dark:via-gray-900 dark:to-gray-900 rounded-2xl md:rounded-none shadow-sm md:shadow-none">
    <div class="pointer-events-none absolute -top-12 -left-12 w-40 h-40 bg-orange-400/10 blur-3xl rounded-full"></div>
    <div class="pointer-events-none absolute -bottom-12 -right-12 w-40 h-40 bg-red-400/10 blur-3xl rounded-full"></div>
    <div class="relative max-w-[1200px] mx-auto px-4 pt-3 pb-3">
        <x-storefront.section-header title="Categories" :view-all-url="route('categories.index')" :count="$categories->count()" spacing="mb-3" />
        {{-- Mobile: a swipeable horizontal row (fixed-width tiles, scroll-snap, no "More"
             button needed at all — every category is one swipe away) instead of a grid
             capped at 6 with a toggle to reveal the rest. This is the same pattern
             Daraz/AliExpress/Amazon's own apps use for a category rail on a phone, and it
             reads as smoother/more native than a static grid + button. sm: and up reverts
             to the original grid (there's already room to show everything at once there,
             so a scroller would just be wasted horizontal space on a wide screen). --}}
        <div class="relative">
            <div class="flex sm:grid gap-3 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 overflow-x-auto sm:overflow-visible snap-x snap-mandatory sm:snap-none scrollbar-hide pb-1 reveal-group">
                @foreach($categories as $category)
                    {{-- Circular "story ring" tile (Instagram/Daraz "Shop by Category" pattern)
                         instead of a square icon block — reads as noticeably more polished at
                         a smaller footprint, and the ring + press-scale make it unmistakably
                         tappable rather than just decorative. --}}
                    <a href="{{ route('shop.category', $category->slug) }}"
                       class="group w-14 flex-shrink-0 sm:w-auto sm:flex-shrink snap-start flex flex-col items-center gap-2 p-1.5 rounded-xl active:scale-95 transition-transform duration-150">
                        {{-- category-ring (app.blade.php <style>) is a continuously-spinning
                             conic-gradient border — smaller than before, too, so one more
                             tile fits in the same mobile viewport width. --}}
                        <div class="category-ring relative w-12 h-12 md:w-14 md:h-14 rounded-full p-[2.5px] shadow-sm group-hover:shadow-lg group-hover:shadow-orange-500/20 group-hover:scale-105 transition-all duration-300">
                            <div class="w-full h-full rounded-full bg-white dark:bg-gray-900 p-[3px]">
                                <div class="w-full h-full rounded-full overflow-hidden bg-gradient-to-br from-orange-50 to-orange-100 dark:from-gray-800 dark:to-gray-700 flex items-center justify-center">
                                    @if($category->image)
                                        <img src="{{ Storage::url($category->image) }}" alt="{{ $category->name }}" loading="lazy" decoding="async" class="w-full h-full object-cover">
                                    @else
                                        {{-- A generic tag icon (not two-letter initials) reads as an
                                             intentional, uniform icon set — initials look like a raw
                                             unstyled fallback the moment more than a couple of
                                             categories are missing a photo. --}}
                                        <svg class="w-6 h-6 md:w-7 md:h-7 text-orange-400 group-hover:animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <p class="text-[10px] md:text-xs font-semibold text-gray-700 dark:text-gray-200 text-center leading-tight group-hover:text-orange-600 transition line-clamp-2">{{ $category->name }}</p>
                    </a>
                @endforeach
            </div>
            {{-- Right-edge fade — hints there's more to swipe to without needing a "More"
                 button; invisible once everything already fits (nothing to scroll to). --}}
            <div class="sm:hidden pointer-events-none absolute top-0 right-0 bottom-1 w-10 bg-gradient-to-l from-white dark:from-gray-900 to-transparent"></div>
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
                <img src="{{ Storage::url($banner->image) }}" alt="{{ $banner->title }}" loading="lazy" decoding="async" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
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
    @php $sec = $entry['section']; $totalCount = $entry['totalCount']; @endphp
    <div class="relative overflow-hidden mx-3 md:mx-0 mt-3 md:mt-4 rounded-2xl md:rounded-none shadow-sm md:shadow-none {{ $sec->theme === 'sale' ? 'bg-gradient-to-r from-red-500 to-orange-500' : 'bg-white dark:bg-gray-900' }}" x-data="{ expanded: false }">
        {{-- Same quiet corner-glow treatment as the trust banner — breaks up the run of
             flat white section cards down the page without competing with the products. --}}
        @if($sec->theme !== 'sale')
        <div class="pointer-events-none absolute -top-16 -right-16 w-48 h-48 bg-orange-500/5 dark:bg-orange-500/10 blur-3xl rounded-full"></div>
        @endif
        <div class="relative max-w-[1200px] mx-auto px-4 py-6">
            <x-storefront.section-header :title="$sec->title" :subtitle="$sec->subtitle" :view-all-url="$sec->getViewAllUrl()"
                :view-all-label="$sec->getViewAllLabelText()" :theme="$sec->theme === 'sale' ? 'sale' : 'default'" :count="$totalCount" />
            {{-- Back to the familiar grid + "View More" button (swiping felt less natural
                 here than it did for Categories) — but newly-revealed cards now fade/slide
                 in instead of just popping into existence, which they never did before. --}}
            @php
                // Mobile is always a fixed 2-column grid (see getGridColsClass), so
                // capping the always-visible tier at 8 keeps mobile's first view to
                // 4 rows regardless of how many columns/products_limit the admin
                // picked for desktop — the rest still shows immediately at sm+.
                $mobileCap = min(8, $sec->product_limit);
            @endphp
            <div class="grid {{ $sec->getGridColsClass() }} gap-3 reveal-group">
                @foreach($entry['products'] as $i => $product)
                    @if($i < $mobileCap)
                        @include('partials.product-card', ['product' => $product])
                    @elseif($i < $sec->product_limit)
                        {{-- Within the admin's chosen limit, so always shown on desktop;
                             on mobile it waits behind "More" alongside the true overflow. --}}
                        <div x-show="expanded" x-cloak class="sm:!block"
                             x-transition:enter="transition ease-out duration-400" x-transition:enter-start="opacity-0 translate-y-3" x-transition:enter-end="opacity-100 translate-y-0">
                            @include('partials.product-card', ['product' => $product])
                        </div>
                    @else
                        <div x-show="expanded" x-cloak
                             x-transition:enter="transition ease-out duration-400" x-transition:enter-start="opacity-0 translate-y-3" x-transition:enter-end="opacity-100 translate-y-0">
                            @include('partials.product-card', ['product' => $product])
                        </div>
                    @endif
                @endforeach
            </div>
            {{-- Only rendered when there's actually more to reveal — clicking stays right
                 here on the homepage and shows the rest of this section's fetched batch,
                 no navigation away (unlike the small "View All" link above, which still
                 goes to the full /shop listing). When the only hidden tier is the
                 mobile row cap (nothing held back on desktop), the button itself is
                 mobile-only — there'd be nothing left for it to reveal at sm+. --}}
            @if($totalCount > $sec->product_limit)
            <div class="text-center mt-6" x-show="!expanded">
                <button type="button" @click="expanded = true"
                   class="group inline-flex items-center gap-2 {{ $sec->theme === 'sale' ? 'bg-white text-orange-600 hover:bg-gray-100' : 'bg-gradient-to-r from-orange-500 to-red-500 text-white hover:shadow-lg hover:shadow-orange-500/30' }} px-10 py-2.5 rounded-full font-bold text-sm transition-all duration-300 shadow-md hover:-translate-y-0.5">
                    {{ $sec->getViewAllLabelText() }}
                    <svg class="w-4 h-4 group-hover:translate-y-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                </button>
            </div>
            @elseif($sec->product_limit > $mobileCap)
            <div class="text-center mt-6 sm:hidden" x-show="!expanded">
                <button type="button" @click="expanded = true"
                   class="group inline-flex items-center gap-2 {{ $sec->theme === 'sale' ? 'bg-white text-orange-600 hover:bg-gray-100' : 'bg-gradient-to-r from-orange-500 to-red-500 text-white hover:shadow-lg hover:shadow-orange-500/30' }} px-10 py-2.5 rounded-full font-bold text-sm transition-all duration-300 shadow-md hover:-translate-y-0.5">
                    {{ $sec->getViewAllLabelText() }}
                    <svg class="w-4 h-4 group-hover:translate-y-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                </button>
            </div>
            @endif
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
<div class="mx-3 md:mx-0 mt-3 md:mt-4 py-8 bg-gradient-to-r from-indigo-50 via-white to-orange-50 overflow-hidden rounded-2xl md:rounded-none shadow-sm md:shadow-none">
    <div class="max-w-[1200px] mx-auto px-4 mb-6 text-center reveal">
        <h2 class="text-lg md:text-2xl font-extrabold bg-gradient-to-r from-pink-500 via-orange-500 to-indigo-500 bg-clip-text text-transparent inline-block">What Our Customers Say</h2>
        <p class="text-gray-500 dark:text-gray-500 text-xs md:text-sm mt-1">Real reviews from real, happy buyers</p>
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
                        {{-- dark:text-* re-asserting the original light-mode color on every one of
                             these — this card's own background is a fixed light pastel gradient
                             (never inverted for dark mode, see $reviewThemes above), so letting
                             the site-wide dark-mode retrofit lighten this text too would make it
                             nearly invisible against a background that never got darker. --}}
                        <p class="text-gray-700 dark:text-gray-700 text-sm leading-relaxed line-clamp-4 mb-4 min-h-[4.5rem] font-medium">&ldquo;{{ $review->comment }}&rdquo;</p>
                        <div class="flex items-center gap-3 pt-3 border-t border-white/60">
                            <div class="w-9 h-9 bg-gradient-to-br {{ $theme['avatar'] }} rounded-full flex items-center justify-center flex-shrink-0 shadow-sm">
                                <span class="text-white font-bold text-xs">{{ strtoupper(substr($review->user->name, 0, 1)) }}</span>
                            </div>
                            <div class="min-w-0">
                                <p class="font-semibold text-gray-800 dark:text-gray-800 text-sm truncate">{{ $review->user->name }}</p>
                                @if($review->product)
                                    <p class="text-gray-500 dark:text-gray-500 text-[11px] truncate">on <a href="{{ route('products.show', $review->product->slug) }}" class="hover:text-orange-600 hover:underline transition">{{ $review->product->name }}</a></p>
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
<div class="mx-3 md:mx-0 mt-3 md:mt-4 bg-white dark:bg-gray-900 rounded-2xl md:rounded-none shadow-sm md:shadow-none">
    <div class="max-w-[1200px] mx-auto px-4 py-6">
        <x-storefront.section-header title="Top Brands" :view-all-url="route('brands.index')" />
        <div class="carousel-container flex gap-3 overflow-x-auto scrollbar-hide pb-2 scroll-smooth reveal-group">
            @foreach($brands as $brand)
                <a href="{{ route('shop.index') }}?brand={{ $brand->slug }}"
                   class="flex-shrink-0 w-32 h-20 bg-gray-50 border border-gray-100 rounded-xl flex items-center justify-center hover:border-orange-300 hover:shadow-md transition-all duration-200 group">
                    @if($brand->logo)
                        <img src="{{ Storage::url($brand->logo) }}" alt="{{ $brand->name }}" loading="lazy" decoding="async" class="max-w-[80%] max-h-[60%] object-contain group-hover:scale-105 transition">
                    @else
                        <span class="text-gray-500 font-bold text-sm group-hover:text-orange-700 transition">{{ $brand->name }}</span>
                    @endif
                </a>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- ═══════════ JUST FOR YOU (personalized feel — overflow from the New Arrivals section) ═══════════ --}}
@if($justForYou->isNotEmpty())
<div class="mx-3 md:mx-0 mt-3 md:mt-4 bg-white dark:bg-gray-900 rounded-2xl md:rounded-none shadow-sm md:shadow-none">
    <div class="max-w-[1200px] mx-auto px-4 py-6">
        <div class="flex items-center justify-center mb-5">
            <div class="h-px bg-gray-200 flex-1"></div>
            <h2 class="text-lg font-extrabold text-gray-900 px-6">{{ setting('just_for_you_title', 'Just For You') }}</h2>
            <div class="h-px bg-gray-200 flex-1"></div>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 reveal-group">
            @foreach($justForYou as $product)
                @include('partials.product-card', ['product' => $product])
            @endforeach
        </div>
        <div class="text-center mt-6">
            <a href="{{ route('shop.index') }}" class="group inline-flex items-center gap-2 bg-gradient-to-r from-orange-500 to-red-500 text-white px-10 py-2.5 rounded-full font-bold text-sm hover:shadow-lg hover:shadow-orange-500/30 hover:-translate-y-0.5 transition-all duration-300 shadow-md">
                {{ setting('just_for_you_button_text', 'View More Products') }}
                <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </a>
        </div>
    </div>
</div>
@endif

{{-- ═══════════ TRUST BANNER ═══════════ --}}
<div class="mt-4 mb-2">
    <div class="max-w-[1200px] mx-auto px-4">
        <div class="relative overflow-hidden bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 rounded-2xl p-6 md:p-8 reveal">
            <div class="pointer-events-none absolute -top-10 -right-10 w-56 h-56 bg-orange-500/10 blur-3xl rounded-full"></div>
            <div class="pointer-events-none absolute -bottom-10 -left-10 w-56 h-56 bg-indigo-500/10 blur-3xl rounded-full"></div>
            <div class="relative grid grid-cols-2 md:grid-cols-4 gap-6 text-center reveal-group">
                @foreach([
                    ['icon' => 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'num' => '100%', 'label' => 'Genuine Products'],
                    ['icon' => 'M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99', 'num' => '7 Days', 'label' => 'Easy Returns'],
                    ['icon' => 'M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z', 'num' => '24/7', 'label' => 'Customer Support'],
                    ['icon' => 'M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z', 'num' => 'Secure', 'label' => 'Payment System'],
                ] as $stat)
                <div class="group">
                    <div class="w-10 h-10 mx-auto mb-2 bg-white/10 rounded-xl flex items-center justify-center group-hover:bg-white/20 group-hover:scale-110 transition-all duration-300">
                        <svg class="w-5 h-5 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $stat['icon'] }}"/></svg>
                    </div>
                    <p class="text-white text-lg md:text-2xl font-extrabold">{{ $stat['num'] }}</p>
                    <p class="text-gray-400 text-xs mt-1">{{ $stat['label'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

@endsection
