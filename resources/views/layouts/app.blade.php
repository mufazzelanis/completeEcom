@php
$siteName     = setting('site_name', 'ShopVista');
$siteTagline  = setting('site_tagline', 'Your one-stop shop for everything you need.');
$logoUrl      = setting_file_url('site_logo');
$faviconUrl   = setting_file_url('favicon');
$primaryColor   = setting('primary_color', '#ea580c');
$secondaryColor = setting('secondary_color', '#ec4899');
$accentColor    = setting('accent_color', '#dc2626');
$textColor      = setting('text_color', '#1f2937');
// Only generate an override ramp when the admin actually picked a different brand color —
// keeps the default look pixel-identical to the hand-tuned Tailwind palette.
$brandShades    = $primaryColor !== '#ea580c' ? brand_color_shades($primaryColor) : null;
$secondaryShades = $secondaryColor !== '#ec4899' ? brand_color_shades($secondaryColor) : null;
$accentShades   = $accentColor !== '#dc2626' ? brand_color_shades($accentColor) : null;
$textColorChanged = $textColor !== '#1f2937';
$gaId         = setting('google_analytics_id', '');
$gtmId        = setting('google_tag_manager_id', '');
$pixelId      = setting('facebook_pixel_id', '');
$customCss    = setting('custom_css', '');

// Theme & Design (Settings → Theme & Design) — same "override the literal Tailwind
// utility classes already hardcoded throughout the storefront" approach as brand colors.
$fontFamily      = setting('font_family', 'Inter, sans-serif');
$isSystemFont    = str_contains($fontFamily, 'system-ui');
$googleFontName  = trim(explode(',', $fontFamily)[0]);
$buttonStyle     = setting('button_style', 'rounded');
$borderRadius    = setting('border_radius', 'soft');
$shadowStyle     = setting('shadow_style', 'soft');
$containerWidth  = setting('container_width', 'standard');
$darkModeDefault = setting('dark_mode_default', 'system');

$radiusScale = [
    'sharp'  => ['rounded' => '0px',   'rounded-md' => '2px',  'rounded-lg' => '4px',  'rounded-xl' => '6px',  'rounded-2xl' => '8px'],
    'soft'   => null, // Tailwind's own defaults — nothing to override
    'round'  => ['rounded' => '8px',   'rounded-md' => '12px', 'rounded-lg' => '16px', 'rounded-xl' => '24px', 'rounded-2xl' => '32px'],
    'xround' => ['rounded' => '16px',  'rounded-md' => '20px', 'rounded-lg' => '28px', 'rounded-xl' => '40px', 'rounded-2xl' => '48px'],
][$borderRadius] ?? null;

$shadowScale = [
    'none'   => 'none',
    'soft'   => null, // Tailwind's own shadow-sm — nothing to override
    'medium' => '0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1)',
    'strong' => '0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1)',
][$shadowStyle] ?? null;

$containerScale = [
    'compact'  => '1040px',
    'standard' => null, // matches the hardcoded max-w-[1200px] already in use — nothing to override
    'wide'     => '1400px',
][$containerWidth] ?? null;

$buttonRadiusValue = ['square' => '0px', 'pill' => '9999px'][$buttonStyle] ?? null; // null = 'rounded' (default, no override)
// Brand-colored elements are the storefront's actual buttons; this list of Tailwind
// radius classes covers every corner style real buttons use across the site.
$buttonRadiusClasses = ['rounded', 'rounded-md', 'rounded-lg', 'rounded-xl', 'rounded-2xl', 'rounded-full', 'rounded-r-md', 'rounded-l-md'];
$buttonColorSteps = ['400', '500', '600', '700', '800'];
$announcementEnabled = setting('announcement_enabled', '0') === '1';
$announcementText    = setting('announcement_text', '');
$announcementBg      = setting('announcement_bg', '#6366f1');
$announcementColor   = setting('announcement_color', '#ffffff');
$headerLayout        = setting('header_layout', 'default');
$stickyHeader         = setting('sticky_header', '1') === '1';
$topbarPhone          = setting('topbar_phone', '');
$topbarEmail          = setting('topbar_email', '');
$topbarText           = setting('topbar_text', '');
// Auto-show whenever the admin has actually filled in contact info — so forgetting
// the separate "Enable Top Bar" checkbox doesn't silently hide already-saved content.
$topBarEnabled        = setting('top_bar_enabled', '0') === '1' || $topbarPhone || $topbarEmail || $topbarText;
// $currentLanguage / $activeLanguages are shared globally by App\Http\Middleware\SetLocale.
$showLanguageSwitcher = setting('multi_language_enabled', '0') === '1' && isset($activeLanguages) && $activeLanguages->count() > 1;
$htmlLang  = $currentLanguage->code ?? setting('default_language', 'en');
$htmlDir   = $currentLanguage->direction ?? 'ltr';

