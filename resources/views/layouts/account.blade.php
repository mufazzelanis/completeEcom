@php
$siteName = setting('site_name', 'ShopVista');
$logoUrl  = setting_file_url('site_logo');
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
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="bg-gray-50 font-sans antialiased">

{{-- Top Nav --}}
<nav class="bg-white shadow-sm sticky top-0 z-50 border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-14 flex items-center justify-between">
        <a href="{{ route('home') }}" class="flex items-center gap-2">
            @if($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="h-7 max-w-[120px] object-contain">
            @else
                <div class="w-7 h-7 bg-orange-500 rounded-lg flex items-center justify-center">
                    <span class="text-white font-bold text-sm">{{ strtoupper(substr($siteName, 0, 1)) }}</span>
                </div>
                <span class="font-bold text-orange-600">{{ $siteName }}</span>
            @endif
        </a>

        <div class="flex items-center gap-4">
            <a href="{{ route('account.notifications') }}" class="relative text-gray-500 hover:text-orange-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                @if($unread > 0)<span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center font-bold">{{ $unread > 9 ? '9+' : $unread }}</span>@endif
            </a>
            <a href="{{ route('cart.index') }}" class="relative text-gray-500 hover:text-orange-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                @if($cartCount > 0)<span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center font-bold">{{ $cartCount > 9 ? '9+' : $cartCount }}</span>@endif
            </a>
            <a href="{{ route('shop.index') }}" class="text-sm text-gray-500 hover:text-orange-600 transition hidden sm:block">Store</a>

            {{-- Account Menu --}}
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" class="flex items-center gap-2 pl-1 pr-1 py-1 rounded-full hover:bg-gray-100 transition">
                    <div class="w-7 h-7 bg-gradient-to-br from-orange-400 to-red-500 rounded-full flex items-center justify-center flex-shrink-0">
                        <span class="text-white font-bold text-xs">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                    </div>
                    <svg class="w-3.5 h-3.5 text-gray-400 transition-transform hidden sm:block" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" @click.outside="open = false" x-cloak x-transition
                    class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-xl py-2 z-50 border border-gray-100">
                    <div class="px-4 py-3 border-b border-gray-100">
                        <p class="text-sm font-bold text-gray-900 truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-gray-500 truncate mt-0.5">{{ auth()->user()->email }}</p>
                    </div>
                    <a href="{{ route('account.dashboard') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-orange-50 hover:text-orange-600 transition">My Account</a>
                    <a href="{{ route('orders.index') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-orange-50 hover:text-orange-600 transition">My Orders</a>
                    <a href="{{ route('wishlist.index') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-orange-50 hover:text-orange-600 transition">Wishlist</a>
                    <div class="border-t border-gray-100 mt-1 pt-1">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left flex items-center gap-3 px-4 py-2.5 text-sm text-red-500 hover:bg-red-50 transition">Logout</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="flex gap-6">

        {{-- Sidebar --}}
        <aside class="w-64 flex-shrink-0" x-data="{ mobileOpen: false }">
            {{-- Mobile toggle --}}
            <button @click="mobileOpen = !mobileOpen" class="lg:hidden w-full bg-white border border-gray-200 rounded-xl px-4 py-3 flex items-center justify-between text-sm font-medium text-gray-700 mb-3">
                My Account
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>

            <div class="hidden lg:block space-y-1" :class="mobileOpen ? '!block' : ''">
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
                            $navItem('vendor.apply', 'M3 3h18v4H3V3zm0 7h18v11H3V10zm4 4h4', 'Become a Seller'),
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

</body>
</html>
