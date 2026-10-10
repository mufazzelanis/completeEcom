{{-- Mobile app-style bottom tab bar — hidden on md+ (desktop already has the full header
     nav up top). This is the single biggest thing that makes a mobile site feel like an
     installed app rather than "a website you're viewing in a browser": the handful of
     things people reach for constantly sit in exactly the same spot on every page, one
     thumb-reach away, instead of behind a menu that has to be opened first every time.

     Home / Orders / Cart / Inbox / Account — not the original Home / Categories / Wishlist
     / Cart / Account. Categories dropped because the homepage already surfaces a full
     category grid on screen (this tab was pointing at something the visitor can already see
     without it), Wishlist dropped as not worth the slot; Orders and Inbox (account
     notifications) added as what people actually come back to check repeatedly post-
     purchase. Cart moved to the center and given its own raised circular "FAB" treatment —
     negative margin lifts it clear of the bar, a ring the same color as the bar's own
     background creates the cutout look — rather than being one more same-sized icon in the
     row, since it's the one action here that leads straight to a sale.

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
    $bnUnreadCount = auth()->check()
        ? \App\Models\UserNotification::where('user_id', auth()->id())->where('is_read', false)->count()
        : 0;

    // Left pair (Home, Orders) and right pair (Inbox, Account/Login) — Cart renders
    // separately, straight into the grid's center column, so it can look nothing like its
    // siblings without fighting a @foreach built around them all looking the same.
    $bnLeftItems = [
        [
            'url' => route('home'), 'active' => request()->routeIs('home'), 'label' => 'Home',
            'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
            'iconFilled' => 'M11.47 3.841a.75.75 0 011.06 0l8.69 8.69a.75.75 0 101.06-1.061l-8.689-8.69a2.25 2.25 0 00-3.182 0l-8.69 8.69a.75.75 0 001.061 1.06l8.69-8.689z|M12 5.432l8.159 8.159c.03.031.06.06.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 01-.75-.75v-4.5a.75.75 0 00-.75-.75h-3a.75.75 0 00-.75.75V21a.75.75 0 01-.75.75H5.625a1.875 1.875 0 01-1.875-1.875v-6.198a2.29 2.29 0 00.091-.086L12 5.432z',
        ],
        [
            'url' => route('orders.index'), 'active' => request()->routeIs('orders.*'), 'label' => 'Orders',
            'icon' => 'M9 17V7a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m-6 0H5a2 2 0 01-2-2V9a2 2 0 012-2h.5m13.5 10a2 2 0 002-2V9a2 2 0 00-2-2h-.5',
            'iconFilled' => 'M5.625 1.5c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0016.5 9h-1.875a1.875 1.875 0 01-1.875-1.875V5.25A3.75 3.75 0 009 1.5H5.625z|M12.971 1.816A5.23 5.23 0 0114.25 5.25v1.875c0 .207.168.375.375.375H16.5a5.23 5.23 0 013.434 1.279 9.768 9.768 0 00-6.963-6.963z',
        ],
    ];
    $bnRightItems = [
        [
            'url' => route('account.notifications'), 'active' => request()->routeIs('account.notifications*'), 'label' => 'Inbox',
            'icon' => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
            'iconFilled' => 'M5.85 3.5a.75.75 0 00-1.117-1.004L1.75 5.653a.75.75 0 00.017 1.021l2.983 3a.75.75 0 101.06-1.06L3.95 6.75h2.65A5.25 5.25 0 0112 15.25a.75.75 0 001.5 0A6.75 6.75 0 006.6 5.25H3.95l1.86-1.75z',
            'badge' => $bnUnreadCount,
        ],
    ];
@endphp
<nav class="md:hidden fixed bottom-0 inset-x-0 z-40 bg-white/75 dark:bg-gray-900/75 backdrop-blur-xl backdrop-saturate-150 border-t border-gray-100/80 dark:border-gray-800/80 rounded-t-2xl shadow-[0_-4px_16px_rgba(0,0,0,0.08)]"
     style="padding-bottom: env(safe-area-inset-bottom);">
    <div class="grid grid-cols-5 px-1 pt-1.5">
        @foreach($bnLeftItems as $item)
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
                </span>
                <span class="text-[10px] font-medium leading-none {{ $item['active'] ? 'text-orange-600 dark:text-orange-400' : 'text-gray-500 dark:text-gray-400' }}">{{ $item['label'] }}</span>
            </a>
        @endforeach

        {{-- Cart — raised circular button in the center column. -mt-7 lifts it clear of the
             bar's top edge; the ring (same color as the bar's own frosted background) is what
             makes it read as "cut into" the bar rather than just floating independently above
             it. Always the gradient/white-icon treatment regardless of active state — this is
             the one tab meant to stand out at a glance on every page, not just when selected. --}}
        <a href="{{ route('cart.index') }}" aria-label="Cart" class="relative flex flex-col items-center justify-center -mt-7 active:scale-90 tap-spring transition-transform duration-200">
            <span class="relative flex items-center justify-center w-14 h-14 rounded-full bg-gradient-to-br from-orange-500 to-red-500 shadow-lg shadow-orange-500/40 ring-[5px] ring-white/75 dark:ring-gray-900/75">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                {{-- Always rendered (not just when > 0) and toggled via the 'hidden' class —
                     the quick-add-to-cart AJAX handler in app.blade.php needs a permanent
                     element here to update live, not one that only exists in the DOM when the
                     page happened to load with items already in it. --}}
                <span id="bottom-nav-cart-count" class="absolute -top-1 -right-1 bg-white text-red-600 text-[10px] rounded-full min-w-[18px] h-[18px] px-0.5 flex items-center justify-center font-extrabold ring-2 ring-red-500 {{ $bnCartCount > 0 ? '' : 'hidden' }}">{{ $bnCartCount > 99 ? '99+' : $bnCartCount }}</span>
            </span>
            <span class="text-[10px] font-semibold leading-none mt-1 {{ request()->routeIs('cart.*') ? 'text-orange-600 dark:text-orange-400' : 'text-gray-600 dark:text-gray-300' }}">Cart</span>
        </a>

        @foreach($bnRightItems as $item)
            <a href="{{ $item['url'] }}" class="relative flex flex-col items-center justify-center gap-1 py-2 active:scale-90 tap-spring transition-transform duration-200">
                <span class="relative flex items-center justify-center w-11 h-7 rounded-full transition-all {{ $item['active'] ? 'bg-gradient-to-b from-orange-400/20 to-orange-500/10 dark:from-orange-500/25 dark:to-orange-500/10 shadow-[0_0_0_1px_rgba(249,115,22,0.15)]' : '' }}">
                    @if($item['active'] && !empty($item['iconFilled']))
                        <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="currentColor" viewBox="0 0 24 24">
                            @foreach(explode('|', $item['iconFilled']) as $path)
                                <path d="{{ $path }}"/>
                            @endforeach
                        </svg>
                    @else
                        <svg class="w-5 h-5 {{ $item['active'] ? 'text-orange-600 dark:text-orange-400' : 'text-gray-500 dark:text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $item['active'] ? '2.5' : '2' }}" d="{{ $item['icon'] }}"/></svg>
                    @endif
                    @if(!empty($item['badge']) && $item['badge'] > 0)
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-[9px] rounded-full min-w-[16px] h-[16px] px-0.5 flex items-center justify-center font-bold">{{ $item['badge'] > 99 ? '99+' : $item['badge'] }}</span>
                    @endif
                </span>
                <span class="text-[10px] font-medium leading-none {{ $item['active'] ? 'text-orange-600 dark:text-orange-400' : 'text-gray-500 dark:text-gray-400' }}">{{ $item['label'] }}</span>
            </a>
        @endforeach

        @auth
            <a href="{{ route('account.dashboard') }}" class="relative flex flex-col items-center justify-center gap-1 py-2 active:scale-90 tap-spring transition-transform duration-200">
                <span class="flex items-center justify-center w-11 h-7 rounded-full transition-all {{ request()->routeIs('account.*') && !request()->routeIs('account.notifications*') ? 'bg-gradient-to-b from-orange-400/20 to-orange-500/10 dark:from-orange-500/25 dark:to-orange-500/10 shadow-[0_0_0_1px_rgba(249,115,22,0.15)]' : '' }}">
                    <span class="w-5 h-5 rounded-full bg-gradient-to-br from-orange-400 to-red-500 flex items-center justify-center">
                        <span class="text-white font-bold text-[9px] leading-none">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                    </span>
                </span>
                <span class="text-[10px] font-medium leading-none {{ request()->routeIs('account.*') && !request()->routeIs('account.notifications*') ? 'text-orange-600 dark:text-orange-400' : 'text-gray-500 dark:text-gray-400' }}">Account</span>
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
