{{-- Mobile app-style bottom tab bar — hidden on md+ (desktop already has the full header
     nav up top). This is the single biggest thing that makes a mobile site feel like an
     installed app rather than "a website you're viewing in a browser": the handful of
     things people reach for constantly (Home, Categories, Wishlist, Cart, Account) sit in
     exactly the same spot on every page, one thumb-reach away, instead of behind a menu
     that has to be opened first every time.

     English labels — the rest of the storefront's UI (header actions, account menu, etc.)
     is English, so this stays consistent with it rather than being the one Bengali corner.

     Fixed to the viewport bottom, safe-area-aware (the padding-bottom below keeps it clear
     of the home-indicator strip on notched iPhones — layouts/app.blade.php pairs this with
     matching body padding-bottom so the fixed bar never permanently covers the last bit of
     footer content underneath it), rounded-t-2xl + an active "pill" behind the current tab's
     icon (not just a color change) for a more native, less flat feel. --}}
@php
    $bnCartCount = auth()->check()
        ? \App\Models\Cart::where('user_id', auth()->id())->sum('quantity')
        : \App\Models\Cart::where('session_id', session()->getId())->sum('quantity');

    $bnItems = [
        [
            'url' => route('home'), 'active' => request()->routeIs('home'), 'label' => 'Home',
            'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
        ],
        [
            'url' => route('categories.index'), 'active' => request()->routeIs('categories.*'), 'label' => 'Categories',
            'icon' => 'M4 6h16M4 10h16M4 14h16M4 18h16',
        ],
        [
            'url' => route('wishlist.index'), 'active' => request()->routeIs('wishlist.*'), 'label' => 'Wishlist',
            'icon' => 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z',
        ],
        [
            'url' => route('cart.index'), 'active' => request()->routeIs('cart.*'), 'label' => 'Cart',
            'icon' => 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z',
            'badge' => $bnCartCount,
        ],
    ];
@endphp
<nav class="md:hidden fixed bottom-0 inset-x-0 z-40 bg-white/95 dark:bg-gray-900/95 backdrop-blur border-t border-gray-100 dark:border-gray-800 rounded-t-2xl shadow-[0_-4px_16px_rgba(0,0,0,0.06)]"
     style="padding-bottom: env(safe-area-inset-bottom);">
    <div class="grid grid-cols-5 px-1 pt-1.5">
        @foreach($bnItems as $item)
            <a href="{{ $item['url'] }}" class="relative flex flex-col items-center justify-center gap-1 py-2 active:scale-95 transition-transform">
                <span class="relative flex items-center justify-center w-10 h-7 rounded-full transition-colors {{ $item['active'] ? 'bg-orange-50 dark:bg-orange-500/15' : '' }}">
                    <svg class="w-5 h-5 {{ $item['active'] ? 'text-orange-600' : 'text-gray-500 dark:text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $item['active'] ? '2.5' : '2' }}" d="{{ $item['icon'] }}"/></svg>
                    @if(!empty($item['badge']) && $item['badge'] > 0)
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-[9px] rounded-full min-w-[16px] h-[16px] px-0.5 flex items-center justify-center font-bold">{{ $item['badge'] > 99 ? '99+' : $item['badge'] }}</span>
                    @endif
                </span>
                <span class="text-[10px] font-medium leading-none {{ $item['active'] ? 'text-orange-600' : 'text-gray-500 dark:text-gray-400' }}">{{ $item['label'] }}</span>
            </a>
        @endforeach

        @auth
            <a href="{{ route('account.dashboard') }}" class="relative flex flex-col items-center justify-center gap-1 py-2 active:scale-95 transition-transform">
                <span class="flex items-center justify-center w-10 h-7 rounded-full transition-colors {{ request()->routeIs('account.*') ? 'bg-orange-50 dark:bg-orange-500/15' : '' }}">
                    <span class="w-5 h-5 rounded-full bg-gradient-to-br from-orange-400 to-red-500 flex items-center justify-center">
                        <span class="text-white font-bold text-[9px] leading-none">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                    </span>
                </span>
                <span class="text-[10px] font-medium leading-none {{ request()->routeIs('account.*') ? 'text-orange-600' : 'text-gray-500 dark:text-gray-400' }}">Account</span>
            </a>
        @else
            <a href="{{ route('login') }}" class="relative flex flex-col items-center justify-center gap-1 py-2 active:scale-95 transition-transform">
                <span class="flex items-center justify-center w-10 h-7 rounded-full transition-colors {{ request()->routeIs('login') ? 'bg-orange-50 dark:bg-orange-500/15' : '' }}">
                    <svg class="w-5 h-5 {{ request()->routeIs('login') ? 'text-orange-600' : 'text-gray-500 dark:text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </span>
                <span class="text-[10px] font-medium leading-none {{ request()->routeIs('login') ? 'text-orange-600' : 'text-gray-500 dark:text-gray-400' }}">Login</span>
            </a>
        @endauth
    </div>
</nav>
