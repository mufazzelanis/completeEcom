@php
$siteName = setting('site_name', 'ShopVista');
$logoUrl  = setting_file_url('site_logo');
$vendor   = auth()->user()->vendor;
$vendorLogoUrl = $vendor->logo ? Storage::url($vendor->logo) : null;

$sellerNavItem = fn(string $route, string $icon, string $label, string $match = '') =>
    ['route' => $route, 'icon' => $icon, 'label' => $label, 'match' => $match ?: $route];
$sellerNavItems = [
    ['group' => 'Overview'],
    $sellerNavItem('seller.dashboard', 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', 'Dashboard'),
    ['group' => 'Catalog'],
    $sellerNavItem('seller.products.index', 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4', 'My Products', 'seller.products.*'),
    $sellerNavItem('seller.products.create', 'M12 4v16m8-8H4', 'Add Product'),
    $sellerNavItem('seller.categories.create', 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z', 'Propose Category'),
    ['group' => 'Sales'],
    $sellerNavItem('seller.orders.index', 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z', 'My Orders'),
    $sellerNavItem('seller.reports.index', 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z', 'Sales Report'),
    ['group' => 'Support'],
    $sellerNavItem('account.support.index', 'M18 2a2 2 0 012 2v12a2 2 0 01-2 2H6l-4 4V4a2 2 0 012-2h14z', 'Support Tickets', 'account.support.*'),
    ['group' => 'Settings'],
    $sellerNavItem('seller.profile.edit', 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z', 'My Profile'),
];
@endphp
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Seller Dashboard') - {{ $siteName }}</title>
    @if($faviconUrl = setting_file_url('favicon'))<link rel="icon" href="{{ $faviconUrl }}">@endif
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak]{display:none!important}</style>
    @stack('styles')
</head>
<body class="bg-gray-50 font-sans antialiased" x-data="{ menuOpen: false }">

{{-- Top Nav --}}
<nav class="bg-white shadow-sm sticky top-0 z-50 border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-14 flex items-center justify-between">
        <a href="{{ route('seller.dashboard') }}" class="flex items-center gap-2">
            @if($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="h-7 max-w-[120px] object-contain">
            @else
                <div class="w-7 h-7 bg-indigo-600 rounded-lg flex items-center justify-center">
                    <span class="text-white font-bold text-sm">{{ strtoupper(substr($siteName, 0, 1)) }}</span>
                </div>
                <span class="font-bold text-indigo-700">{{ $siteName }}</span>
            @endif
            <span class="text-xs bg-indigo-50 text-indigo-600 px-2 py-0.5 rounded-full font-semibold ml-1">Seller</span>
        </a>

        <div class="flex items-center gap-3">
            <a href="{{ route('shop.index') }}" class="text-sm text-gray-500 hover:text-indigo-600 transition hidden sm:block">View Store</a>

            <button @click="menuOpen = !menuOpen" class="flex items-center gap-2 pl-1 pr-1 py-1 rounded-full hover:bg-gray-100 transition">
                @if($vendorLogoUrl)
                    <img src="{{ $vendorLogoUrl }}" alt="{{ $vendor->business_name }}" class="w-7 h-7 rounded-full object-cover flex-shrink-0">
                @else
                    <div class="w-7 h-7 bg-gradient-to-br from-indigo-400 to-purple-500 rounded-full flex items-center justify-center flex-shrink-0">
                        <span class="text-white font-bold text-xs">{{ strtoupper(substr($vendor->business_name, 0, 1)) }}</span>
                    </div>
                @endif
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
    <div class="absolute inset-0 bg-black/40" @click="menuOpen = false"></div>
    <div x-show="menuOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
         class="absolute bottom-0 left-0 right-0 bg-white rounded-t-2xl max-h-[85vh] flex flex-col shadow-2xl">
        <div class="flex justify-center pt-3 pb-1"><div class="w-10 h-1 bg-gray-300 rounded-full"></div></div>
        <div class="px-5 py-3 border-b border-gray-100 flex items-center gap-3">
            @if($vendorLogoUrl)
                <img src="{{ $vendorLogoUrl }}" alt="{{ $vendor->business_name }}" class="w-11 h-11 rounded-full object-cover flex-shrink-0">
            @else
                <div class="w-11 h-11 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                    <span class="text-indigo-600 font-bold">{{ strtoupper(substr($vendor->business_name, 0, 1)) }}</span>
                </div>
            @endif
            <div class="min-w-0">
                <p class="text-sm font-bold text-gray-900 truncate">{{ $vendor->business_name }}</p>
                <p class="text-xs text-gray-500 truncate mt-0.5">{{ auth()->user()->email }}</p>
            </div>
        </div>
        <div class="flex-1 overflow-y-auto py-2">
            @foreach($sellerNavItems as $item)
                @continue(isset($item['group']))
                @php $isActive = request()->routeIs($item['match']); @endphp
                <a href="{{ route($item['route']) }}" @click="menuOpen = false"
                   class="flex items-center gap-3 px-5 py-3 text-sm font-medium transition {{ $isActive ? 'bg-indigo-50 text-indigo-600' : 'text-gray-700 hover:bg-gray-50' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/></svg>
                    {{ $item['label'] }}
                </a>
            @endforeach
        </div>
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
                <div class="bg-white rounded-2xl p-4 mb-3 flex items-center gap-3 shadow-sm">
                    @if($vendorLogoUrl)
                        <img src="{{ $vendorLogoUrl }}" alt="{{ $vendor->business_name }}" class="w-11 h-11 rounded-full object-cover flex-shrink-0">
                    @else
                        <div class="w-11 h-11 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                            <span class="text-indigo-600 font-bold">{{ strtoupper(substr($vendor->business_name, 0, 1)) }}</span>
                        </div>
                    @endif
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-800 truncate">{{ $vendor->business_name }}</p>
                        <p class="text-xs text-gray-400 truncate">{{ auth()->user()->email }}</p>
                    </div>
                </div>

                <nav class="bg-white rounded-2xl shadow-sm overflow-hidden">
                    @php $inGroup = false; @endphp
                    @foreach($sellerNavItems as $item)
                        @if(isset($item['group']))
                            @if($inGroup)</div>@endif
                            <div class="px-3 py-2 border-b border-gray-50">
                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ $item['group'] }}</p>
                            </div>
                            <div>
                            @php $inGroup = true; @endphp
                        @else
                            @php $isActive = request()->routeIs($item['match']); @endphp
                            <a href="{{ route($item['route']) }}"
                                class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium transition {{ $isActive ? 'bg-indigo-50 text-indigo-600 border-r-2 border-indigo-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/>
                                </svg>
                                <span>{{ $item['label'] }}</span>
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
            <div class="flex items-center gap-3 mb-4 lg:hidden">
                <h1 class="text-lg font-bold text-gray-800 truncate">@yield('pageTitle', 'Seller Dashboard')</h1>
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

@stack('scripts')
</body>
</html>
