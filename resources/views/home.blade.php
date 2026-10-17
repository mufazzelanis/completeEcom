@extends('layouts.app')
{{-- Homepage title/H1 come from Settings → SEO → Default Meta Title (the layout already uses
     it as the site-wide fallback) — a hardcoded "Home - <site>" told Google nothing about
     what the store sells. --}}
@php $homeHeading = setting('seo_meta_title') ?: setting('site_name', 'ShopVista') . ' – Online Shopping in Bangladesh'; @endphp
@section('title', $homeHeading)

@if($banners->first()?->image)
@push('meta')
{{-- The first hero slide is the homepage's LCP image, but its <img> only appears ~240 KB into
     the HTML (after the header/menu) — preloading it from <head> starts the download right away.
     Same srcset/sizes as the <img>, so the browser fetches exactly the file it will use. --}}
@php $heroImage = $banners->first()->image; @endphp
<link rel="preload" as="image" fetchpriority="high"
      imagesrcset="{{ image_thumb($heroImage, 800) }} 800w, {{ image_thumb($heroImage, 1200) }} 1200w, {{ Storage::url($heroImage) }} 1920w"
      imagesizes="(max-width: 1200px) 100vw, 1200px">
@endpush
@endif

@push('meta')
{{-- Organization + WebSite structured data — homepage-only signals for Google's
     knowledge panel and sitelinks search box, mirroring the per-product/category
     JSON-LD already used on products/show and shop/index. --}}
@php
    $orgSameAs = array_values(array_filter([
        setting('facebook_url'), setting('instagram_url'), setting('youtube_url'),
        setting('linkedin_url'), setting('twitter_url'),
    ]));
    // Bangladesh-qualified description + @id — this is what actually helps Google tell
    // THIS "Mitavin" (an e-commerce store in Bangladesh) apart from an unrelated foreign
    // brand that happens to share the plain word "mitavin" (e.g. an Indian supplement
    // sold via 1mg) when it builds its knowledge panel / AI Overview for the query. A
    // bare "name": "Mitavin" with no address/description gives Google nothing to
    // disambiguate with; this does.
    $orgId = route('home') . '#organization';
    // The tagline alone ("Your one-stop shop...") says nothing that distinguishes this
    // Mitavin from any other business with the same name, so "Bangladesh" is appended
    // explicitly — that's the actual disambiguating token Google's AI Overview needs.
    $orgDescription = trim((setting('site_tagline') ?: 'Online shopping store') . ' — ' . setting('site_name', 'Mitavin') . ' is an e-commerce store based in Bangladesh.');