// Site-wide SEO fallback — individual pages override via @section('title'), @section('og_image'),
// etc. (see products/show, shop/index, pages/show, blog/show) so every page gets unique meta
// tags instead of the whole site sharing one description, while pages that don't bother to
// override still get sensible non-empty tags for social shares and search snippets.
$pageTitle       = trim($__env->yieldContent('title', setting('seo_meta_title', $siteName . ' – Online Store')));
$pageDescription = trim($__env->yieldContent('meta_description', setting('seo_meta_description', $siteTagline)));
$pageKeywords    = trim($__env->yieldContent('meta_keywords', setting('seo_keywords', '')));
$pageCanonical   = trim($__env->yieldContent('canonical', url()->current()));
$pageOgType      = trim($__env->yieldContent('og_type', 'website'));
$pageOgImage     = trim($__env->yieldContent('og_image', $logoUrl ?: ''));
$pageOgTitle     = trim($__env->yieldContent('og_title', $pageTitle));
$pageOgDesc      = trim($__env->yieldContent('og_description', $pageDescription));
$pageRobots      = trim($__env->yieldContent('robots', 'index, follow'));
$ogSiteName      = setting('og_site_name') ?: $siteName;
$twitterSite     = setting('og_twitter_user', '');
$pageTwitterCard  = trim($__env->yieldContent('twitter_card', $pageOgImage ? 'summary_large_image' : 'summary'));
$pageTwitterTitle = trim($__env->yieldContent('twitter_title', $pageTitle));
$pageTwitterDesc  = trim($__env->yieldContent('twitter_description', $pageDescription));
$pageTwitterImage = trim($__env->yieldContent('twitter_image', $pageOgImage));
@endphp
<!DOCTYPE html>
<html lang="{{ $htmlLang }}" dir="{{ $htmlDir }}" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    @if($pageKeywords)<meta name="keywords" content="{{ $pageKeywords }}">@endif
    <meta name="robots" content="{{ $pageRobots }}">
    <link rel="canonical" href="{{ $pageCanonical }}">
    @if($faviconUrl)<link rel="icon" href="{{ $faviconUrl }}">@endif
    @if(setting('google_site_verification'))<meta name="google-site-verification" content="{{ setting('google_site_verification') }}">@endif
    @if(!$isSystemFont)
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family={{ str_replace(' ', '+', $googleFontName) }}:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @endif

    {{-- Open Graph --}}
    <meta property="og:site_name" content="{{ $ogSiteName }}">
    <meta property="og:type" content="{{ $pageOgType }}">
    <meta property="og:title" content="{{ $pageOgTitle }}">
    <meta property="og:description" content="{{ $pageOgDesc }}">
    <meta property="og:url" content="{{ $pageCanonical }}">
    @if($pageOgImage)<meta property="og:image" content="{{ $pageOgImage }}">@endif

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="{{ $pageTwitterCard }}">
    @if($twitterSite)<meta name="twitter:site" content="{{ '@' . ltrim($twitterSite, '@') }}">@endif
    <meta name="twitter:title" content="{{ $pageTwitterTitle }}">
    <meta name="twitter:description" content="{{ $pageTwitterDesc }}">
    @if($pageTwitterImage)<meta name="twitter:image" content="{{ $pageTwitterImage }}">@endif

    @stack('meta') {{-- JSON-LD / structured data pushed by individual pages --}}
    <script>
        // Applied before first paint so there's never a flash of the wrong theme.
        (function () {
            var stored = localStorage.getItem('site-theme');
            var adminDefault = {{ Js::from($darkModeDefault) }}; // admin's Theme & Design → Dark Mode Default
            var isDark;
            if (stored) {
                isDark = stored === 'dark';
            } else if (adminDefault === 'dark') {
                isDark = true;
            } else if (adminDefault === 'light') {
                isDark = false;
            } else {
                isDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            }
            document.documentElement.classList.toggle('dark', isDark);
        })();
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            @if($brandShades)
                                @foreach($brandShades as $step => $hex) {{ $step }}:'{{ $hex }}', @endforeach
                            @else
                                50:'#fff7ed',100:'#ffedd5',200:'#fed7aa',300:'#fdba74',400:'#fb923c',500:'#f97316',600:'#ea580c',700:'#c2410c',800:'#9a3412',900:'#7c2d12',
                            @endif
                        },
                    }
                }
            }
        }
    </script>
    @if($brandShades || $secondaryShades || $accentShades || $textColorChanged)
    {{-- Brand Colors (Settings → Branding): the storefront's palette is hardcoded as literal
         Tailwind classes throughout, so re-theming it means overriding those generated utility
         classes directly with the admin's chosen colors. Primary→orange, Secondary→pink,
         Accent→red (each admin field's own default hex is that exact Tailwind shade), and
         Text→gray-800 (the default body/heading text color). --}}
    <style>
        @if($brandShades)
        @foreach($brandShades as $step => $hex)
        .bg-orange-{{ $step }} { background-color: {{ $hex }} !important; }
        .text-orange-{{ $step }} { color: {{ $hex }} !important; }
        .border-orange-{{ $step }} { border-color: {{ $hex }} !important; }
        .ring-orange-{{ $step }} { --tw-ring-color: {{ $hex }} !important; }
        .hover\:bg-orange-{{ $step }}:hover { background-color: {{ $hex }} !important; }
        .hover\:text-orange-{{ $step }}:hover { color: {{ $hex }} !important; }
        .hover\:border-orange-{{ $step }}:hover { border-color: {{ $hex }} !important; }
        .focus\:ring-orange-{{ $step }}:focus { --tw-ring-color: {{ $hex }} !important; }
        .focus\:border-orange-{{ $step }}:focus { border-color: {{ $hex }} !important; }
        @endforeach
        @endif
        @if($secondaryShades)
        @foreach($secondaryShades as $step => $hex)
        .bg-pink-{{ $step }} { background-color: {{ $hex }} !important; }
        .text-pink-{{ $step }} { color: {{ $hex }} !important; }
        .border-pink-{{ $step }} { border-color: {{ $hex }} !important; }
        .ring-pink-{{ $step }} { --tw-ring-color: {{ $hex }} !important; }
        .hover\:bg-pink-{{ $step }}:hover { background-color: {{ $hex }} !important; }
        .hover\:text-pink-{{ $step }}:hover { color: {{ $hex }} !important; }
        .hover\:border-pink-{{ $step }}:hover { border-color: {{ $hex }} !important; }
        .focus\:ring-pink-{{ $step }}:focus { --tw-ring-color: {{ $hex }} !important; }
        .focus\:border-pink-{{ $step }}:focus { border-color: {{ $hex }} !important; }
        @endforeach
        @endif
        @if($accentShades)
        @foreach($accentShades as $step => $hex)
        .bg-red-{{ $step }} { background-color: {{ $hex }} !important; }
        .text-red-{{ $step }} { color: {{ $hex }} !important; }
        .border-red-{{ $step }} { border-color: {{ $hex }} !important; }
        .ring-red-{{ $step }} { --tw-ring-color: {{ $hex }} !important; }
        .hover\:bg-red-{{ $step }}:hover { background-color: {{ $hex }} !important; }
        .hover\:text-red-{{ $step }}:hover { color: {{ $hex }} !important; }
        .hover\:border-red-{{ $step }}:hover { border-color: {{ $hex }} !important; }
        .focus\:ring-red-{{ $step }}:focus { --tw-ring-color: {{ $hex }} !important; }
        .focus\:border-red-{{ $step }}:focus { border-color: {{ $hex }} !important; }
        @endforeach
        @endif
        @if($textColorChanged)
        .text-gray-800 { color: {{ $textColor }} !important; }
        @endif
    </style>
    @endif
    @if(!$isSystemFont || $radiusScale || $shadowScale || $containerScale || $buttonRadiusValue)
    {{-- Theme & Design (Settings → Theme & Design): same literal-class-override approach
         as brand colors above — font/radius/shadow/width are hardcoded Tailwind utility
         classes throughout the storefront, so re-theming them means overriding those
         generated classes directly rather than templating every view. --}}
    <style>
        @if(!$isSystemFont)
        .font-sans { font-family: {{ $fontFamily }} !important; }
        @endif
        @if($radiusScale)
        @foreach($radiusScale as $class => $px)
        .{{ $class }} { border-radius: {{ $px }} !important; }
        @endforeach
        @endif
        @if($shadowScale)
        .shadow-sm { box-shadow: {{ $shadowScale }} !important; }
        @endif
        @if($containerScale)
        .max-w-\[1200px\] { max-width: {{ $containerScale }} !important; }
        @endif
        @if($buttonRadiusValue)
        {{-- Scoped to brand-colored elements (the site's actual buttons) so circular
             avatars/badges — which use gradient backgrounds, not a plain bg-{color}-{step}
             class — are never affected. --}}
        @foreach(['orange', 'pink', 'red'] as $color)
        @foreach($buttonColorSteps as $step)
        @foreach($buttonRadiusClasses as $radiusClass)
        .bg-{{ $color }}-{{ $step }}.{{ $radiusClass }} { border-radius: {{ $buttonRadiusValue }} !important; }
        @endforeach
        @endforeach
        @endforeach
        @endif
    </style>
    @endif
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('theme', {
                dark: document.documentElement.classList.contains('dark'),
                toggle() {
                    this.dark = !this.dark;
                    localStorage.setItem('site-theme', this.dark ? 'dark' : 'light');
                    document.documentElement.classList.toggle('dark', this.dark);
                },
            });
        });
    </script>
    <style>
        [x-cloak]{display:none!important}
        .scrollbar-hide::-webkit-scrollbar{display:none}
        .scrollbar-hide{-ms-overflow-style:none;scrollbar-width:none}
        .carousel-container{scroll-snap-type:x mandatory;-webkit-overflow-scrolling:touch}
        .carousel-container > *{scroll-snap-align:start}
        @keyframes slideIn{from{opacity:0;transform:translateX(30px)}to{opacity:1;transform:translateX(0)}}
        .animate-slide-in{animation:slideIn .4s ease-out}
        @keyframes pulse-badge{0%,100%{transform:scale(1)}50%{transform:scale(1.05)}}
        .pulse-badge{animation:pulse-badge 2s infinite}
        .fade-in{animation:fadeIn .3s ease-in}
        @keyframes fadeIn{from{opacity:0}to{opacity:1}}
        @keyframes marquee{from{transform:translateX(0)}to{transform:translateX(-50%)}}
        .animate-marquee{animation:marquee 45s linear infinite}
        .marquee-pause:hover .animate-marquee{animation-play-state:paused}
    </style>
    @if($customCss)<style>{{ $customCss }}</style>@endif
    @if($gaId)
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaId }}"></script>
    <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','{{ $gaId }}');</script>
    @endif
    @if($gtmId)
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','{{ $gtmId }}');</script>
    @endif
    @if($pixelId)
    <script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','{{ $pixelId }}');fbq('track','PageView');</script>
    @endif
