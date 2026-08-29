@php
$siteName     = setting('site_name', 'ShopVista');
$siteTagline  = setting('site_tagline', 'Your one-stop shop for everything you need.');
$logoUrl      = setting_file_url('site_logo');
$logoMobileUrl = setting_file_url('site_logo_mobile');
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
$adsConversionId = setting('google_ads_conversion_id', '');
$googleEnhancedOn = setting('google_enhanced_conversions_enabled', '0') == '1';
$pixelId      = setting('facebook_pixel_id', '');
$pixelOn      = $pixelId && setting('facebook_pixel_enabled', $pixelId ? '1' : '0') == '1';
$fbAdvancedMatchingOn = setting('facebook_advanced_matching_enabled', '0') == '1';
$customCss    = setting('custom_css', '');

// Site-wide default for Advanced Matching / Enhanced Conversions: whatever we
// know about the logged-in visitor. checkout/success.blade.php refreshes this
// with the order's own (often more complete, and the only source for guest
// checkouts) shipping details right before the Purchase event fires.
$trackedUser = auth()->check() ? auth()->user() : null;
$trackingData = $trackedUser ? pixel_advanced_matching_data($trackedUser->name, $trackedUser->email, $trackedUser->phone) : ['fb' => [], 'google' => []];

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

// Every shadow SIZE the storefront actually uses (not just shadow-sm) gets a value per
// intensity level, so picking "None"/"Strong" here visibly changes dropdowns, hero images,
// and the sticky header too — not only the one class product cards happen to rest on.
// 'soft' (the default) intentionally stays null/uncovered — that's Tailwind's own untouched
// shadow scale, i.e. the site's current hand-tuned look, pixel-identical unless changed.
$shadowScale = [
    'none' => [
        'shadow-sm' => 'none', 'shadow' => 'none', 'shadow-md' => 'none',
        'shadow-lg' => 'none', 'shadow-xl' => 'none', 'shadow-2xl' => 'none',
    ],
    'soft' => null,
    'medium' => [
        'shadow-sm' => '0 2px 4px 0 rgb(0 0 0 / 0.08)',
        'shadow'    => '0 4px 8px -1px rgb(0 0 0 / 0.12), 0 2px 4px -2px rgb(0 0 0 / 0.08)',
        'shadow-md' => '0 6px 10px -2px rgb(0 0 0 / 0.12), 0 3px 5px -3px rgb(0 0 0 / 0.08)',
        'shadow-lg' => '0 12px 18px -4px rgb(0 0 0 / 0.14), 0 5px 8px -5px rgb(0 0 0 / 0.1)',
        'shadow-xl' => '0 22px 30px -8px rgb(0 0 0 / 0.16), 0 10px 12px -8px rgb(0 0 0 / 0.1)',
        'shadow-2xl'=> '0 30px 45px -10px rgb(0 0 0 / 0.2)',
    ],
    'strong' => [
        'shadow-sm' => '0 3px 6px 0 rgb(0 0 0 / 0.12)',
        'shadow'    => '0 6px 12px -1px rgb(0 0 0 / 0.18), 0 3px 6px -2px rgb(0 0 0 / 0.12)',
        'shadow-md' => '0 10px 15px -3px rgb(0 0 0 / 0.18), 0 4px 6px -4px rgb(0 0 0 / 0.12)',
        'shadow-lg' => '0 20px 25px -5px rgb(0 0 0 / 0.2), 0 8px 10px -6px rgb(0 0 0 / 0.14)',
        'shadow-xl' => '0 30px 40px -8px rgb(0 0 0 / 0.24), 0 12px 16px -8px rgb(0 0 0 / 0.15)',
        'shadow-2xl'=> '0 35px 60px -12px rgb(0 0 0 / 0.3)',
    ],
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

    {{-- PWA — lets a phone browser offer "Add to Home Screen" and, once installed, launch
         full-screen under the store's own name/icon/color instead of inside browser chrome. --}}
    <link rel="manifest" href="{{ route('manifest') }}">
    <meta name="theme-color" content="{{ $primaryColor }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="{{ $siteName }}">
    @if($faviconUrl)<link rel="apple-touch-icon" href="{{ $faviconUrl }}">@endif
    @if(!$isSystemFont)
    @php $fontHref = 'https://fonts.googleapis.com/css2?family=' . str_replace(' ', '+', $googleFontName) . ':wght@400;500;600;700;800&display=swap'; @endphp
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    {{-- Non-render-blocking: loads with low priority (media="print" isn't relevant to a
         screen render, so the browser doesn't wait on it), then swaps to apply once ready.
         display=swap in the URL already means fallback-font text shows immediately either
         way — this just stops the font request itself from delaying first paint. --}}
    <link rel="stylesheet" href="{{ $fontHref }}" media="print" onload="this.media='all'; this.onload=null;">
    <noscript><link rel="stylesheet" href="{{ $fontHref }}"></noscript>
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
    @vite(['resources/css/app.css', 'resources/js/app.js'])
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
        @foreach($shadowScale as $shadowClass => $shadowValue)
        .{{ $shadowClass }} { box-shadow: {{ $shadowValue }} !important; }
        .hover\:{{ $shadowClass }}:hover { box-shadow: {{ $shadowValue }} !important; }
        @endforeach
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
        {{-- The pink→fuchsia→orange gradient "Buy Now"/"Select Options" quick-action button
             (partials.product-card, used on every product listing sitewide) has no plain
             bg-{color}-{step} class for the loop above to match — it's brand-colored too,
             just via a gradient, so it gets its own explicit selector here. --}}
        @foreach($buttonRadiusClasses as $radiusClass)
        .bg-gradient-to-r.from-pink-500.via-fuchsia-500.to-orange-400.{{ $radiusClass }} { border-radius: {{ $buttonRadiusValue }} !important; }
        @endforeach
        @endif
    </style>
    @endif
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

        /* ── Scroll-reveal system ────────────────────────────────────────────
           `.reveal` fades/slides a single element in once it enters the
           viewport; `.reveal-group` does the same to each of its direct
           children with a staggered delay (nth-child, capped at 10 — a grid
           with more items than that just has its tail arrive together with
           the 10th, which reads fine since they're already close together).
           JS (resources/js/scroll-reveal.js) does only one job: add
           `.is-visible` the first time each `.reveal`/`.reveal-group` enters
           view. Everything else — timing, distance, stagger — lives here so
           designers can retune it without touching JS. */
        .reveal{opacity:0;transform:translateY(22px);transition:opacity .6s cubic-bezier(.22,1,.36,1),transform .6s cubic-bezier(.22,1,.36,1)}
        .reveal.is-visible{opacity:1;transform:translateY(0)}
        .reveal-group > *{opacity:0;transform:translateY(18px);transition:opacity .5s cubic-bezier(.22,1,.36,1),transform .5s cubic-bezier(.22,1,.36,1)}
        .reveal-group.is-visible > *{opacity:1;transform:translateY(0)}
        @for ($i = 1; $i <= 10; $i++)
        .reveal-group.is-visible > *:nth-child({{ $i }}){transition-delay:{{ $i * 0.06 }}s}
        @endfor
        @media (prefers-reduced-motion: reduce){
            .reveal,.reveal-group > *{opacity:1!important;transform:none!important;transition:none!important}
        }

        /* Hero content's one-shot entrance on first paint — no observer needed,
           it's above the fold so "scrolled into view" is immediate anyway. */
        @keyframes fadeInUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
        .animate-fade-in-up{animation:fadeInUp .7s cubic-bezier(.22,1,.36,1) both}

        /* Slow-panning gradient for the default (no-banner-configured) hero — used via
           Tailwind's arbitrary `animate-[gradientPan_8s_ease_infinite]` on home.blade.php,
           which only emits the `animation:` declaration and expects this keyframe to
           already exist (Tailwind doesn't generate @keyframes from arbitrary values). */
        @keyframes gradientPan{0%,100%{background-position:0% 50%}50%{background-position:100% 50%}}
        @media (prefers-reduced-motion: reduce){ .animate-\[gradientPan_8s_ease_infinite\]{animation:none} }

        /* Soft breathing glow for the one hero CTA that should feel "alive"
           without every button on the page competing for attention. */
        @keyframes ctaGlow{0%,100%{box-shadow:0 0 0 0 rgba(255,255,255,.55)}50%{box-shadow:0 0 0 8px rgba(255,255,255,0)}}
        .btn-glow{animation:ctaGlow 2.2s ease-out infinite}
        @media (prefers-reduced-motion: reduce){ .btn-glow{animation:none} }

        /* Shared "acknowledged" micro-interaction for the wishlist heart and quick-add-
           to-cart button — a quick overshoot pop on activation rather than a flat color
           swap, so the click feels registered. */
        @keyframes popBounce{0%{transform:scale(1)}30%{transform:scale(1.35)}55%{transform:scale(.9)}100%{transform:scale(1)}}
        .pop-bounce{animation:popBounce .45s ease-out}
        @media (prefers-reduced-motion: reduce){ .pop-bounce{animation:none} }

        /* showToast() entrance/exit — slides in from the right and fades, reverses
           on the way out (class added by the same setTimeout that schedules removal). */
        @keyframes toastIn{from{opacity:0;transform:translateX(24px)}to{opacity:1;transform:translateX(0)}}
        @keyframes toastOut{from{opacity:1;transform:translateX(0)}to{opacity:0;transform:translateX(24px)}}
        .toast-pop{animation:toastIn .35s cubic-bezier(.22,1,.36,1) both}
        .toast-pop-out{animation:toastOut .25s ease-in both}
        @media (prefers-reduced-motion: reduce){ .toast-pop,.toast-pop-out{animation:none} }

        /* Tailwind's built-in animate-ping/animate-bounce/animate-pulse don't come with a
           reduced-motion guard out of the box — added once here so every use of them
           site-wide (the floating contact widget included) respects the preference. */
        @media (prefers-reduced-motion: reduce){
            .animate-ping,.animate-bounce,.animate-pulse{animation:none!important}
        }
    </style>
    {{-- Raw, not escaped — this is admin-entered CSS source, not user content. {{ }} would
         HTML-entity-escape every quote (font-family: 'Georgia' → &#039;Georgia&#039;), which
         a <style> tag never decodes back — silently corrupting any real-world CSS that uses
         quotes at all (which is most of it: quoted font names, content:"", url("...")). --}}
    @if($customCss)<style>{!! $customCss !!}</style>@endif
    @if($gaId || $adsConversionId)
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaId ?: $adsConversionId }}"></script>
    <script>
        window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());
        @if($gaId)gtag('config','{{ $gaId }}');@endif
        @if($adsConversionId)gtag('config','{{ $adsConversionId }}');@endif
        @if($googleEnhancedOn && $trackingData['google'])gtag('set','user_data',{!! Js::from($trackingData['google']) !!});@endif
    </script>
    @endif
    @if($gtmId)
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','{{ $gtmId }}');</script>
    @endif
    @if($pixelOn)
    <script>
        !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
        fbq('init','{{ $pixelId }}'@if($fbAdvancedMatchingOn && $trackingData['fb']), {!! Js::from($trackingData['fb']) !!}@endif);
        fbq('track','PageView');
    </script>
    @endif
</head>
<body class="bg-gray-100 dark:bg-gray-950 font-sans antialiased transition-colors pb-[calc(4rem_+_env(safe-area-inset-bottom))] md:pb-0">
@if($gtmId)<noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtmId }}" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>@endif