@endphp
<script type="application/ld+json">
{!! json_encode(array_filter([
    '@@context' => 'https://schema.org',
    '@type' => 'Organization',
    '@id' => $orgId,
    'name' => setting('site_name', 'ShopVista'),
    'description' => $orgDescription,
    'url' => route('home'),
    'logo' => setting_file_url('site_logo'),
    'sameAs' => $orgSameAs ?: null,
    'address' => setting('company_address') ? [
        '@type' => 'PostalAddress',
        'streetAddress' => setting('company_address'),
        'addressCountry' => 'BD',
    ] : null,
    'telephone' => setting('contact_phone') ?: setting('company_phone') ?: null,
], fn ($v) => $v !== null && $v !== ''), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
<script type="application/ld+json">
{!! json_encode([
    '@@context' => 'https://schema.org',
    '@type' => 'WebSite',
    'name' => setting('site_name', 'ShopVista'),
    'url' => route('home'),
    'publisher' => ['@id' => $orgId],
    'potentialAction' => [
        '@type' => 'SearchAction',
        'target' => route('shop.index') . '?search={search_term_string}',
        'query-input' => 'required name=search_term_string',
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
@endpush

@section('content')
{{-- The page's single H1 — visually the hero banner carries the message, so this stays
     screen-reader/crawler-only rather than adding a heading bar above the carousel. --}}
<h1 class="sr-only">{{ $homeHeading }}</h1>

{{-- ═══════════ HERO BANNER CAROUSEL ═══════════ --}}
{{-- aspect-[16/5] is load-bearing, not a style choice — admins are told to upload at
     1920×600, and a previous attempt to make mobile's hero taller via a fixed h-56
     forced object-cover to blow the image up ~2x to fill a box far taller than its
     native ratio, cropping/zooming it badly. That ratio has to stay fixed at every
     breakpoint, so "more compact on mobile" can only come from narrowing the box
     itself — aspect-ratio then shrinks the height to match automatically, with zero
     crop risk either way. px-3 (was px-0, edge-to-edge) does that: a visible inset
     from the screen edge, which incidentally also means the shadow is now visible on
     every side instead of just top/bottom, and reads as a floating card rather than a
     bar — the same treatment the mobile header capsule already uses. --}}
<div class="relative max-w-[1200px] mx-auto px-3 sm:px-4 pt-2 sm:pt-4">
    {{-- Soft ambient glow behind the hero card — purely decorative depth, clipped by the
         page's own overflow so it never creates a horizontal scrollbar. --}}
    <div class="pointer-events-none absolute -top-4 left-1/2 -translate-x-1/2 w-[90%] h-[80%] bg-gradient-to-r from-orange-300/30 via-pink-300/20 to-indigo-300/30 blur-3xl rounded-full"></div>

    <div class="relative" x-data="{
        current: 0,
        rotated: false, // true after the first auto-advance — see the slide text animation below
        total: {{ max($banners->count(), 1) }},
        touchX: null,
        init() {
            @if($banners->count() > 1)
            // Auto-advance starts on the visitor's first scroll/tap/keypress, not on page load:
            // nothing moves until someone is actually engaging with the page (WCAG 2.2.2 motion),
            // and a slide caught mid-crossfade by an automated audit no longer reads as
            // low-contrast text. Visitors who ask their OS for reduced motion get no autoplay.
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
            const evs = ['scroll', 'pointerdown', 'keydown', 'touchstart'];
            const start = () => {
                evs.forEach(ev => window.removeEventListener(ev, start));
                setInterval(() => { this.rotated = true; this.current = (this.current + 1) % this.total }, 5000);
            };
            evs.forEach(ev => window.addEventListener(ev, start, { passive: true, once: true }));
            @endif
        },
        onTouchStart(e) { this.touchX = e.changedTouches[0].clientX; },
        onTouchEnd(e) {
            if (this.touchX === null) return;
            const dx = e.changedTouches[0].clientX - this.touchX;
            this.touchX = null;
            if (Math.abs(dx) < 40) return;  // a tap/scroll, not a swipe
            this.rotated = true;
            this.current = dx < 0 ? (this.current + 1) % this.total : (this.current - 1 + this.total) % this.total;
        }
    }" @touchstart.passive="onTouchStart($event)" @touchend.passive="onTouchEnd($event)">
        {{-- One fixed 16:5 ratio at EVERY breakpoint, no exceptions — matches the 1920×600
             canvas banners are now server-side cropped to exactly (BannerController::
             storeBannerImage), so the box and the image are always the same ratio and
             object-cover never has anything to crop. There used to be a `min-h-[112px]`
             floor here meant to stop the text overlay clipping on the narrowest phones —
             but that floor kicked in for content-box widths under 360px, which is most
             phones (360/375/390 viewport width, minus the px-3 gutters, almost all land
             under that), silently un-matching the box from the image on exactly the
             devices most people actually use — reported as the hero image still looking
             cropped on mobile even after the server-side crop fix. Dropped entirely; the
             overlay's own spacing (tightened below) now carries the narrow-screen case
             instead of a competing height rule. --}}
        <div class="relative rounded-2xl overflow-hidden bg-gray-200 aspect-[16/5] shadow-lg sm:shadow-xl shadow-orange-900/5">
            @if($banners->count() > 0)
                @foreach($banners as $i => $banner)
                {{-- Slides after the first start hidden in the HTML itself: until Alpine boots,
                     x-show hasn't hidden them yet, so they sat visible in the viewport and their
                     lazy images downloaded immediately, competing with the LCP slide. --}}
                <div x-show="current === {{ $i }}" @if($i > 0) style="display: none" @endif
                     x-transition:enter="transition ease-[cubic-bezier(.16,1,.3,1)] duration-700" x-transition:enter-start="opacity-0 scale-105" x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-105"
                     class="absolute inset-0 overflow-hidden">
                    @if($banner->image)
                        @php
                            // Title is optional — an image that already carries its own headline
                            // shouldn't also get the page's text stamped on top of it. The dark
                            // scrim exists only to keep text readable, so it's skipped entirely
                            // for an image-only banner instead of needlessly darkening the photo.
                            $hasOverlayText = $banner->title || $banner->subtitle || $banner->description;
                            $btnBg = $banner->bg_color ?: '#ffffff';
                            $btnText = $banner->text_color ?: '#1f2937';
                        @endphp
                        <a href="{{ $banner->button_link ?: '#' }}" class="block w-full h-full">
                            {{-- Banner uploads are now server-side cropped to exactly 1920×600 at
                                 upload time (BannerController::storeBannerImage, for position=hero)
                                 instead of being left at whatever ratio the admin's original file
                                 was — that mismatch is what made object-cover crop unpredictably in
                                 the browser (reported: the photo's right edge getting sliced off).
                                 With the stored file already matching this box exactly, object-cover
                                 here never has anything to crop; it's just a safety net for any
                                 banner saved before this change, until it's re-uploaded. --}}
                            {{-- Every slide is in the DOM at once (x-show just toggles visibility,
                                 not presence) — without hints, the browser fetches all of them
                                 immediately and the actual LCP image (slide 0, the only one visible
                                 on load) competes for bandwidth with slides nobody's looking at yet.
                                 The slow scale (Ken Burns) only plays while a slide is the active
                                 one, restarting fresh each time it comes back around. --}}
                            <img src="{{ Storage::url($banner->image) }}"
                                srcset="{{ image_thumb($banner->image, 800) }} 800w, {{ image_thumb($banner->image, 1200) }} 1200w, {{ Storage::url($banner->image) }} 1920w"
                                sizes="(max-width: 1200px) 100vw, 1200px" width="1920" height="600"
                                {{-- With text overlaid, the title is already read out — repeating it as alt
                                     makes screen readers say it twice (PageSpeed "redundant alt"). --}}
                                alt="{{ $hasOverlayText ? '' : ($banner->title ?: $banner->subtitle ?: setting('site_name', 'Banner')) }}"
                                class="w-full h-full object-cover object-center transition-transform duration-[6000ms] ease-linear"
                                :class="current === {{ $i }} ? 'scale-110' : 'scale-100'"
                                @if($i === 0) fetchpriority="high" @else loading="lazy" @endif>
                            @if($hasOverlayText)
                            <div class="absolute inset-0 bg-gradient-to-r from-black/65 via-black/25 to-transparent"></div>
                            @endif
                            <div class="absolute inset-0 flex items-center px-6 md:px-14">
                                {{-- No fade-in on first paint: the first slide is the LCP area, and text caught mid-fade
                                     (semi-transparent) fails PageSpeed's contrast check on a slow run. Slides
                                     shown by the rotation still animate in. --}}
                                <div class="max-w-lg" :class="rotated && 'animate-fade-in-up'">
                                    {{-- Margins trimmed (mb-3→mb-1.5, mb-2→mb-1) now that the box
                                         can be as short as ~90px on the narrowest phones (no more
                                         min-h floor forcing extra height) — the content itself needs
                                         to fit that, not just avoid being visually cramped. --}}
                                    @if($banner->subtitle)
                                        <span class="inline-block bg-white/15 backdrop-blur-sm border border-white/25 text-white/90 text-[11px] md:text-xs font-semibold uppercase tracking-wider px-3 py-1 rounded-full mb-1.5 md:mb-3">{{ $banner->subtitle }}</span>
                                    @endif
                                    @if($banner->title)
                                        <h2 class="text-white text-xl md:text-4xl font-extrabold mb-1 md:mb-2 leading-tight [text-wrap:balance]">{{ $banner->title }}</h2>
                                    @endif
                                    @if($banner->description)<p class="text-white/80 text-sm mb-4 hidden md:block max-w-md">{{ $banner->description }}</p>@endif
                                    @if($banner->button_text)
                                        {{-- bg_color/text_color (Admin → Banners) actually drive this now — they
                                             were being collected and saved but never once read anywhere, so a
                                             custom color here silently did nothing. Smaller + an explicit color
                                             (default a solid white/charcoal, same as before, when unset) reads as
                                             a deliberate accent chip rather than a plain oversized white slab. --}}
                                        <span class="btn-glow inline-flex items-center gap-1 px-3.5 py-1.5 md:px-4 md:py-2 rounded-full text-xs md:text-sm font-bold shadow-lg hover:scale-105 hover:brightness-110 transition-all"
                                              style="background-color: {{ $btnBg }}; color: {{ readable_text_color($btnBg, $btnText) }};">
                                            {{ $banner->button_text }}
                                            <svg class="w-3.5 h-3.5 md:w-4 md:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
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

            {{-- Navigation Arrows — desktop only now (hidden md:flex, was unconditional).
                 Mobile already has real swipe support (@touchstart/@touchend above) plus the
                 dot indicators below, so a persistent arrow button added nothing there except
                 a circle sitting right in the content column's vertical center — on a short
                 16:5-ratio banner, that center line runs straight through wherever the
                 title/button text happens to land, so the button text and the arrow icon
                 were overlapping (reported: "Shop Now" reading as "op Now", its "Sh" covered
                 by the arrow). Desktop has no touch gestures, so it keeps the clickable arrows. --}}
            @if($banners->count() > 1)
            <button @click="current = (current - 1 + total) % total" class="hidden md:flex absolute left-3 top-1/2 -translate-y-1/2 w-9 h-9 bg-white/10 hover:bg-white/25 text-white rounded-full items-center justify-center transition backdrop-blur-md ring-1 ring-white/30" aria-label="Previous">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </button>
            <button @click="current = (current + 1) % total" class="hidden md:flex absolute right-3 top-1/2 -translate-y-1/2 w-9 h-9 bg-white/10 hover:bg-white/25 text-white rounded-full items-center justify-center transition backdrop-blur-md ring-1 ring-white/30" aria-label="Next">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>
            @endif
        </div>

        {{-- Dots now live below the card, Arogga-style, instead of floating on top of the
             image — an overlaid row risked covering a banner's own text/button at short
             aspect ratios (the same overlap class of bug the arrows just had), and it also
             reads cleaner against a plain background than against a photo. --}}
        @if($banners->count() > 1)
        <div class="flex items-center justify-center mt-1">
            @foreach($banners as $i => $banner)
            {{-- The dot stays small; the button around it is a 24px-tall tap target, with the
                 padding keeping 24px between dot centers (PageSpeed "touch targets too small"). --}}
            <button @click="rotated = true; current = {{ $i }}" class="h-6 flex items-center" style="padding: 0 9px" aria-label="Go to slide {{ $i + 1 }}">
                <span class="block h-1.5 rounded-full transition-all duration-300"
                      :class="current === {{ $i }} ? 'bg-gray-800 dark:bg-gray-200 w-6' : 'bg-gray-300 dark:bg-gray-600 w-1.5 hover:bg-gray-400 dark:hover:bg-gray-500'"></span>
            </button>
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
                            <p class="font-bold text-gray-900 dark:text-gray-100 text-[10px] sm:text-xs leading-tight">{{ $f['text'] }}</p>
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
                            <p class="font-bold text-gray-900 dark:text-gray-100 text-[10px] sm:text-xs leading-tight">{{ $f['text'] }}</p>
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
            <a href="{{ route('shop.index') }}?on_sale=1" class="group inline-flex items-center gap-1 bg-red-50 hover:bg-red-100 dark:bg-red-500/10 dark:hover:bg-red-500/20 text-red-700 dark:text-red-400 px-3.5 py-1.5 rounded-full font-bold text-xs transition">
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
        {{-- Used to switch to a plain grid at sm:+ (sm:grid-cols-4, md:6, lg:8), which
             just meant it wrapped onto a second row the moment there were more categories
             than one row's column count — reported as categories "going down" on wider
             screens. Now a single horizontal rail at every breakpoint, same auto-scroll +
             arrows + edge-fade carousel as the Top Brands rail below (copy-pasted scroll
             logic; kept as its own instance rather than a shared component since Blade has
             no component-with-slot-and-shared-Alpine-state primitive worth the indirection
             here). Auto-advances one page every 3.5s, pauses instantly on hover/touch, and
             the arrows (desktop only — phones already swipe) disable+fade at either end. --}}
        <div class="relative"
             x-data="{
                atStart: true, atEnd: false, paused: false, timer: null,
                init() {
                    this.$nextTick(() => this.updateEdges());
                    this.$refs.track.addEventListener('scroll', () => this.updateEdges(), { passive: true });
                    this.timer = setInterval(() => { if (!this.paused) this.step(1, true); }, 3500);
                },
                updateEdges() {
                    const el = this.$refs.track;
                    this.atStart = el.scrollLeft <= 4;
                    this.atEnd = el.scrollLeft + el.clientWidth >= el.scrollWidth - 4;
                },
                step(dir, auto = false) {
                    const el = this.$refs.track;
                    // Turbo swapped the page out but this timer kept firing — stop it for good
                    if (!el || !el.isConnected) { clearInterval(this.timer); return; }
                    if (auto && this.atEnd) { el.scrollTo({ left: 0, behavior: 'smooth' }); return; }
                    el.scrollBy({ left: dir * el.clientWidth * 0.8, behavior: 'smooth' });
                },
             }"
             @mouseenter="paused = true" @mouseleave="paused = false"
             @touchstart.passive="paused = true" @touchend.passive="setTimeout(() => paused = false, 2500)">
            <div class="flex gap-3 overflow-x-auto snap-x snap-mandatory scrollbar-hide pb-1 reveal-group" x-ref="track">
                @foreach($categories as $category)
                    {{-- Circular "story ring" tile (Instagram/Daraz "Shop by Category" pattern)
                         instead of a square icon block — reads as noticeably more polished at
                         a smaller footprint, and the ring + press-scale make it unmistakably
                         tappable rather than just decorative. --}}
                    <a href="{{ route('shop.category', $category->slug) }}"
                       class="group w-16 sm:w-20 md:w-24 flex-shrink-0 snap-start flex flex-col items-center gap-2 p-1.5 rounded-xl active:scale-95 transition-transform duration-150">
                        {{-- category-ring (app.blade.php <style>) is a continuously-spinning
                             conic-gradient border — smaller than before, too, so one more
                             tile fits in the same mobile viewport width. --}}
                        <div class="category-ring relative w-12 h-12 md:w-14 md:h-14 rounded-full p-[2.5px] shadow-sm group-hover:shadow-lg group-hover:shadow-orange-500/20 group-hover:scale-105 transition-all duration-300">
                            <div class="w-full h-full rounded-full bg-white dark:bg-gray-900 p-[3px]">
                                <div class="w-full h-full rounded-full overflow-hidden bg-gradient-to-br from-orange-50 to-orange-100 dark:from-gray-800 dark:to-gray-700 flex items-center justify-center">
                                    @if($category->image)
                                        <img src="{{ image_thumb($category->image, 200) }}" alt="" width="200" height="200" loading="lazy" decoding="async" class="w-full h-full object-cover">
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

            {{-- Edge fades, hidden once scrolled all the way to that edge. --}}
            <div class="pointer-events-none absolute left-0 inset-y-0 top-0 bottom-1 w-8 md:w-10 bg-gradient-to-r from-orange-50/70 dark:from-gray-900 to-transparent transition-opacity duration-200" :class="atStart ? 'opacity-0' : 'opacity-100'"></div>
            <div class="pointer-events-none absolute right-0 inset-y-0 top-0 bottom-1 w-8 md:w-10 bg-gradient-to-l from-white dark:from-gray-900 to-transparent transition-opacity duration-200" :class="atEnd ? 'opacity-0' : 'opacity-100'"></div>

            {{-- Desktop only — mobile already swipes natively. --}}
            <button type="button" @click="step(-1)" :disabled="atStart" :class="atStart ? 'opacity-0 pointer-events-none' : 'opacity-100'"
                    class="hidden md:flex absolute left-0 top-[34px] -translate-y-1/2 -translate-x-1/2 z-10 w-9 h-9 items-center justify-center rounded-full bg-white dark:bg-gray-800 shadow-lg ring-1 ring-black/5 dark:ring-white/10 text-gray-500 dark:text-gray-300 hover:text-orange-600 hover:shadow-xl active:scale-90 transition-all duration-200" aria-label="Scroll categories left">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
            </button>
            <button type="button" @click="step(1)" :disabled="atEnd" :class="atEnd ? 'opacity-0 pointer-events-none' : 'opacity-100'"
                    class="hidden md:flex absolute right-0 top-[34px] -translate-y-1/2 translate-x-1/2 z-10 w-9 h-9 items-center justify-center rounded-full bg-white dark:bg-gray-800 shadow-lg ring-1 ring-black/5 dark:ring-white/10 text-gray-500 dark:text-gray-300 hover:text-orange-600 hover:shadow-xl active:scale-90 transition-all duration-200" aria-label="Scroll categories right">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
            </button>
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
                <img src="{{ image_thumb($banner->image, 800) }}" alt="{{ $banner->title }}" loading="lazy" decoding="async" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
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
    @php
        $sec = $entry['section']; $totalCount = $entry['totalCount'];
        // Only the first two sections are rendered into the page; the rest (each ~250-450 DOM
        // elements of product cards) fetch their cards as the visitor scrolls near them. With all
        // ~100 cards in the initial HTML, building and Alpine-initializing a 4,300-element page
        // was the main source of PageSpeed's Total Blocking Time on phones. Google renders
        // IntersectionObserver lazy-loading, so these products stay visible to it. Personalized
        // sections differ per visitor and stay server-rendered.
        $lazySection = $loop->index >= 2 && $sec->source_type !== 'personalized';
    @endphp
    {{-- revealedExtra: how many of the beyond-product_limit "overflow" products are currently
         shown, staged in batches of $step (2 desktop rows' worth) per click instead of all at
         once — a long section used to dump its entire remaining batch on the first click, which
         read as an abrupt wall of products rather than a smooth, inviting "there's more" browse. --}}
    {{-- Only the first product_limit cards are rendered; the overflow (up to the section's
         fetched batch) is pulled from home.section-products a batch at a time on "See More",
         instead of shipping every hidden card in the initial HTML. --}}
    <div class="cv-auto relative overflow-hidden mx-3 md:mx-0 mt-3 md:mt-4 rounded-2xl md:rounded-none shadow-sm md:shadow-none {{ $sec->theme === 'sale' ? 'bg-gradient-to-r from-red-500 to-orange-500' : 'bg-white dark:bg-gray-900' }}"
         data-track-list="{{ $sec->source_type === 'personalized' ? 'Home: Recommended for you' : 'Home: ' . $sec->title }}"
         x-data="{
            expanded: false, loading: false,
            next: {{ min($sec->product_limit, count($entry['products'])) }},
            hasMore: {{ count($entry['products']) > $sec->product_limit ? 'true' : 'false' }},
            @if($lazySection)
            init() {
                if (!this.$refs.grid.querySelector('[data-skeleton]')) return; // already filled (Turbo restore)
                const io = new IntersectionObserver((entries) => {
                    if (!entries.some(e => e.isIntersecting)) return;
                    io.disconnect();
                    fetch('{{ route('home.section-products', $sec) }}?offset=0&count={{ $sec->product_limit }}&mobile_cap={{ min(8, $sec->product_limit) }}', { headers: { 'Accept': 'application/json' } })
                        .then(r => r.json())
                        .then(data => { this.$refs.grid.innerHTML = data.html; })
                        .catch(() => { this.$refs.grid.innerHTML = ''; });
                }, { rootMargin: '800px 0px' });
                io.observe(this.$el);
            },
            @endif
            async loadMore(count) {
                this.expanded = true;
                if (this.loading || !this.hasMore) return;
                this.loading = true;
                try {
                    const res = await fetch('{{ route('home.section-products', $sec) }}?offset=' + this.next + '&count=' + count, { headers: { 'Accept': 'application/json' } });
                    const data = await res.json();
                    this.$refs.grid.insertAdjacentHTML('beforeend', data.html);
                    this.next = data.next;
                    this.hasMore = data.hasMore;
                } catch (e) {
                    this.hasMore = false;
                } finally {
                    this.loading = false;
                }
            },
         }">
        {{-- Same quiet corner-glow treatment as the trust banner — breaks up the run of
             flat white section cards down the page without competing with the products. --}}
        @if($sec->theme !== 'sale')
        <div class="pointer-events-none absolute -top-16 -right-16 w-48 h-48 bg-orange-500/5 dark:bg-orange-500/10 blur-3xl rounded-full"></div>
        @endif
        <div class="relative max-w-[1200px] mx-auto px-4 py-6">
            {{-- Personalized sections show no title/badge/"View All" at all — by design
                 (Admin > Home Sections > Product Source > Personalized), so it reads as
                 just more products rather than a labeled "recommended for you" block. The
                 Title field still exists for the admin's own reference in the section list. --}}
            @unless($sec->source_type === 'personalized')
            <x-storefront.section-header :title="$sec->title" :subtitle="$sec->subtitle" :view-all-url="$sec->getViewAllUrl()"
                :view-all-label="$sec->getViewAllLabelText()" :theme="$sec->theme === 'sale' ? 'sale' : 'default'" :count="$totalCount" />
            @endunless
            {{-- Back to the familiar grid + "View More" button (swiping felt less natural
                 here than it did for Categories) — but newly-revealed cards now fade/slide
                 in instead of just popping into existence, which they never did before. --}}
            @php
                // Mobile is always a fixed 2-column grid (see getGridColsClass), so
                // capping the always-visible tier at 8 keeps mobile's first view to
                // 4 rows regardless of how many columns/products_limit the admin
                // picked for desktop — the rest still shows immediately at sm+.
                $mobileCap = min(8, $sec->product_limit);
                // Step size for the "reveal more" button below: 2 rows at the section's own
                // desktop column count (admin-configured, 2-6 — see getGridColsClass). Mobile is
                // always a fixed 2-column grid regardless of that setting, so a step sized for a
                // wide desktop grid reveals more than literally 2 rows there — an acceptable
                // trade-off for one shared counter rather than tracking per-breakpoint reveal
                // state, and still "staged in batches," never the old single all-at-once dump.
                $step = $sec->columns * 2;

                // Admin-chosen colors (Admin > Home Sections > edit > "See More" Button Color)
                // override the hardcoded theme classes below only when both gradient stops are
                // set — otherwise this stays null and the button keeps its exact current look.
                $seeMoreBgStyle = $sec->getSeeMoreBackgroundStyle();
                $seeMoreStyle = trim(($seeMoreBgStyle ?? '') . ($sec->see_more_text_color ? " color: {$sec->see_more_text_color};" : ''));
                $seeMoreColorClasses = $seeMoreBgStyle
                    ? 'hover:shadow-lg hover:shadow-black/10'
                    : ($sec->theme === 'sale' ? 'bg-white text-orange-600 hover:bg-gray-100' : 'bg-gradient-to-r from-orange-500 to-red-500 text-white hover:shadow-lg hover:shadow-orange-500/30');
            @endphp
            <div class="grid {{ $sec->getGridColsClass() }} gap-3 reveal-group" x-ref="grid">
                @if($lazySection)
                    {{-- Placeholders holding the section's height until its cards arrive --}}
                    @for($i = 0; $i < min($sec->product_limit, count($entry['products'])); $i++)
                        <div data-skeleton class="{{ $i >= $mobileCap ? 'hidden sm:block' : '' }} rounded-xl bg-gray-100 dark:bg-gray-800" style="aspect-ratio: 3 / 5"></div>
                    @endfor
                @else
                    @foreach($entry['products']->take($sec->product_limit) as $i => $product)
                        @include('partials.home-section-card', ['product' => $product, 'hideOnMobile' => $i >= $mobileCap])
                    @endforeach
                @endif
            </div>
            {{-- Only rendered when there's actually more to reveal — clicking stays right
                 here on the homepage and shows the rest of this section's fetched batch,
                 no navigation away (unlike the small "View All" link above, which still
                 goes to the full /shop listing). When the only hidden tier is the
                 mobile row cap (nothing held back on desktop), the button itself is
                 mobile-only — there'd be nothing left for it to reveal at sm+. --}}
            @if($totalCount > $sec->product_limit)
            <div class="text-center mt-6" x-show="hasMore || !expanded">
                <button type="button" @click="loadMore({{ $step }})" :disabled="loading" :class="loading && 'opacity-60 cursor-wait'"
                   @if($seeMoreStyle) style="{{ $seeMoreStyle }}" @endif
                   class="group inline-flex items-center gap-2 {{ $seeMoreColorClasses }} px-10 py-2.5 rounded-full font-bold text-sm transition-all duration-300 shadow-md hover:-translate-y-0.5">
                    {{ $sec->getSeeMoreLabelText() }}
                    <svg class="w-4 h-4 group-hover:translate-y-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                </button>
            </div>
            @elseif($sec->product_limit > $mobileCap)
            <div class="text-center mt-6 sm:hidden" x-show="!expanded">
                <button type="button" @click="expanded = true"
                   @if($seeMoreStyle) style="{{ $seeMoreStyle }}" @endif
                   class="group inline-flex items-center gap-2 {{ $seeMoreColorClasses }} px-10 py-2.5 rounded-full font-bold text-sm transition-all duration-300 shadow-md hover:-translate-y-0.5">
                    {{ $sec->getSeeMoreLabelText() }}
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
<div class="cv-auto mx-3 md:mx-0 mt-3 md:mt-4 py-8 bg-gradient-to-r from-indigo-50 via-white to-orange-50 overflow-hidden rounded-2xl md:rounded-none shadow-sm md:shadow-none">
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
<div class="cv-auto mx-3 md:mx-0 mt-3 md:mt-4 bg-white dark:bg-gray-900 rounded-2xl md:rounded-none shadow-sm md:shadow-none">
    <div class="max-w-[1200px] mx-auto px-4 py-6">
        <x-storefront.section-header title="Top Brands" :view-all-url="route('brands.index')" />
        {{-- carousel-container was a plain overflow-x-auto strip with no way to tell it
             scrolled at all (no arrows, no motion) — "auto scroll doesn't work" because
             there never was one. Auto-advances one page at a time, pauses the instant a
             visitor touches/hovers it (so it never fights a manual scroll or swipe), and
             the arrows disable+fade at either end instead of staying clickable past the
             last brand. Edge fades hint there's more to scroll to without needing a
             scrollbar, same "soft mask" treatment panels like this use elsewhere. --}}
        <div class="relative"
             x-data="{
                atStart: true, atEnd: false, paused: false, timer: null,
                init() {
                    this.$nextTick(() => this.updateEdges());
                    this.$refs.track.addEventListener('scroll', () => this.updateEdges(), { passive: true });
                    this.timer = setInterval(() => { if (!this.paused) this.step(1, true); }, 3000);
                },
                updateEdges() {
                    const el = this.$refs.track;
                    this.atStart = el.scrollLeft <= 4;
                    this.atEnd = el.scrollLeft + el.clientWidth >= el.scrollWidth - 4;
                },
                step(dir, auto = false) {
                    const el = this.$refs.track;
                    // Turbo swapped the page out but this timer kept firing — stop it for good
                    if (!el || !el.isConnected) { clearInterval(this.timer); return; }
                    if (auto && this.atEnd) { el.scrollTo({ left: 0, behavior: 'smooth' }); return; }
                    el.scrollBy({ left: dir * el.clientWidth * 0.8, behavior: 'smooth' });
                },
             }"
             @mouseenter="paused = true" @mouseleave="paused = false"
             @touchstart.passive="paused = true" @touchend.passive="setTimeout(() => paused = false, 2500)">
            <div class="carousel-container flex gap-3 overflow-x-auto scrollbar-hide pb-2 scroll-smooth reveal-group" x-ref="track">
                @foreach($brands as $brand)
                    {{-- Forced light tile (dark:bg-white/dark:border/dark:text below) rather than
                         letting the site-wide dark-mode retrofit darken this one: brand logos are
                         fixed-color artwork made for a light background, so a logo with a transparent
                         background and dark-colored mark (e.g. Aveeno's) would otherwise vanish
                         against the retrofit's near-black card — see resources/css/app.css's
                         "Site-wide dark mode retrofit" comment, which calls out exactly this kind of
                         spot as the one to carve out with its own selector. --}}
                    <a href="{{ route('shop.brand', $brand) }}"
                       class="flex-shrink-0 w-32 h-20 bg-gray-50 dark:bg-white border border-gray-100 dark:border-gray-200 rounded-xl flex items-center justify-center hover:border-orange-300 hover:shadow-md dark:hover:shadow-black/30 transition-all duration-200 group">
                        @if($brand->logo)
                            <img src="{{ image_thumb($brand->logo, 200) }}" alt="{{ $brand->name }}" loading="lazy" decoding="async" class="max-w-[80%] max-h-[60%] object-contain group-hover:scale-105 transition">
                        @else
                            <span class="text-gray-500 dark:text-gray-500 font-bold text-sm group-hover:text-orange-700 transition">{{ $brand->name }}</span>
                        @endif
                    </a>
                @endforeach
            </div>

            {{-- Edge fades — pointer-events-none so they never block the arrows/tiles
                 sitting on top of them. Hidden once scrolled all the way to that edge,
                 since "more content this way" would be a lie at that point. --}}
            <div class="pointer-events-none absolute left-0 inset-y-0 top-0 bottom-2 w-10 bg-gradient-to-r from-white dark:from-gray-900 to-transparent transition-opacity duration-200" :class="atStart ? 'opacity-0' : 'opacity-100'"></div>
            <div class="pointer-events-none absolute right-0 inset-y-0 top-0 bottom-2 w-10 bg-gradient-to-l from-white dark:from-gray-900 to-transparent transition-opacity duration-200" :class="atEnd ? 'opacity-0' : 'opacity-100'"></div>

            {{-- Desktop only — mobile already swipes naturally, and a thumb-width circle
                 sitting on top of the first/last tile there would cost more than it gives. --}}
            <button type="button" @click="step(-1)" :disabled="atStart" :class="atStart ? 'opacity-0 pointer-events-none' : 'opacity-100'"
                    class="hidden md:flex absolute left-0 top-1/2 -translate-y-1/2 -translate-x-1/2 z-10 w-9 h-9 items-center justify-center rounded-full bg-white dark:bg-gray-800 shadow-lg ring-1 ring-black/5 dark:ring-white/10 text-gray-500 dark:text-gray-300 hover:text-orange-600 hover:shadow-xl active:scale-90 transition-all duration-200" aria-label="Scroll brands left">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
            </button>
            <button type="button" @click="step(1)" :disabled="atEnd" :class="atEnd ? 'opacity-0 pointer-events-none' : 'opacity-100'"
                    class="hidden md:flex absolute right-0 top-1/2 -translate-y-1/2 translate-x-1/2 z-10 w-9 h-9 items-center justify-center rounded-full bg-white dark:bg-gray-800 shadow-lg ring-1 ring-black/5 dark:ring-white/10 text-gray-500 dark:text-gray-300 hover:text-orange-600 hover:shadow-xl active:scale-90 transition-all duration-200" aria-label="Scroll brands right">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
            </button>
        </div>
    </div>
</div>
@endif

{{-- ═══════════ JUST FOR YOU (personalized feel — overflow from the New Arrivals section) ═══════════ --}}
@if($justForYou->isNotEmpty())
<div class="cv-auto mx-3 md:mx-0 mt-3 md:mt-4 bg-white dark:bg-gray-900 rounded-2xl md:rounded-none shadow-sm md:shadow-none">
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
<div class="cv-auto mt-4 mb-2">
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