</head>
<body class="bg-gray-100 dark:bg-gray-950 font-sans antialiased transition-colors">
@if($gtmId)<noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtmId }}" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>@endif

@php
$navCategories = \App\Models\Category::with(['children' => fn($q) => $q->where('is_active', true)->orderBy('sort_order')])
    ->whereNull('parent_id')->where('is_active', true)->orderBy('sort_order')->take(12)->get();
@endphp

{{-- Top Utility Bar --}}
@if($topBarEnabled)
<div class="bg-gray-800 text-gray-300 text-xs hidden md:block">
    <div class="max-w-[1200px] mx-auto px-4 flex items-center justify-between h-8">
        <div class="flex items-center gap-4">
            <span>{{ $topbarText ?: t('header.welcome_default', 'Welcome to :site', ['site' => $siteName], 'header') }}</span>
            <span class="text-gray-600">|</span>
            <a href="{{ route('vendor.apply') }}" class="hover:text-white transition">{{ t('header.sell_on', 'Sell on :site', ['site' => $siteName], 'header') }}</a>
        </div>
        <div class="flex items-center gap-4">
            @if($topbarPhone)
                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $topbarPhone) }}" class="hover:text-white transition flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    {{ $topbarPhone }}
                </a>
                <span class="text-gray-600">|</span>
            @endif
            @if($topbarEmail)
                <a href="mailto:{{ $topbarEmail }}" class="hover:text-white transition flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    {{ $topbarEmail }}
                </a>
                <span class="text-gray-600">|</span>
            @endif
            <a href="{{ route('guest.order.track.form') }}" class="hover:text-white transition">{{ t('header.track_order', 'Track Order', [], 'header') }}</a>
            <span class="text-gray-600">|</span>
            <a href="{{ route('faq') }}" class="hover:text-white transition">{{ t('header.help_center', 'Help Center', [], 'header') }}</a>
        </div>
    </div>