@php
$navCategories = \App\Models\Category::with(['children' => fn($q) => $q->active()->orderBy('sort_order')])
    ->whereNull('parent_id')->active()->orderBy('sort_order')->take(12)->get();
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
            <div class="flex items-center justify-between h-16 md:h-20 gap-4">
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
    <div class="bg-orange-700 hidden md:block border-t border-orange-600">
        <div class="max-w-[1200px] mx-auto px-4 flex items-center overflow-x-auto scrollbar-hide">
            @foreach($navCategories as $navCat)
                <div class="relative flex-shrink-0" x-data="{ open: false, top: 0, left: 0 }"
                     @mouseenter="open = true; const r = $el.getBoundingClientRect(); top = r.bottom; left = r.left;"
                     @mouseleave="open = false">
                    <a href="{{ route('shop.category', $navCat->slug) }}"
                       class="inline-flex items-center gap-1 text-sm text-white whitespace-nowrap hover:bg-orange-800 px-3 py-2.5 transition font-medium">
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
            <a href="{{ route('shop.index') }}" class="inline-flex items-center text-sm text-white whitespace-nowrap hover:text-white px-3 py-2.5 ml-auto font-medium transition">{{ t('header.all_products', 'All Products', [], 'header') }} →</a>
        </div>
    </div>
    @endif

    {{-- Mobile Menu --}}
    <div x-show="mobileOpen" x-cloak x-transition @click.outside="mobileOpen = false" @keydown.escape.window="mobileOpen = false" class="md:hidden bg-white dark:bg-gray-900 border-t border-gray-100 dark:border-gray-700 shadow-xl">
        <div class="p-4">
            {{-- Lazy-mounted (x-if, not x-show) — this has its own x-data/fetch/debounce
                 wiring, so this way Alpine never sets any of that up on page load for the
                 ~most visitors who never open the mobile menu; only when mobileOpen flips
                 true does it actually get built. --}}
            <template x-if="mobileOpen">
            <div class="relative mb-4" x-data="{
                    query: '{{ addslashes(request('search', '')) }}',
                    results: { products: [], categories: [] },
                    open: false,
                    async fetchSuggestions() {
                        if (this.query.length < 2) { this.open = false; return; }
                        try {
                            const res = await fetch('/search/suggest?q=' + encodeURIComponent(this.query));
                            this.results = await res.json();
                            this.open = this.results.products.length > 0 || this.results.categories.length > 0;
                        } catch (e) {}
                    }
                }" @click.outside="open = false">
                <form action="{{ route('shop.index') }}" method="GET" class="flex" @submit="open = false">
                    <input type="text" name="search" x-ref="mobileSearchInput" x-model="query"
                        @input.debounce.300ms="fetchSuggestions()"
                        @focus="query.length > 1 && fetchSuggestions()"
                        @keydown.escape="open = false"
                        placeholder="Search products..." aria-label="Search products" autocomplete="off"
                        class="flex-1 border-2 border-orange-400 rounded-l-md px-4 py-2 text-sm focus:outline-none focus:border-orange-500">
                    <button type="submit" aria-label="Search" class="bg-orange-500 text-white px-4 py-2 rounded-r-md flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </button>
                </form>
                {{-- Auto-suggest dropdown — same /search/suggest endpoint the desktop search bar uses --}}
                <div x-show="open" x-cloak x-transition
                     class="absolute top-full left-0 right-0 bg-white dark:bg-gray-800 rounded-b-lg shadow-2xl border border-gray-100 dark:border-gray-700 z-[200] overflow-hidden fade-in max-h-80 overflow-y-auto">
                    <template x-if="results.categories && results.categories.length > 0">
                        <div class="border-b border-gray-100 dark:border-gray-700">
                            <p class="px-4 pt-3 pb-1 text-[10px] font-bold text-orange-400 uppercase tracking-wider">{{ t('header.categories', 'Categories', [], 'header') }}</p>
                            <template x-for="cat in results.categories" :key="cat.url">
                                <a :href="cat.url" @click="open = false; mobileOpen = false"
                                   class="flex items-center px-4 py-2 hover:bg-orange-50 dark:hover:bg-gray-700 gap-2 text-sm text-gray-700 dark:text-gray-200 hover:text-orange-600 transition">
                                    <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                    <span x-text="cat.name"></span>
                                </a>
                            </template>
                        </div>
                    </template>
                    <template x-if="results.products && results.products.length > 0">
                        <div>
                            <p class="px-4 pt-3 pb-1 text-[10px] font-bold text-orange-400 uppercase tracking-wider">{{ t('header.products', 'Products', [], 'header') }}</p>
                            <template x-for="product in results.products" :key="product.url">
                                <a :href="product.url" @click="open = false; mobileOpen = false"
                                   class="flex items-center px-4 py-2.5 hover:bg-orange-50 dark:hover:bg-gray-700 gap-3 transition">
                                    <div class="w-10 h-10 bg-gray-100 rounded overflow-hidden flex-shrink-0 flex items-center justify-center">
                                        <img x-show="product.image" :src="product.image" :alt="product.name" class="w-full h-full object-cover">
                                        <svg x-show="!product.image" class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-800 dark:text-gray-100 truncate" x-text="product.name"></p>
                                        <p class="text-xs font-bold text-orange-700" x-text="product.price"></p>
                                    </div>
                                </a>
                            </template>
                            <div class="px-4 py-2.5 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                                <a :href="'{{ route('shop.index') }}?search=' + encodeURIComponent(query)" @click="mobileOpen = false"
                                   class="text-xs text-orange-700 hover:text-orange-800 font-semibold">
                                    {{ t('header.see_all_results', 'See all results for', [], 'header') }} "<span x-text="query"></span>" &rarr;
                                </a>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            </template>
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
                    $mobileCategories = \App\Models\Category::whereNull('parent_id')->active()->withCount('products')->orderBy('sort_order')->limit(8)->get();
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
                            <a href="{{ route('shop.index') }}" class="block px-3 py-2 text-xs text-orange-700 font-medium hover:bg-orange-50 dark:hover:bg-gray-800 rounded-lg transition">{{ t('header.view_all_categories', 'View All Categories', [], 'header') }} →</a>
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

