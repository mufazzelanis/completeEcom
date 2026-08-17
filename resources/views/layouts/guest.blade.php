<?php /** @noinspection HtmlUnknownTarget */ ?>
@php
    // Same admin-editable branding used on the storefront (Settings → Branding /
    // Theme & Design) — kept independent from layouts/app.blade.php's own copy of
    // this logic (rather than extracted into a shared include) because the storefront
    // layout leans on it far more heavily; duplicating the handful of lines here is
    // cheaper than risking that much larger file.
    $siteName    = setting('site_name', 'ShopVista');
    $siteTagline = setting('site_tagline', 'Your one-stop shop for everything you need.');
    $logoUrl     = setting_file_url('login_logo', setting_file_url('site_logo'));
    $faviconUrl  = setting_file_url('favicon');

    $primaryColor   = setting('primary_color', '#ea580c');
    $secondaryColor = setting('secondary_color', '#ec4899');
    $accentColor    = setting('accent_color', '#dc2626');
    $textColor      = setting('text_color', '#1f2937');
    $brandShades     = $primaryColor !== '#ea580c' ? brand_color_shades($primaryColor) : null;
    $secondaryShades = $secondaryColor !== '#ec4899' ? brand_color_shades($secondaryColor) : null;
    $accentShades    = $accentColor !== '#dc2626' ? brand_color_shades($accentColor) : null;
    $textColorChanged = $textColor !== '#1f2937';
    // Always-resolved ramps (regardless of whether the admin customized the color) for
    // the panel gradient/CSS variables below — those aren't literal-class overrides,
    // so there's no "stay pixel-identical to the default Tailwind palette" constraint.
    $primaryRamp = brand_color_shades($primaryColor);
    $accentRamp  = brand_color_shades($accentColor);

    $fontFamily     = setting('font_family', 'Inter, sans-serif');
    $isSystemFont   = str_contains($fontFamily, 'system-ui');
    $googleFontName = trim(explode(',', $fontFamily)[0]);
    $borderRadius   = setting('border_radius', 'soft');
    $shadowStyle    = setting('shadow_style', 'soft');
    $darkModeDefault = setting('dark_mode_default', 'system');

    $radiusScale = [
        'sharp'  => ['rounded' => '0px',   'rounded-md' => '2px',  'rounded-lg' => '4px',  'rounded-xl' => '6px',  'rounded-2xl' => '8px'],
        'soft'   => null,
        'round'  => ['rounded' => '8px',   'rounded-md' => '12px', 'rounded-lg' => '16px', 'rounded-xl' => '24px', 'rounded-2xl' => '32px'],
        'xround' => ['rounded' => '16px',  'rounded-md' => '20px', 'rounded-lg' => '28px', 'rounded-xl' => '40px', 'rounded-2xl' => '48px'],
    ][$borderRadius] ?? null;

    $shadowScale = [
        'none'   => 'none',
        'soft'   => null,
        'medium' => '0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1)',
        'strong' => '0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1)',
    ][$shadowStyle] ?? null;

    $customCss = setting('custom_css', '');

    // Marketing panel content (Settings → Branding → "Login & Verification Pages").
    $panelHeading    = setting('auth_panel_heading') ?: 'Welcome back to ' . $siteName;
    $panelSubheading = setting('auth_panel_subheading') ?: $siteTagline;
    $panelFeaturesRaw = setting('auth_panel_features') ?: "Secure checkout & encrypted data\nFast, tracked delivery\n24/7 customer support";
    $panelFeatures = collect(preg_split('/\r\n|\r|\n/', $panelFeaturesRaw))->map(fn ($f) => trim($f))->filter()->values();
    $panelImageUrl = setting_file_url('auth_panel_image');
    // Shared by the desktop side panel and the mobile hero band below, so an admin-set
    // background image/color shows up on every screen size, not just lg+.
    $panelBackgroundCss = $panelImageUrl
        ? "linear-gradient(160deg, {$primaryRamp['800']}e6, {$accentRamp['800']}e6), url('" . e($panelImageUrl) . "') center/cover"
        : "linear-gradient(160deg, {$primaryColor}, {$accentRamp['700']})";
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ match(true) {
        request()->routeIs('register') => 'Register',
        request()->routeIs('login') => 'Login',
        request()->routeIs('password.request') => 'Forgot Password',
        request()->routeIs('password.reset') => 'Reset Password',
        request()->routeIs('verification.notice') => 'Verify Email',
        request()->routeIs('password.confirm') => 'Confirm Password',
        request()->routeIs('two-factor.challenge') => 'Verify Your Identity',
        default => 'Account',
    } }} | {{ $siteName }}</title>
    @if($faviconUrl)<link rel="icon" href="{{ $faviconUrl }}">@endif

    @if(!$isSystemFont)
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family={{ str_replace(' ', '+', $googleFontName) }}:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @endif

    <script>
        // Applied before first paint so there's never a flash of the wrong theme —
        // shares the same "site-theme" localStorage key as the storefront, so a
        // preference set on either side carries over to the other.
        (function () {
            var stored = localStorage.getItem('site-theme');
            var adminDefault = {{ Js::from($darkModeDefault) }};
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
    {{-- Brand colors + Theme & Design (Settings → Branding / Theme & Design): the shared
         auth components (x-text-input, x-primary-button, etc.) are styled with literal
         Tailwind classes just like the rest of the storefront, so re-theming them here
         uses the same "override the generated utility class" approach as layouts/app.blade.php. --}}
    <style>
        :root {
            --brand-primary: {{ $primaryColor }};
            --brand-primary-dark: {{ $primaryRamp['700'] }};
            --brand-primary-light: {{ $primaryRamp['50'] }};
            --brand-accent: {{ $accentColor }};
            --brand-accent-dark: {{ $accentRamp['700'] }};
        }
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
        @endforeach
        @endif
        @if($accentShades)
        @foreach($accentShades as $step => $hex)
        .bg-red-{{ $step }} { background-color: {{ $hex }} !important; }
        .text-red-{{ $step }} { color: {{ $hex }} !important; }
        @endforeach
        @endif
        @if($textColorChanged)
        .text-gray-800 { color: {{ $textColor }} !important; }
        @endif
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
    </style>
    @if($customCss)<style>{{ $customCss }}</style>@endif
    <style>
        [x-cloak] { display: none !important; }
        .otp-box:focus { transform: translateY(-1px); }
        @keyframes authFadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
        .auth-fade-in { animation: authFadeIn .35s ease-out; }
    </style>

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
</head>
<body class="font-sans antialiased bg-gray-50 dark:bg-gray-950 text-gray-900 dark:text-gray-100 transition-colors">

    <div class="min-h-screen lg:grid lg:grid-cols-2">

        {{-- Marketing panel — hidden on small screens, admin-editable via Settings → Branding --}}
        <aside class="relative hidden lg:flex flex-col justify-between overflow-hidden px-12 py-12 text-white"
               style="background: {{ $panelBackgroundCss }};">
            {{-- Decorative blurred shapes --}}
            <div class="pointer-events-none absolute -top-24 -right-24 w-96 h-96 rounded-full bg-white/10 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-32 -left-16 w-80 h-80 rounded-full bg-black/10 blur-3xl"></div>

            {{-- Logo if one's uploaded, otherwise the site name as text — never both, so this
                 doesn't read as the brand identity repeated twice next to each other. --}}
            <a href="{{ route('home') }}" class="relative inline-block">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="h-10 max-w-[160px] object-contain drop-shadow">
                @else
                    <span class="text-lg font-bold tracking-tight">{{ $siteName }}</span>
                @endif
            </a>

            <div class="relative max-w-md">
                <h2 class="text-3xl font-bold leading-tight">{{ $panelHeading }}</h2>
                <p class="mt-3 text-white/80 leading-relaxed">{{ $panelSubheading }}</p>

                @if($panelFeatures->isNotEmpty())
                <ul class="mt-8 space-y-3">
                    @foreach($panelFeatures as $feature)
                    <li class="flex items-start gap-3">
                        <span class="mt-0.5 flex-shrink-0 w-5 h-5 rounded-full bg-white/15 flex items-center justify-center">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        </span>
                        <span class="text-sm text-white/90">{{ $feature }}</span>
                    </li>
                    @endforeach
                </ul>
                @endif
            </div>

            <p class="relative text-xs text-white/60">&copy; {{ date('Y') }} {{ $siteName }}. All rights reserved.</p>
        </aside>

        {{-- Form panel --}}
        <div class="flex flex-col min-h-screen lg:min-h-0 lg:h-screen lg:overflow-y-auto">

            {{-- Mobile hero band — the same admin-editable gradient/image/heading/trust-points
                 as the desktop side panel (Settings → Branding → "Login & Verification Pages"),
                 just laid out as a top band instead of a side column since there's no room for
                 a split layout on small screens. --}}
            <div class="lg:hidden relative overflow-hidden px-6 pt-5 pb-10 text-white" style="background: {{ $panelBackgroundCss }};">
                <div class="pointer-events-none absolute -top-14 -right-14 w-52 h-52 rounded-full bg-white/10 blur-2xl"></div>

                <div class="relative flex items-center justify-between">
                    <a href="{{ route('home') }}">
                        @if($logoUrl)
                            <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="h-9 max-w-[140px] object-contain drop-shadow">
                        @else
                            <span class="font-bold text-lg tracking-tight">{{ $siteName }}</span>
                        @endif
                    </a>
                    <button type="button" x-data @click="$store.theme.toggle()"
                            class="w-9 h-9 rounded-full flex items-center justify-center text-white/90 hover:bg-white/15 transition"
                            aria-label="Toggle dark mode">
                        <svg x-show="!$store.theme.dark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <svg x-cloak x-show="$store.theme.dark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    </button>
                </div>

                <div class="relative mt-6">
                    <h2 class="text-2xl font-bold leading-tight">{{ $panelHeading }}</h2>
                    <p class="mt-1.5 text-sm text-white/80">{{ $panelSubheading }}</p>

                    @if($panelFeatures->isNotEmpty())
                    <ul class="mt-4 flex flex-wrap gap-x-4 gap-y-1.5">
                        @foreach($panelFeatures as $feature)
                        <li class="flex items-center gap-1.5 text-xs text-white/90">
                            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            {{ $feature }}
                        </li>
                        @endforeach
                    </ul>
                    @endif
                </div>
            </div>

            {{-- Desktop-only top bar: branding already lives in the side panel, so just the toggle --}}
            <div class="hidden lg:flex justify-end items-center px-6 py-5">
                <button type="button" x-data @click="$store.theme.toggle()"
                        class="w-9 h-9 rounded-full flex items-center justify-center text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                        aria-label="Toggle dark mode">
                    <svg x-show="!$store.theme.dark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    <svg x-cloak x-show="$store.theme.dark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                </button>
            </div>

            <div class="flex-1 flex flex-col items-center justify-center px-6 pb-12">
                <div class="w-full sm:max-w-md -mt-6 lg:mt-0 auth-fade-in">
                    @if(session('success'))
                    <div class="mb-4 flex items-start gap-2.5 rounded-xl border border-green-200 dark:border-green-900 bg-green-50 dark:bg-green-950/40 px-4 py-3 text-sm text-green-700 dark:text-green-400">
                        <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>{{ session('success') }}</span>
                    </div>
                    @endif

                    <div class="bg-white dark:bg-gray-900 shadow-xl shadow-gray-200/60 dark:shadow-none ring-1 ring-gray-900/5 dark:ring-white/10 rounded-2xl px-6 py-7 sm:px-8 sm:py-8">
                        {{ $slot }}
                    </div>

                    <p class="mt-6 text-center text-xs text-gray-400 dark:text-gray-500 lg:hidden">
                        &copy; {{ date('Y') }} {{ $siteName }}. All rights reserved.
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
