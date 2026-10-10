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
     footer content underneath it). Styled after an iOS tab bar specifically: real frosted
     glass (translucent + blur + saturate, not just a flat color), the active tab's icon
     switches from outline to a solid/filled version (not just a color change — that swap is
     what actually reads as "selected" at a glance the way Apple's own tab bars do), and taps
     get a spring-eased scale-down instead of a flat one. --}}
@php
    $bnCartCount = auth()->check()
        ? \App\Models\Cart::where('user_id', auth()->id())->sum('quantity')
        : \App\Models\Cart::where('session_id', session()->getId())->sum('quantity');

    $bnItems = [
        [
            'url' => route('home'), 'active' => request()->routeIs('home'), 'label' => 'Home',
            'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
            'iconFilled' => 'M11.47 3.841a.75.75 0 011.06 0l8.69 8.69a.75.75 0 101.06-1.061l-8.689-8.69a2.25 2.25 0 00-3.182 0l-8.69 8.69a.75.75 0 001.061 1.06l8.69-8.689z|M12 5.432l8.159 8.159c.03.031.06.06.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 01-.75-.75v-4.5a.75.75 0 00-.75-.75h-3a.75.75 0 00-.75.75V21a.75.75 0 01-.75.75H5.625a1.875 1.875 0 01-1.875-1.875v-6.198a2.29 2.29 0 00.091-.086L12 5.432z',
        ],
        [
            'url' => route('categories.index'), 'active' => request()->routeIs('categories.*'), 'label' => 'Categories',
            'icon' => 'M4 6h16M4 10h16M4 14h16M4 18h16',
            'iconFilled' => 'M4.5 4.5a3 3 0 00-3 3v2.25a3 3 0 003 3h2.25a3 3 0 003-3V7.5a3 3 0 00-3-3H4.5zM4.5 15a3 3 0 00-3 3v2.25a3 3 0 003 3h2.25a3 3 0 003-3V18a3 3 0 00-3-3H4.5zM15 4.5a3 3 0 00-3 3v2.25a3 3 0 003 3h2.25a3 3 0 003-3V7.5a3 3 0 00-3-3H15zM15 15a3 3 0 00-3 3v2.25a3 3 0 003 3h2.25a3 3 0 003-3V18a3 3 0 00-3-3H15z',
        ],
        [
            'url' => route('wishlist.index'), 'active' => request()->routeIs('wishlist.*'), 'label' => 'Wishlist',
            'icon' => 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z',
            'iconFilled' => 'M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001z',
        ],
        [
            'url' => route('cart.index'), 'active' => request()->routeIs('cart.*'), 'label' => 'Cart',
            'icon' => 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z',
            'badge' => $bnCartCount,
        ],
    ];