{{-- JS-driven toasts (showToast() below) — added-to-cart/wishlist confirmations that
     fire from an AJAX response, not a page load, so they can't be server-rendered
     session flashes like the two blocks above. Same fixed position/styling, stacked
     in a column so several in quick succession don't overlap. --}}
<div id="toast-stack" class="fixed top-20 right-4 left-4 md:left-auto md:max-w-sm z-50 flex flex-col gap-2 pointer-events-none"></div>

{{-- ═══════════ FLOATING CONTACT WIDGET (admin: Settings → Social Media) ═══════════
     One button per channel, each shown only when both its link is filled in AND its
     own floating_{channel}_enabled toggle is on — independent per icon, so the admin
     can run any subset of the four rather than all-or-nothing. The pinging ring is
     Tailwind's stock animate-ping (no custom keyframes needed); reduced-motion users
     get it switched off site-wide via the rule in the <style> block above. --}}
@if(setting('floating_widget_enabled', '0') == '1')
    @php
        $floatingContacts = array_filter([
            [
                'url' => setting('whatsapp_link', ''),
                'enabled' => setting('floating_whatsapp_enabled', '1') == '1',
                'label' => 'Chat on WhatsApp',
                'bg' => '#25D366',
                'icon' => '<path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.198-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12.004 2C6.486 2 2.01 6.477 2.01 11.994c0 1.885.522 3.647 1.428 5.153L2 22l4.965-1.404a9.958 9.958 0 004.988 1.334h.004c5.518 0 9.994-4.477 9.994-9.994C21.951 6.477 17.522 2 12.004 2zm0 18.032a8.03 8.03 0 01-4.393-1.315l-.316-.194-3.09.874.85-3.01-.207-.31a8.017 8.017 0 01-1.293-4.383c0-4.43 3.607-8.036 8.045-8.036 2.148 0 4.166.838 5.684 2.359a7.981 7.981 0 012.36 5.677c0 4.43-3.608 8.038-8.04 8.038z"/>',
            ],
            [
                'url' => setting('telegram_link', ''),
                'enabled' => setting('floating_telegram_enabled', '1') == '1',
                'label' => 'Message on Telegram',
                'bg' => '#26A5E4',
                'icon' => '<path d="M21.94 5.36l-3.16 14.9c-.24 1.05-.86 1.31-1.74.82l-4.81-3.55-2.32 2.24c-.26.26-.47.47-.96.47l.34-4.87 8.86-8.01c.39-.34-.08-.53-.6-.19L6.4 12.86l-4.8-1.5c-1.04-.33-1.06-1.04.22-1.53L20.6 4.02c.87-.32 1.63.2 1.34 1.34z"/>',
            ],
            [
                'url' => setting('messenger_link', ''),
                'enabled' => setting('floating_messenger_enabled', '1') == '1',
                'label' => 'Message on Messenger',
                'bg' => '#00B2FF',
                'icon' => '<path d="M12 2C6.477 2 2 6.145 2 11.259c0 2.913 1.454 5.512 3.726 7.21V22l3.405-1.87c.909.251 1.871.387 2.869.387 5.523 0 10-4.145 10-9.258C22 6.145 17.523 2 12 2zm1.008 12.461l-2.548-2.72-4.97 2.72 5.467-5.803 2.61 2.72 4.907-2.72-5.466 5.803z"/>',
            ],
            [
                'url' => setting('linkedin_url', ''),
                'enabled' => setting('floating_linkedin_enabled', '1') == '1',
                'label' => 'Connect on LinkedIn',
                'bg' => '#0A66C2',
                'icon' => '<path d="M6.94 5a2 2 0 11-4-.002 2 2 0 014 .002zM7 8.48H3V21h4V8.48zm6.32 0H9.34V21h3.94v-6.57c0-3.66 4.77-3.96 4.77 0V21H22v-7.93c0-6.17-7.06-5.94-8.68-2.91V8.48z"/>',
            ],
        ], fn ($c) => !empty($c['url']) && $c['enabled']);
    @endphp
    @if(!empty($floatingContacts))
        {{-- bottom-20 (not bottom-4) on mobile — partials.storefront.bottom-nav is a fixed
             full-width bar pinned to the very bottom on small screens (md:hidden), so
             anything closer than that overlaps it. Desktop has no such bar. --}}
        <div class="fixed left-3 md:left-4 bottom-20 md:bottom-6 z-40 flex flex-col gap-3">
            @foreach($floatingContacts as $contact)
                <a href="{{ $contact['url'] }}" target="_blank" rel="noopener" aria-label="{{ $contact['label'] }}" title="{{ $contact['label'] }}"
                   class="relative w-11 h-11 md:w-12 md:h-12 rounded-full text-white shadow-lg flex items-center justify-center hover:scale-110 transition-transform duration-200"
                   style="background-color: {{ $contact['bg'] }}">
                    <span class="absolute inset-0 rounded-full animate-ping opacity-75" style="background-color: {{ $contact['bg'] }}" aria-hidden="true"></span>
                    <svg class="relative w-5 h-5 md:w-6 md:h-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">{!! $contact['icon'] !!}</svg>
                </a>
            @endforeach
        </div>
    @endif
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
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="{{ t('footer.email_placeholder', 'Enter your email', [], 'footer') }}" aria-label="{{ t('footer.email_placeholder', 'Enter your email', [], 'footer') }}" required class="flex-1 md:w-72 px-4 py-2.5 rounded-l-lg bg-gray-700 text-white border border-gray-600 focus:outline-none focus:border-orange-500 text-sm placeholder-gray-500">
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
                <div class="flex flex-wrap items-center gap-2">
                    @php $hasPaymentIcons = false; @endphp
                    @for($i = 1; $i <= 8; $i++)
                        @if(setting("payment_icon_{$i}"))
                            @php $hasPaymentIcons = true; @endphp
                            <img src="{{ setting_file_url("payment_icon_{$i}") }}" alt="Payment method"
                                 class="h-6 max-w-[60px] object-contain rounded bg-white/10 px-1">
                        @endif
                    @endfor
                    @if(!$hasPaymentIcons)
                        @foreach(['Visa', 'Mastercard', 'bKash', 'Nagad', 'COD'] as $method)
                            <span class="bg-gray-800 text-gray-400 text-[10px] px-2 py-1 rounded font-medium border border-gray-700">{{ $method }}</span>
                        @endforeach
                    @endif
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
                    @if($fbUrl)<a href="{{ $fbUrl }}" target="_blank" rel="noopener" class="text-gray-500 hover:text-blue-400 transition" title="Facebook" aria-label="Facebook"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/></svg></a>@endif
                    @if($igUrl)<a href="{{ $igUrl }}" target="_blank" rel="noopener" class="text-gray-500 hover:text-pink-400 transition" title="Instagram" aria-label="Instagram"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg></a>@endif
                    @if($ytUrl)<a href="{{ $ytUrl }}" target="_blank" rel="noopener" class="text-gray-500 hover:text-red-400 transition" title="YouTube" aria-label="YouTube"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg></a>@endif
                    @if($twUrl)<a href="{{ $twUrl }}" target="_blank" rel="noopener" class="text-gray-500 hover:text-gray-200 transition" title="X" aria-label="X (Twitter)"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg></a>@endif
                    @if($waUrl)<a href="{{ $waUrl }}" target="_blank" rel="noopener" class="text-gray-500 hover:text-green-400 transition" title="WhatsApp" aria-label="WhatsApp"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg></a>@endif
                    @if($msgUrl)<a href="{{ $msgUrl }}" target="_blank" rel="noopener" class="text-gray-500 hover:text-blue-400 transition" title="Messenger" aria-label="Messenger"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.373 0 0 4.974 0 11.111c0 3.498 1.744 6.614 4.469 8.652V24l4.088-2.242c1.092.301 2.246.464 3.443.464 6.627 0 12-4.975 12-11.111C24 4.974 18.627 0 12 0zm1.191 14.963l-3.055-3.26-5.963 3.26L10.732 8l3.131 3.259L19.752 8l-6.561 6.963z"/></svg></a>@endif
                    @if($tkUrl)<a href="{{ $tkUrl }}" target="_blank" rel="noopener" class="text-gray-500 hover:text-gray-200 transition" title="TikTok" aria-label="TikTok"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.36-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg></a>@endif
                    @if($liUrl)<a href="{{ $liUrl }}" target="_blank" rel="noopener" class="text-gray-500 hover:text-blue-500 transition" title="LinkedIn" aria-label="LinkedIn"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg></a>@endif
                    @if($ptUrl)<a href="{{ $ptUrl }}" target="_blank" rel="noopener" class="text-gray-500 hover:text-red-500 transition" title="Pinterest" aria-label="Pinterest"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.162-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.663.967-2.911 2.171-2.911 1.023 0 1.518.769 1.518 1.688 0 1.029-.653 2.567-.992 3.992-.285 1.193.6 2.165 1.775 2.165 2.128 0 3.768-2.245 3.768-5.487 0-2.861-2.063-4.869-5.008-4.869-3.41 0-5.409 2.562-5.409 5.199 0 1.033.394 2.143.889 2.741.099.12.112.225.085.345-.09.375-.293 1.199-.334 1.363-.053.225-.172.271-.402.165-1.495-.69-2.433-2.878-2.433-4.646 0-3.776 2.748-7.252 7.92-7.252 4.158 0 7.392 2.967 7.392 6.923 0 4.135-2.607 7.462-6.233 7.462-1.214 0-2.357-.629-2.75-1.378l-.748 2.853c-.271 1.043-1.004 2.352-1.494 3.146 1.126.345 2.317.535 3.554.535 6.607 0 11.985-5.365 11.985-11.987C23.97 5.39 18.592.026 11.985.026L12.017 0z"/></svg></a>@endif
                </div>
                @endif
            </div>
        </div>

        <div class="border-t border-gray-800 mt-8 pt-6 text-center text-xs text-gray-500">
            {!! $copyright !!}
        </div>
    </div>
</footer>

@include('partials.storefront.bottom-nav')

{{-- Back to Top — nudged up on mobile (bottom-24 instead of bottom-6) so it floats above
     the app-style bottom nav bar instead of overlapping it. --}}
<div x-data="{ show: false }" @scroll.window="show = window.scrollY > 400"
     x-show="show" x-cloak x-transition
     class="fixed bottom-24 right-6 md:bottom-6 z-40">
    <button onclick="window.scrollTo({top:0,behavior:'smooth'})" aria-label="Back to top" class="w-11 h-11 bg-orange-500 text-white rounded-full shadow-lg hover:bg-orange-600 transition flex items-center justify-center">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/></svg>
    </button>
</div>

{{-- Shared toast for any AJAX action that needs a visible, hard-to-miss confirmation
     regardless of scroll position (the sticky header's own cart-count badge already
     pulses, but it's a small target and easy to miss on a long page) — used by both
     the wishlist and quick-add-to-cart handlers below. Message is set via textContent,
     never interpolated into innerHTML, so it's safe even for a product name with
     special characters. --}}
<script>
function showToast(message, type = 'success') {
    const stack = document.getElementById('toast-stack');
    if (!stack) return;

    const isError = type === 'error';
    const toast = document.createElement('div');
    toast.className = (isError ? 'bg-red-500' : 'bg-green-500')
        + ' text-white px-5 py-3 rounded-lg shadow-xl flex items-center space-x-3 pointer-events-auto toast-pop';
    toast.innerHTML = isError
        ? '<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>'
        : '<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';

    const span = document.createElement('span');
    span.className = 'text-sm font-medium';
    span.textContent = message;
    toast.appendChild(span);

    stack.appendChild(toast);
    setTimeout(() => {
        toast.classList.add('toast-pop-out');
        toast.addEventListener('animationend', () => toast.remove(), { once: true });
    }, 2500);
}
</script>

{{-- Wishlist AJAX — no login required; guests favorite via the same session_id
     scoping the cart already uses (see WishlistController), only checkout still
     asks for an account. So a failure here is a real error, not "please log
     in" — surfaced as a toast instead of bouncing the visitor off the page. --}}
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
        if (!res.ok) {
            throw new Error('Request failed: ' + res.status);
        }
        const data = await res.json();
        if (data.status === 'added') {
            btn.classList.add('text-red-500');
            btn.classList.remove('text-gray-400');
            btn.querySelector('svg').setAttribute('fill', 'currentColor');
            // Restart the pop animation even on rapid re-clicks — removing the
            // class and forcing a reflow before re-adding it is what makes a
            // CSS animation replay instead of being a no-op the second time.
            btn.classList.remove('pop-bounce');
            void btn.offsetWidth;
            btn.classList.add('pop-bounce');
            showToast('Added to wishlist!');
        } else {
            btn.classList.remove('text-red-500');
            btn.classList.add('text-gray-400');
            btn.querySelector('svg').setAttribute('fill', 'none');
            showToast('Removed from wishlist.');
        }
    } catch (e) {
        showToast('Something went wrong — please try again.', 'error');
    }
}
</script>

{{-- Quick Add to Cart AJAX (product card grids). The button's selected/added state reflects
     actual cart membership (data-in-cart), so picking one product doesn't unselect another —
     each button toggles independently and stays selected until explicitly un-selected. --}}
<script>
// Fires AddToCart for both the quick-add button (called directly, right after
// its fetch() succeeds) and the product-page form (a full-page POST + redirect —
// see the session-flash block near the end of this file, which calls this once
// per add, guarded server-side the same way the Purchase event already is).
function trackAddToCart(item) {
    var currency = {!! Js::from(setting('currency_code', 'BDT')) !!};
    var value = (item.price || 0) * (item.quantity || 1);
    if (typeof fbq === 'function') {
        // eventID (when present) matches the server-side Conversions API call
        // CartController@add already fired for this same add — lets Meta dedupe the
        // pixel+CAPI pair into one event instead of double-counting.
        fbq('track', 'AddToCart', {
            content_ids: [String(item.id)], content_type: 'product',
            content_name: item.name, value: value, currency: currency,
        }, item.fb_event_id ? { eventID: item.fb_event_id } : undefined);
    }
    if (typeof gtag === 'function') {
        gtag('event', 'add_to_cart', {
            currency: currency, value: value,
            items: [{ item_id: String(item.id), item_name: item.name, price: item.price, quantity: item.quantity || 1 }],
        });
    }
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({
        event: 'add_to_cart',
        ecommerce: { currency: currency, value: value, items: [{ item_id: String(item.id), item_name: item.name, price: item.price, quantity: item.quantity || 1 }] },
    });
}

function setQuickAddButtonState(btn, inCart) {
    btn.dataset.inCart = inCart ? 'true' : 'false';

    const icon = btn.querySelector('.quick-add-icon');
    if (icon) icon.innerHTML = inCart ? btn.dataset.iconAdded : btn.dataset.iconDefault;

    const label = btn.querySelector('.quick-add-label');
    if (label) label.textContent = inCart ? btn.dataset.labelAdded : btn.dataset.labelDefault;

    btn.title = inCart ? 'Remove from Cart' : btn.dataset.labelDefault;

    btn.classList.toggle('bg-orange-500', inCart);
    btn.classList.toggle('text-white', inCart);
    btn.classList.toggle('hover:bg-orange-600', inCart);
    btn.classList.toggle('bg-white', !inCart);
    btn.classList.toggle('text-gray-500', !inCart);
    btn.classList.toggle('hover:bg-orange-50', !inCart);
    btn.classList.toggle('hover:text-orange-700', !inCart);

    if (inCart) {
        btn.classList.remove('pop-bounce');
        void btn.offsetWidth;
        btn.classList.add('pop-bounce');
    }
}

async function toggleCartItem(productId, btn) {
    if (btn.disabled) return;
    const wasInCart = btn.dataset.inCart === 'true';
    btn.disabled = true;

    try {
        const res = await fetch(wasInCart ? '/cart/product/' + productId : '{{ route('cart.add') }}', {
            method: wasInCart ? 'DELETE' : 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: wasInCart ? null : JSON.stringify({ product_id: productId, quantity: 1 })
        });
        const data = await res.json();

        if (res.ok && (data.status === 'added' || data.status === 'removed')) {
            setQuickAddButtonState(btn, data.status === 'added');

            if (data.status === 'added') {
                trackAddToCart({
                    id: productId,
                    name: btn.dataset.productName || '',
                    price: parseFloat(btn.dataset.productPrice || '0'),
                    quantity: 1,
                    fb_event_id: data.fb_event_id,
                });
                const name = btn.dataset.productName;
                showToast(name ? `Added "${name}" to cart!` : 'Added to cart!');
            }

            const badge = document.getElementById('header-cart-count');
            if (badge) {
                badge.textContent = data.cart_count;
                badge.classList.toggle('hidden', data.cart_count <= 0);
                badge.classList.remove('pulse-badge');
                void badge.offsetWidth; // restart the CSS animation
                badge.classList.add('pulse-badge');
            }
        } else {
            alert(data.message || 'Could not update your cart.');
        }
    } catch (e) {
        alert('Something went wrong. Please try again.');
    } finally {
        btn.disabled = false;
    }
}
</script>

@if(session('tracked_add_to_cart'))
<script>trackAddToCart(@json(session('tracked_add_to_cart')));</script>
@endif

@php $customJs = setting('custom_js', ''); @endphp
{{-- Raw, not escaped — same reasoning as Custom CSS above. Virtually all real JS uses
     quotes, and {{ }}'s HTML-entity-escaping would corrupt every one of them into invalid
     syntax the moment this admin pasted anything beyond a quote-free one-liner. --}}
@if($customJs)<script>{!! $customJs !!}</script>@endif
</body>
</html>
