@php
$siteName = setting('site_name', 'ShopVista');
$logoUrl  = setting_file_url('site_logo');
$logoMobileUrl = setting_file_url('site_logo_mobile');
$unread   = \App\Models\UserNotification::where('user_id', auth()->id())->where('is_read', false)->count();
$cartCount = \App\Models\Cart::where('user_id', auth()->id())->sum('quantity');
@endphp
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'My Account') - {{ $siteName }}</title>
    @if($faviconUrl = setting_file_url('favicon'))<link rel="icon" href="{{ $faviconUrl }}">@endif
    {{-- Same pre-paint dark-mode detection + 'site-theme' localStorage key as
         layouts/app.blade.php, so a customer's theme choice carries over consistently
         between the main storefront and Account rather than this separate layout always
         starting back at system/light. --}}
    <script>
        (function () {
            var stored = localStorage.getItem('site-theme');
            var adminDefault = {{ Js::from(setting('dark_mode_default', 'system')) }};
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
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="bg-gray-50 dark:bg-gray-950 font-sans antialiased transition-colors pb-[calc(4rem_+_env(safe-area-inset-bottom))] md:pb-0" x-data="{ menuOpen: false }">

{{-- Top Nav — same logo size as the main storefront header (partials.storefront.header-logo)
     so switching into Account doesn't visibly shrink the branding down to a different scale. --}}
<nav class="bg-white shadow-sm sticky top-0 z-50 border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
        <a href="{{ route('home') }}" class="flex items-center gap-2">
            <div class="md:hidden flex items-center gap-2">
                @if($logoMobileUrl)
                    <img src="{{ $logoMobileUrl }}" alt="{{ $siteName }}" class="h-11 max-w-[170px] object-contain">
                @elseif($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="h-11 max-w-[170px] object-contain">
                @else
                    <div class="w-9 h-9 bg-orange-500 rounded-lg flex items-center justify-center">
                        <span class="text-white font-bold text-base">{{ strtoupper(substr($siteName, 0, 1)) }}</span>
                    </div>
                    <span class="font-bold text-lg text-orange-600">{{ $siteName }}</span>
                @endif
            </div>
            <div class="hidden md:flex items-center gap-2">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="h-11 max-w-[170px] object-contain">
                @else
                    <div class="w-9 h-9 bg-orange-500 rounded-lg flex items-center justify-center">
                        <span class="text-white font-bold text-base">{{ strtoupper(substr($siteName, 0, 1)) }}</span>
                    </div>
                    <span class="font-bold text-lg text-orange-600">{{ $siteName }}</span>
                @endif
            </div>
        </a>

        <div class="flex items-center gap-3">
            {{-- Theme Toggle — same $store.theme as the main storefront header
                 (partials.storefront.header-actions), sharing the 'site-theme' key. --}}
            <button @click="$store.theme.toggle()" type="button"
                class="p-1.5 rounded-lg text-gray-500 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                :aria-label="$store.theme.dark ? 'Switch to light mode' : 'Switch to dark mode'">
                <svg x-show="!$store.theme.dark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                <svg x-show="$store.theme.dark" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </button>
            <a href="{{ route('account.notifications') }}" class="relative text-gray-500 hover:text-orange-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                @if($unread > 0)<span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center font-bold">{{ $unread > 9 ? '9+' : $unread }}</span>@endif
            </a>
            <a href="{{ route('cart.index') }}" class="relative text-gray-500 hover:text-orange-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                @if($cartCount > 0)<span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center font-bold">{{ $cartCount > 9 ? '9+' : $cartCount }}</span>@endif
            </a>
            <a href="{{ route('shop.index') }}" class="text-sm text-gray-500 hover:text-orange-600 transition hidden sm:block">Store</a>

            {{-- Profile Icon --}}
            <button @click="menuOpen = !menuOpen" class="flex items-center gap-2 pl-1 pr-1 py-1 rounded-full hover:bg-gray-100 transition">
                <div class="w-7 h-7 bg-gradient-to-br from-orange-400 to-red-500 rounded-full flex items-center justify-center flex-shrink-0">
                    <span class="text-white font-bold text-xs">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                </div>
                <svg class="w-3.5 h-3.5 text-gray-400 transition-transform" :class="menuOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
        </div>
    </div>
</nav>

{{-- Mobile Full-Screen Menu --}}
<div x-show="menuOpen" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
     @click.outside="menuOpen = false" @keydown.escape.window="menuOpen = false"
     class="fixed inset-0 z-50 lg:hidden">
    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-black/40" @click="menuOpen = false"></div>
    {{-- Panel --}}
    <div x-show="menuOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
         class="absolute bottom-0 left-0 right-0 bg-white rounded-t-2xl max-h-[85vh] flex flex-col shadow-2xl">
        {{-- Handle --}}
        <div class="flex justify-center pt-3 pb-1"><div class="w-10 h-1 bg-gray-300 rounded-full"></div></div>
        {{-- User Info --}}
        <div class="px-5 py-3 border-b border-gray-100 flex items-center gap-3">
            <img src="{{ auth()->user()->avatar_url }}" class="w-11 h-11 rounded-full object-cover flex-shrink-0">
            <div class="min-w-0">
                <p class="text-sm font-bold text-gray-900 truncate">{{ auth()->user()->name }}</p>
                <p class="text-xs text-gray-500 truncate mt-0.5">{{ auth()->user()->email }}</p>
            </div>
        </div>
        {{-- Nav Items --}}
        <div class="flex-1 overflow-y-auto py-2">
            @php
                $mobileNav = fn(string $route, string $icon, string $label, string $match = '') =>
                    ['route' => $route, 'icon' => $icon, 'label' => $label, 'match' => $match ?: $route];
                $isApprovedVendor = auth()->user()->vendor?->status === 'approved';
                $mobileItems = [
                    $mobileNav('account.dashboard', 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', 'Dashboard'),
                    $mobileNav('account.profile', 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z', 'My Profile'),
                    $mobileNav('account.addresses.index', 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z', 'Address Book', 'account.addresses.*'),
                    $mobileNav('orders.index', 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z', 'My Orders', 'orders.*'),
                    $mobileNav('account.returns.index', 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15', 'My Returns', 'account.returns.*'),
                    $mobileNav('wishlist.index', 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z', 'Wishlist', 'wishlist.*'),
                    $mobileNav('account.reviews.index', 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z', 'My Reviews', 'account.reviews.*'),
                    $mobileNav('account.referral', 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z', 'Referral Program'),
                    $isApprovedVendor
                        ? $mobileNav('seller.dashboard', 'M3 3h18v4H3V3zm0 7h18v11H3V10zm4 4h4', 'Seller Dashboard')
                        : $mobileNav('vendor.apply', 'M3 3h18v4H3V3zm0 7h18v11H3V10zm4 4h4', 'Become a Seller'),
                    $mobileNav('account.support.index', 'M18 2a2 2 0 012 2v12a2 2 0 01-2 2H6l-4 4V4a2 2 0 012-2h14z', 'Support Tickets', 'account.support.*'),
                    $mobileNav('account.notifications', 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9', 'Notifications', 'account.notifications*'),
                    $mobileNav('account.security', 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', 'Security'),
                ];
            @endphp
            @foreach($mobileItems as $item)
                @php $isActive = request()->routeIs($item['match']); @endphp
                <a href="{{ route($item['route']) }}" @click="menuOpen = false"
                   class="flex items-center gap-3 px-5 py-3 text-sm font-medium transition {{ $isActive ? 'bg-orange-50 text-orange-600' : 'text-gray-700 hover:bg-gray-50' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/></svg>
                    {{ $item['label'] }}
                    @if($item['route'] === 'account.notifications' && $unread > 0)
                        <span class="ml-auto bg-red-500 text-white text-xs rounded-full px-1.5 py-0.5 font-semibold leading-none">{{ $unread }}</span>
                    @endif
                </a>
            @endforeach
        </div>
        {{-- Logout --}}
        <div class="border-t border-gray-100 px-5 py-3">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full flex items-center justify-center gap-2 py-2.5 rounded-xl bg-red-50 text-red-600 text-sm font-semibold hover:bg-red-100 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    Logout
                </button>
            </form>
        </div>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="flex gap-6">

        {{-- Sidebar (desktop only) --}}
        <aside class="w-64 flex-shrink-0 hidden lg:block">
            <div class="space-y-1 sticky top-20">
                {{-- User card --}}
                <div class="bg-white rounded-2xl p-4 mb-3 flex items-center gap-3 shadow-sm">
                    <img src="{{ auth()->user()->avatar_url }}" class="w-11 h-11 rounded-full object-cover flex-shrink-0">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-800 truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-gray-400 truncate">{{ auth()->user()->email }}</p>
                    </div>
                </div>

                <nav class="bg-white rounded-2xl shadow-sm overflow-hidden">
                    @php
                        $navItem = fn(string $route, string $icon, string $label, string $match = '') =>
                            ['route' => $route, 'icon' => $icon, 'label' => $label, 'match' => $match ?: $route];
                        $isApprovedVendor = auth()->user()->vendor?->status === 'approved';
                        $items = [
                            ['group' => 'Account'],
                            $navItem('account.dashboard', 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', 'Dashboard'),
                            $navItem('account.profile', 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z', 'My Profile'),
                            $navItem('account.addresses.index', 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z', 'Address Book', 'account.addresses.*'),
                            ['group' => 'Orders'],
                            $navItem('orders.index', 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z', 'My Orders', 'orders.*'),
                            $navItem('account.returns.index', 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15', 'My Returns', 'account.returns.*'),
                            $navItem('wishlist.index', 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z', 'Wishlist', 'wishlist.*'),
                            ['group' => 'Rewards'],
                            $navItem('account.reviews.index', 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z', 'My Reviews', 'account.reviews.*'),
                            $navItem('account.referral', 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z', 'Referral Program'),
                            $isApprovedVendor
                                ? $navItem('seller.dashboard', 'M3 3h18v4H3V3zm0 7h18v11H3V10zm4 4h4', 'Seller Dashboard')
                                : $navItem('vendor.apply', 'M3 3h18v4H3V3zm0 7h18v11H3V10zm4 4h4', 'Become a Seller'),
                            ['group' => 'Support'],
                            $navItem('account.support.index', 'M18 2a2 2 0 012 2v12a2 2 0 01-2 2H6l-4 4V4a2 2 0 012-2h14z', 'Support Tickets', 'account.support.*'),
                            ['group' => 'Settings'],
                            $navItem('account.notifications', 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9', 'Notifications', 'account.notifications*'),
                            $navItem('account.notifications.preferences', 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z', 'Notification Settings'),
                            $navItem('account.security', 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', 'Security'),
                        ];
                    @endphp

                    @php $inGroup = false; @endphp
                    @foreach($items as $item)
                        @if(isset($item['group']))
                            @if($inGroup)</div>@endif
                            <div class="px-3 py-2 border-b border-gray-50">
                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ $item['group'] }}</p>
                            </div>
                            <div>
                            @php $inGroup = true; @endphp
                        @else
                            @php
                                $isActive = request()->routeIs($item['match']);
                            @endphp
                            <a href="{{ route($item['route']) }}"
                                class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium transition {{ $isActive ? 'bg-orange-50 text-orange-600 border-r-2 border-orange-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/>
                                </svg>
                                <span>{{ $item['label'] }}</span>
                                @if($item['route'] === 'account.notifications' && $unread > 0)
                                    <span class="ml-auto bg-red-500 text-white text-xs rounded-full px-1.5 py-0.5 font-semibold leading-none">{{ $unread }}</span>
                                @endif
                            </a>
                        @endif
                    @endforeach
                    @if($inGroup)</div>@endif

                    <div class="border-t border-gray-100">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full flex items-center gap-3 px-4 py-3 text-sm font-medium text-red-500 hover:bg-red-50 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                Logout
                            </button>
                        </form>
                    </div>
            </nav>
            </div>
        </aside>

        {{-- Main Content --}}
        <main class="flex-1 min-w-0">
            {{-- Mobile page header --}}
            <div class="flex items-center gap-3 mb-4 lg:hidden">
                <a href="{{ route('shop.index') }}" class="w-9 h-9 bg-white rounded-xl shadow-sm flex items-center justify-center text-gray-500 hover:text-orange-600 transition flex-shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </a>
                <h1 class="text-lg font-bold text-gray-800 truncate">@yield('pageTitle', 'My Account')</h1>
            </div>
            @if(session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                 class="mb-4 bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm flex items-center justify-between">
                <span>{{ session('success') }}</span>
                <button @click="show = false" class="text-green-400 hover:text-green-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            @endif
            @if(session('error'))
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm">{{ session('error') }}</div>
            @endif
            @yield('content')
        </main>

    </div>
</div>

{{-- Same global bottom tab bar as the rest of the storefront (layouts/app.blade.php) — this
     is its own separate layout (not an @extends of layouts.app), so without this the tab bar
     would vanish the moment a customer steps into Account, which is exactly the "why does
     the bottom bar disappear here" inconsistency this fixes. The account section's own
     avatar-triggered bottom-sheet menu above still handles the deeper account sub-navigation
     (Profile, Addresses, Returns, etc.) — the two are complementary, not a duplicate. --}}
@include('partials.storefront.bottom-nav')

</body>
</html>