@endphp
<nav class="md:hidden fixed bottom-0 inset-x-0 z-40 bg-white/75 dark:bg-gray-900/75 backdrop-blur-xl backdrop-saturate-150 border-t border-gray-100/80 dark:border-gray-800/80 rounded-t-2xl shadow-[0_-4px_16px_rgba(0,0,0,0.08)]"
     style="padding-bottom: env(safe-area-inset-bottom);">
    <div class="grid grid-cols-5 px-1 pt-1.5">
        @foreach($bnItems as $item)
            <a href="{{ $item['url'] }}" class="relative flex flex-col items-center justify-center gap-1 py-2 active:scale-90 tap-spring transition-transform duration-200">
                <span class="relative flex items-center justify-center w-11 h-7 rounded-full transition-all {{ $item['active'] ? 'bg-gradient-to-b from-orange-400/20 to-orange-500/10 dark:from-orange-500/25 dark:to-orange-500/10 shadow-[0_0_0_1px_rgba(249,115,22,0.15)]' : '' }}">
                    @if($item['active'] && !empty($item['iconFilled']))
                        {{-- Solid/filled variant when active — an iOS tab bar's real "you are
                             here" signal, not just a color swap on the same outline glyph. --}}
                        <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="currentColor" viewBox="0 0 24 24">
                            @foreach(explode('|', $item['iconFilled']) as $path)
                                <path d="{{ $path }}"/>
                            @endforeach
                        </svg>
                    @else
                        <svg class="w-5 h-5 {{ $item['active'] ? 'text-orange-600 dark:text-orange-400' : 'text-gray-500 dark:text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $item['active'] ? '2.5' : '2' }}" d="{{ $item['icon'] }}"/></svg>
                    @endif
                    @if($item['label'] === 'Cart')
                        {{-- Always rendered (not just when > 0) and toggled via the 'hidden'
                             class instead — same pattern as the header's own #header-cart-count
                             badge, and for the same reason: now that the header's cart icon is
                             desktop-only (mobile relies on this tab as its one cart indicator),
                             the quick-add-to-cart AJAX handler in app.blade.php needs a
                             permanent element here to update live, not one that only exists in
                             the DOM when the page happened to load with items already in it. --}}
                        <span id="bottom-nav-cart-count" class="absolute -top-1 -right-1 bg-red-500 text-white text-[9px] rounded-full min-w-[16px] h-[16px] px-0.5 flex items-center justify-center font-bold {{ ($item['badge'] ?? 0) > 0 ? '' : 'hidden' }}">{{ ($item['badge'] ?? 0) > 99 ? '99+' : ($item['badge'] ?? 0) }}</span>
                    @elseif(!empty($item['badge']) && $item['badge'] > 0)
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-[9px] rounded-full min-w-[16px] h-[16px] px-0.5 flex items-center justify-center font-bold">{{ $item['badge'] > 99 ? '99+' : $item['badge'] }}</span>
                    @endif
                </span>
                <span class="text-[10px] font-medium leading-none {{ $item['active'] ? 'text-orange-600 dark:text-orange-400' : 'text-gray-500 dark:text-gray-400' }}">{{ $item['label'] }}</span>
            </a>
        @endforeach

        @auth
            <a href="{{ route('account.dashboard') }}" class="relative flex flex-col items-center justify-center gap-1 py-2 active:scale-90 tap-spring transition-transform duration-200">
                <span class="flex items-center justify-center w-11 h-7 rounded-full transition-all {{ request()->routeIs('account.*') ? 'bg-gradient-to-b from-orange-400/20 to-orange-500/10 dark:from-orange-500/25 dark:to-orange-500/10 shadow-[0_0_0_1px_rgba(249,115,22,0.15)]' : '' }}">
                    <span class="w-5 h-5 rounded-full bg-gradient-to-br from-orange-400 to-red-500 flex items-center justify-center">
                        <span class="text-white font-bold text-[9px] leading-none">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                    </span>
                </span>
                <span class="text-[10px] font-medium leading-none {{ request()->routeIs('account.*') ? 'text-orange-600 dark:text-orange-400' : 'text-gray-500 dark:text-gray-400' }}">Account</span>
            </a>
        @else
            <a href="{{ route('login') }}" class="relative flex flex-col items-center justify-center gap-1 py-2 active:scale-90 tap-spring transition-transform duration-200">
                <span class="flex items-center justify-center w-11 h-7 rounded-full transition-all {{ request()->routeIs('login') ? 'bg-gradient-to-b from-orange-400/20 to-orange-500/10 dark:from-orange-500/25 dark:to-orange-500/10 shadow-[0_0_0_1px_rgba(249,115,22,0.15)]' : '' }}">
                    <svg class="w-5 h-5 {{ request()->routeIs('login') ? 'text-orange-600 dark:text-orange-400' : 'text-gray-500 dark:text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </span>
                <span class="text-[10px] font-medium leading-none {{ request()->routeIs('login') ? 'text-orange-600 dark:text-orange-400' : 'text-gray-500 dark:text-gray-400' }}">Login</span>
            </a>
        @endauth
    </div>
</nav>