</div>
@endif

{{-- Announcement Bar --}}
@if($announcementEnabled && $announcementText)
<div style="background: {{ $announcementBg }}; color: {{ $announcementColor }};" class="text-sm py-1.5 text-center font-medium px-4" x-data="{ show: true }" x-show="show">
    {!! $announcementText !!}
    <button @click="show = false" class="ml-3 opacity-70 hover:opacity-100 text-lg leading-none" style="color: {{ $announcementColor }};">&times;</button>
</div>
@endif

{{-- Main Header --}}
<header class="bg-white dark:bg-gray-900 shadow-sm {{ $stickyHeader ? 'sticky top-0' : '' }} z-50 transition-colors" x-data="{ mobileOpen: false }">
    <div class="max-w-[1200px] mx-auto px-4">
        @if($headerLayout === 'centered')
            {{-- Centered layout: logo on its own row, search + actions below --}}
            <div class="flex flex-col items-center py-3 gap-2">
                @include('partials.storefront.header-logo')
                <div class="flex items-center justify-between w-full gap-4">
                    @include('partials.storefront.header-search')
                    @include('partials.storefront.header-actions')
                </div>
            </div>
        @else
            {{-- Default / minimal layout: single row --}}
            <div class="flex items-center justify-between h-14 md:h-16 gap-4">
                @include('partials.storefront.header-logo')
                @if($headerLayout !== 'minimal')
                    @include('partials.storefront.header-search')
                @endif
                @include('partials.storefront.header-actions')
            </div>
        @endif
    </div>

    {{-- Category Navigation Bar --}}
    @if($headerLayout !== 'minimal')
    <div class="bg-orange-500 hidden md:block border-t border-orange-400">
        <div class="max-w-[1200px] mx-auto px-4 flex items-center overflow-x-auto scrollbar-hide">
            @foreach($navCategories as $navCat)
                <div class="relative flex-shrink-0" x-data="{ open: false, top: 0, left: 0 }"
                     @mouseenter="open = true; const r = $el.getBoundingClientRect(); top = r.bottom; left = r.left;"
                     @mouseleave="open = false">
                    <a href="{{ route('shop.category', $navCat->slug) }}"
                       class="inline-flex items-center gap-1 text-sm text-white whitespace-nowrap hover:bg-orange-600 px-3 py-2.5 transition font-medium">
                        {{ $navCat->name }}
                        @if($navCat->children->count() > 0)
                            <svg class="w-3 h-3 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                        @endif
                    </a>
                    @if($navCat->children->count() > 0)
                        <div x-show="open" x-cloak x-transition
                             x-bind:style="`position:fixed; top:${top}px; left:${left}px;`"
                             class="bg-white text-gray-700 shadow-xl rounded-b-lg min-w-52 py-2 z-[150] border border-gray-100">
                            @foreach($navCat->children as $child)
                                <a href="{{ route('shop.category', $child->slug) }}"
                                   class="block px-4 py-2 text-sm hover:bg-orange-50 hover:text-orange-600 whitespace-nowrap transition">
                                    {{ $child->name }}
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
            <a href="{{ route('shop.index') }}" class="inline-flex items-center text-sm text-white/80 whitespace-nowrap hover:text-white px-3 py-2.5 ml-auto font-medium transition">{{ t('header.all_products', 'All Products', [], 'header') }} →</a>
        </div>
    </div>
    @endif

    {{-- Mobile Menu --}}
    <div x-show="mobileOpen" x-cloak x-transition class="md:hidden bg-white dark:bg-gray-900 border-t border-gray-100 dark:border-gray-700 shadow-xl">
        <div class="p-4">
            <form action="{{ route('shop.index') }}" method="GET" class="flex mb-4">
                <input type="text" name="search" placeholder="Search products..." class="flex-1 border-2 border-orange-400 rounded-l-md px-4 py-2 text-sm focus:outline-none focus:border-orange-500">
                <button type="submit" class="bg-orange-500 text-white px-4 py-2 rounded-r-md">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </button>
            </form>
            @auth
                <div class="flex items-center gap-3 pb-3 border-b border-gray-100 dark:border-gray-700 mb-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-orange-400 to-red-500 rounded-full flex items-center justify-center">
                        <span class="text-white font-bold">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                    </div>
                    <div>
                        <p class="font-bold text-sm text-gray-900 dark:text-gray-100">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ auth()->user()->email }}</p>
                    </div>
                </div>
            @endauth
            <nav class="space-y-1">
                <a href="{{ route('home') }}" class="block px-3 py-2.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-orange-50 dark:hover:bg-gray-800 hover:text-orange-600 rounded-lg transition">{{ t('header.home', 'Home', [], 'header') }}</a>
                <a href="{{ route('shop.index') }}" class="block px-3 py-2.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-orange-50 dark:hover:bg-gray-800 hover:text-orange-600 rounded-lg transition">{{ t('header.shop_all', 'Shop All', [], 'header') }}</a>
                @php
                    $mobileCategories = \App\Models\Category::whereNull('parent_id')->withCount('products')->orderBy('sort_order')->limit(8)->get();
                @endphp
                @if($mobileCategories->count() > 0)
                    <div x-data="{ showCats: false }">
                        <button @click="showCats = !showCats" class="flex items-center justify-between w-full px-3 py-2.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-orange-50 dark:hover:bg-gray-800 hover:text-orange-600 rounded-lg transition">
                            <span>{{ t('header.categories', 'Categories', [], 'header') }}</span>
                            <svg class="w-4 h-4 transition-transform" :class="showCats ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="showCats" x-cloak class="pl-4 space-y-1 mt-1">
                            @foreach($mobileCategories as $cat)
                                <a href="{{ route('shop.category', $cat->slug) }}" class="block px-3 py-2 text-xs text-gray-600 dark:text-gray-300 hover:bg-orange-50 dark:hover:bg-gray-800 hover:text-orange-600 rounded-lg transition">{{ $cat->name }}</a>
                            @endforeach
                            <a href="{{ route('shop.index') }}" class="block px-3 py-2 text-xs text-orange-500 font-medium hover:bg-orange-50 dark:hover:bg-gray-800 rounded-lg transition">{{ t('header.view_all_categories', 'View All Categories', [], 'header') }} →</a>
                        </div>
                    </div>
                @endif
                @auth
                    <a href="{{ route('orders.index') }}" class="block px-3 py-2.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-orange-50 dark:hover:bg-gray-800 hover:text-orange-600 rounded-lg transition">{{ t('header.my_orders', 'My Orders', [], 'header') }}</a>
                    <a href="{{ route('wishlist.index') }}" class="block px-3 py-2.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-orange-50 dark:hover:bg-gray-800 hover:text-orange-600 rounded-lg transition">{{ t('header.wishlist', 'Wishlist', [], 'header') }}</a>
                    <a href="{{ route('account.dashboard') }}" class="block px-3 py-2.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-orange-50 dark:hover:bg-gray-800 hover:text-orange-600 rounded-lg transition">{{ t('header.my_account', 'My Account', [], 'header') }}</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="block w-full text-left px-3 py-2.5 text-sm text-red-500 hover:bg-red-50 dark:hover:bg-gray-800 rounded-lg transition">{{ t('header.logout', 'Logout', [], 'header') }}</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="block px-3 py-2.5 text-sm text-orange-600 font-medium hover:bg-orange-50 dark:hover:bg-gray-800 rounded-lg transition">{{ t('header.login', 'Login', [], 'header') }}</a>
                    <a href="{{ route('register') }}" class="block px-3 py-2.5 text-sm text-white bg-orange-500 text-center font-medium rounded-lg hover:bg-orange-600 transition">{{ t('header.signup', 'Sign Up', [], 'header') }}</a>
                @endauth
            </nav>
        </div>
    </div>
</header>

{{-- Flash Messages --}}
@if(session('success'))
    <div class="fixed top-20 right-4 left-4 md:left-auto md:max-w-sm z-50 bg-green-500 text-white px-5 py-3 rounded-lg shadow-xl flex items-center space-x-3 fade-in" x-data x-init="setTimeout(() => $el.remove(), 4000)">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span class="text-sm font-medium">{{ session('success') }}</span>
    </div>
@endif
@if(session('error'))
    <div class="fixed top-20 right-4 left-4 md:left-auto md:max-w-sm z-50 bg-red-500 text-white px-5 py-3 rounded-lg shadow-xl flex items-center space-x-3 fade-in" x-data x-init="setTimeout(() => $el.remove(), 4000)">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        <span class="text-sm font-medium">{{ session('error') }}</span>
    </div>
@endif

<main>
    @yield('content')
</main>

{{-- Footer --}}
<footer class="bg-gray-900 text-gray-300 mt-0">
    @php
    $footerLogo  = setting_file_url('footer_logo', $logoUrl);
    $fbUrl       = setting('facebook_url', '');
    $ytUrl       = setting('youtube_url', '');
    $igUrl       = setting('instagram_url', '');
    $twUrl       = setting('twitter_url', '');
    $tkUrl       = setting('tiktok_url', '');
    $liUrl       = setting('linkedin_url', '');
    $ptUrl       = setting('pinterest_url', '');
    $msgUrl      = setting('messenger_link', '');
    $waUrl       = setting('whatsapp_link', '');
    $shopUrl     = setting('nav_shop_url') ?: route('shop.index');
    $blogUrl     = setting('nav_blog_url') ?: route('blog.index');
    $contactUrl  = setting('nav_contact_url') ?: route('contact');
    $aboutUrl    = setting('nav_about_url') ?: ((int) setting('about_page_id', 0) || \App\Models\Page::where('slug', 'about-us')->active()->exists() ? route('about') : null);
    $termsUrl    = (int) setting('terms_page_id', 0) || \App\Models\Page::where('slug', 'terms-conditions')->active()->exists() ? route('terms') : null;
    $privacyUrl  = (int) setting('privacy_page_id', 0) || \App\Models\Page::where('slug', 'privacy-policy')->active()->exists() ? route('privacy') : null;
    $copyright   = setting('copyright_text')
        ? str_replace('{year}', date('Y'), setting('copyright_text'))
        : t('footer.copyright_default', '© :year :site. All rights reserved.', ['year' => date('Y'), 'site' => $siteName], 'footer');
    @endphp

    {{-- Newsletter --}}
    <div class="bg-gray-800 border-b border-gray-700">
        <div class="max-w-[1200px] mx-auto px-4 py-8">
            <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                <div>
                    <h3 class="text-white text-lg font-bold">{{ t('footer.newsletter_title', 'Subscribe to Our Newsletter', [], 'footer') }}</h3>
                    <p class="text-gray-400 text-sm mt-1">{{ t('footer.newsletter_subtitle', 'Get updates on new arrivals, deals, and exclusive offers.', [], 'footer') }}</p>
                </div>
                <div class="w-full md:w-auto">
                    <form action="{{ route('newsletter.subscribe') }}" method="POST" class="flex w-full md:w-auto">
                        @csrf
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="{{ t('footer.email_placeholder', 'Enter your email', [], 'footer') }}" required class="flex-1 md:w-72 px-4 py-2.5 rounded-l-lg bg-gray-700 text-white border border-gray-600 focus:outline-none focus:border-orange-500 text-sm placeholder-gray-500">
                        <button type="submit" class="bg-orange-500 text-white px-6 py-2.5 rounded-r-lg font-medium text-sm hover:bg-orange-600 transition whitespace-nowrap">{{ t('footer.subscribe', 'Subscribe', [], 'footer') }}</button>
                    </form>
                    @error('email')<p class="text-red-400 text-xs mt-1.5">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-[1200px] mx-auto px-4 py-10">
        <div class="grid grid-cols-2 md:grid-cols-5 gap-8">
            {{-- Brand --}}
            <div class="col-span-2 md:col-span-1">
                <div class="flex items-center space-x-2 mb-4">
                    @if($footerLogo)
                        <img src="{{ $footerLogo }}" alt="{{ $siteName }}" class="h-8 max-w-[120px] object-contain">
                    @else
                        <div class="w-8 h-8 bg-gradient-to-br from-orange-500 to-red-500 rounded-lg flex items-center justify-center">
                            <span class="text-white font-bold">{{ strtoupper(substr($siteName,0,1)) }}</span>
                        </div>
                        <span class="text-white text-lg font-bold">{{ $siteName }}</span>
                    @endif
                </div>
                <p class="text-sm text-gray-400 mb-4 leading-relaxed">{{ $siteTagline }}</p>
                {{-- Payment Methods --}}
                <div class="flex items-center gap-2 mb-4">
                    <span class="text-xs text-gray-500">{{ t('footer.we_accept', 'We accept:', [], 'footer') }}</span>
                </div>
                <div class="flex flex-wrap gap-1.5">
                    @foreach(['Visa', 'Mastercard', 'bKash', 'Nagad', 'COD'] as $method)
                        <span class="bg-gray-800 text-gray-400 text-[10px] px-2 py-1 rounded font-medium border border-gray-700">{{ $method }}</span>
                    @endforeach
                </div>
            </div>

            {{-- Quick Links --}}
            <div>
                <h4 class="text-white font-bold mb-4 text-sm">{{ setting('footer_col2_title') ?: t('footer.quick_links_default', 'Quick Links', [], 'footer') }}</h4>
                <ul class="space-y-2.5 text-sm">
                    <li><a href="{{ route('home') }}" class="hover:text-orange-400 transition">{{ t('footer.home', 'Home', [], 'footer') }}</a></li>
                    <li><a href="{{ $shopUrl }}" class="hover:text-orange-400 transition">{{ t('footer.shop', 'Shop', [], 'footer') }}</a></li>
                    <li><a href="{{ $blogUrl }}" class="hover:text-orange-400 transition">{{ t('footer.blog', 'Blog', [], 'footer') }}</a></li>
                    @if($aboutUrl)<li><a href="{{ $aboutUrl }}" class="hover:text-orange-400 transition">{{ t('footer.about_us', 'About Us', [], 'footer') }}</a></li>@endif
                    <li><a href="{{ route('vendor.apply') }}" class="hover:text-orange-400 transition">{{ t('footer.sell_on', 'Sell on :site', ['site' => $siteName], 'footer') }}</a></li>
                </ul>
            </div>

            {{-- Customer Service --}}
            <div>
                <h4 class="text-white font-bold mb-4 text-sm">{{ setting('footer_col3_title') ?: t('footer.customer_service_default', 'Customer Service', [], 'footer') }}</h4>
                <ul class="space-y-2.5 text-sm">
                    <li><a href="{{ $contactUrl }}" class="hover:text-orange-400 transition">{{ t('footer.contact_us', 'Contact Us', [], 'footer') }}</a></li>
                    <li><a href="{{ route('faq') }}" class="hover:text-orange-400 transition">{{ t('footer.faq', 'FAQ', [], 'footer') }}</a></li>
                    <li><a href="{{ route('guest.order.track.form') }}" class="hover:text-orange-400 transition">{{ t('footer.track_order', 'Track Order', [], 'footer') }}</a></li>
                    <li><a href="{{ route('pages.show', 'return-policy') }}" class="hover:text-orange-400 transition">{{ t('footer.return_policy', 'Return Policy', [], 'footer') }}</a></li>
                    @if($termsUrl)<li><a href="{{ $termsUrl }}" class="hover:text-orange-400 transition">{{ t('footer.terms', 'Terms & Conditions', [], 'footer') }}</a></li>@endif
                    @if($privacyUrl)<li><a href="{{ $privacyUrl }}" class="hover:text-orange-400 transition">{{ t('footer.privacy', 'Privacy Policy', [], 'footer') }}</a></li>@endif
                </ul>
            </div>

            {{-- Contact --}}
            <div>
                <h4 class="text-white font-bold mb-4 text-sm">{{ setting('footer_col4_title') ?: t('footer.contact_default', 'Contact', [], 'footer') }}</h4>
                <ul class="space-y-2.5 text-sm text-gray-400">
                    @if(setting('company_address'))
                    <li class="flex items-start gap-2">
                        <svg class="w-4 h-4 mt-0.5 flex-shrink-0 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        {{ setting('company_address') }}
                    </li>
                    @endif
                    @if(setting('company_phone'))
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 flex-shrink-0 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', setting('company_phone')) }}" class="hover:text-orange-400 transition">{{ setting('company_phone') }}</a>
                    </li>
                    @endif
                    @php $contactEmail = setting('support_email') ?: setting('company_email'); @endphp
                    @if($contactEmail)
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 flex-shrink-0 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <a href="mailto:{{ $contactEmail }}" class="hover:text-orange-400 transition">{{ $contactEmail }}</a>
                    </li>
                    @endif
                </ul>

                @if($fbUrl || $igUrl || $ytUrl || $twUrl || $waUrl || $msgUrl || $tkUrl || $liUrl || $ptUrl)
                <div class="flex flex-wrap gap-2 mt-4">
                    @if($fbUrl)<a href="{{ $fbUrl }}" target="_blank" rel="noopener" class="text-gray-500 hover:text-blue-400 transition" title="Facebook"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/></svg></a>@endif
                    @if($igUrl)<a href="{{ $igUrl }}" target="_blank" rel="noopener" class="text-gray-500 hover:text-pink-400 transition" title="Instagram"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg></a>@endif
                    @if($ytUrl)<a href="{{ $ytUrl }}" target="_blank" rel="noopener" class="text-gray-500 hover:text-red-400 transition" title="YouTube"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg></a>@endif
                    @if($twUrl)<a href="{{ $twUrl }}" target="_blank" rel="noopener" class="text-gray-500 hover:text-gray-200 transition" title="X"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg></a>@endif
                    @if($waUrl)<a href="{{ $waUrl }}" target="_blank" rel="noopener" class="text-gray-500 hover:text-green-400 transition" title="WhatsApp"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg></a>@endif
                    @if($msgUrl)<a href="{{ $msgUrl }}" target="_blank" rel="noopener" class="text-gray-500 hover:text-blue-400 transition" title="Messenger"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.373 0 0 4.974 0 11.111c0 3.498 1.744 6.614 4.469 8.652V24l4.088-2.242c1.092.301 2.246.464 3.443.464 6.627 0 12-4.975 12-11.111C24 4.974 18.627 0 12 0zm1.191 14.963l-3.055-3.26-5.963 3.26L10.732 8l3.131 3.259L19.752 8l-6.561 6.963z"/></svg></a>@endif
                    @if($tkUrl)<a href="{{ $tkUrl }}" target="_blank" rel="noopener" class="text-gray-500 hover:text-gray-200 transition" title="TikTok"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.36-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg></a>@endif
                    @if($liUrl)<a href="{{ $liUrl }}" target="_blank" rel="noopener" class="text-gray-500 hover:text-blue-500 transition" title="LinkedIn"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg></a>@endif
                    @if($ptUrl)<a href="{{ $ptUrl }}" target="_blank" rel="noopener" class="text-gray-500 hover:text-red-500 transition" title="Pinterest"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.162-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.663.967-2.911 2.171-2.911 1.023 0 1.518.769 1.518 1.688 0 1.029-.653 2.567-.992 3.992-.285 1.193.6 2.165 1.775 2.165 2.128 0 3.768-2.245 3.768-5.487 0-2.861-2.063-4.869-5.008-4.869-3.41 0-5.409 2.562-5.409 5.199 0 1.033.394 2.143.889 2.741.099.12.112.225.085.345-.09.375-.293 1.199-.334 1.363-.053.225-.172.271-.402.165-1.495-.69-2.433-2.878-2.433-4.646 0-3.776 2.748-7.252 7.92-7.252 4.158 0 7.392 2.967 7.392 6.923 0 4.135-2.607 7.462-6.233 7.462-1.214 0-2.357-.629-2.75-1.378l-.748 2.853c-.271 1.043-1.004 2.352-1.494 3.146 1.126.345 2.317.535 3.554.535 6.607 0 11.985-5.365 11.985-11.987C23.97 5.39 18.592.026 11.985.026L12.017 0z"/></svg></a>@endif
                </div>
                @endif
            </div>
        </div>

        <div class="border-t border-gray-800 mt-8 pt-6 text-center text-xs text-gray-500">
            {!! $copyright !!}
        </div>
    </div>
</footer>

{{-- Back to Top --}}
<div x-data="{ show: false }" @scroll.window="show = window.scrollY > 400"
     x-show="show" x-cloak x-transition
     class="fixed bottom-6 right-6 z-40">
    <button onclick="window.scrollTo({top:0,behavior:'smooth'})" class="w-11 h-11 bg-orange-500 text-white rounded-full shadow-lg hover:bg-orange-600 transition flex items-center justify-center">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/></svg>
    </button>
</div>

{{-- Wishlist AJAX --}}
<script>
async function toggleWishlist(productId, btn) {
    try {
        const res = await fetch('/wishlist/toggle/' + productId, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });
        const data = await res.json();
        if (data.status === 'added') {
            btn.classList.add('text-red-500');
            btn.classList.remove('text-gray-400');
            btn.querySelector('svg').setAttribute('fill', 'currentColor');
        } else {
            btn.classList.remove('text-red-500');
            btn.classList.add('text-gray-400');
            btn.querySelector('svg').setAttribute('fill', 'none');
        }
    } catch (e) {
        window.location.href = '/login';
    }
}
</script>

@php $customJs = setting('custom_js', ''); @endphp
@if($customJs)<script>{{ $customJs }}</script>@endif
</body>
</html>
