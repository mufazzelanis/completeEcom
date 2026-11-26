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
    $bnCartCount = \App\Support\StorefrontCache::cartCount();
    $bnUnreadCount = auth()->check()
        ? \App\Models\UserNotification::where('user_id', auth()->id())->where('is_read', false)->count()
        : 0;

    // Same admin-configurable color as the product card's "Add to Cart" circle (Settings →
    // General → Quick "Add to Cart" Button Color) — not a hardcoded Tailwind gradient, since
    // that isn't covered by the storefront's brand-color override system (which only
    // intercepts plain bg-orange-*/text-orange-* classes, not gradient-stop utilities). This
    // used to follow the separate Buy Now gradient instead, which is why changing the new
    // Add to Cart color left this FAB looking untouched — it's a cart icon, so it reads as
    // "the cart color" to an admin, not "the buy-now color".
    $bnCartGradient = sprintf(
        'linear-gradient(to bottom right, %s, %s)',
        brand_button_color('add_to_cart_button_color'),
        brand_button_color('add_to_cart_button_hover_color'),
    );
    $bnCartTextColor = setting('add_to_cart_button_text_color', '#ffffff');

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
@php
    // the five slots in order — the sliding highlight sits under whichever one is active
    $bnAccountActive = auth()->check()
        ? request()->routeIs('account.*') && !request()->routeIs('account.notifications*')
        : request()->routeIs('login');
    $bnSlots = [
        $bnLeftItems[0]['active'], $bnLeftItems[1]['active'], request()->routeIs('cart.*'),
        $bnRightItems[0]['active'], $bnAccountActive,
    ];
    $bnActive = array_search(true, $bnSlots, true);
    $bnActive = $bnActive === false ? -1 : $bnActive;
@endphp
{{-- 2026-11 redesign: a floating glass "dock" a few px off the screen edges — the active tab
     gets a soft brand-coloured pill that slides to whichever tab is tapped (moved instantly on
     tap, so it never waits for the next page), and the centre cart is a glowing orb whose ring
     slowly spins while the cart has something in it. resources/js/cart-fx.js flies the product
     photo into this orb on every Add to Cart. --}}
<nav data-bottom-nav class="bn md:hidden fixed inset-x-0 bottom-0 z-40 px-2.5" aria-label="Main"
     style="padding-bottom: calc(env(safe-area-inset-bottom) + .45rem); --bn-active: {{ $bnActive }};">
    <div class="bn-dock">
        <span class="bn-pill {{ $bnActive < 0 || $bnActive === 2 ? 'is-off' : '' }}" aria-hidden="true"></span>

        @foreach(array_merge($bnLeftItems, [null], $bnRightItems, ['account']) as $i => $item)
            @if($item === null)
                {{-- centre: the cart orb --}}
                <a href="{{ route('cart.index') }}" aria-label="Cart" class="bn-cart" data-cart-target data-bn-i="2">
                    <span class="bn-orb {{ $bnCartCount > 0 ? 'has-items' : '' }}" data-cart-orb style="--bn-cart: {{ $bnCartGradient }}; color: {{ $bnCartTextColor }};">
                        <span class="bn-orb-ring" aria-hidden="true"></span>
                        <svg class="bn-orb-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        {{-- always in the DOM (hidden when empty): the AJAX add-to-cart updates it live --}}
                        <span id="bottom-nav-cart-count" class="bn-count {{ $bnCartCount > 0 ? '' : 'hidden' }}">{{ $bnCartCount > 99 ? '99+' : $bnCartCount }}</span>
                    </span>
                    <span class="bn-label {{ request()->routeIs('cart.*') ? 'is-on' : '' }}">Cart</span>
                </a>
            @elseif($item === 'account')
                @auth
                    <a href="{{ route('account.dashboard') }}" class="bn-tab {{ $bnAccountActive ? 'is-on' : '' }}" data-bn-i="4">
                        <span class="bn-ico"><span class="bn-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span></span>
                        <span class="bn-label">Account</span>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="bn-tab {{ $bnAccountActive ? 'is-on' : '' }}" data-bn-i="4">
                        <span class="bn-ico"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg></span>
                        <span class="bn-label">Login</span>
                    </a>
                @endauth
            @else
                <a href="{{ $item['url'] }}" class="bn-tab {{ $item['active'] ? 'is-on' : '' }}" data-bn-i="{{ $i }}">
                    <span class="bn-ico">
                        @if($item['active'] && !empty($item['iconFilled']))
                            <svg fill="currentColor" viewBox="0 0 24 24">@foreach(explode('|', $item['iconFilled']) as $path)<path d="{{ $path }}"/>@endforeach</svg>
                        @else
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/></svg>
                        @endif
                        @if(!empty($item['badge']) && $item['badge'] > 0)
                            <span class="bn-badge">{{ $item['badge'] > 99 ? '99+' : $item['badge'] }}</span>
                        @endif
                    </span>
                    <span class="bn-label">{{ $item['label'] }}</span>
                </a>
            @endif
        @endforeach
    </div>
</nav>
<style>
    .bn-dock { position: relative; display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); align-items: end; height: 3.85rem; border-radius: 1.4rem;
        background: rgba(255, 255, 255, .82); -webkit-backdrop-filter: blur(18px) saturate(1.6); backdrop-filter: blur(18px) saturate(1.6);
        box-shadow: 0 12px 32px -12px rgba(15, 23, 42, .35), 0 2px 8px -2px rgba(15, 23, 42, .08), inset 0 0 0 1px rgba(255, 255, 255, .7), 0 0 0 1px rgba(15, 23, 42, .06); }
    .dark .bn-dock { background: rgba(17, 24, 39, .82); box-shadow: 0 12px 32px -12px rgba(0, 0, 0, .7), inset 0 0 0 1px rgba(255, 255, 255, .06); }
    /* the sliding highlight: one fifth wide, moved by --bn-active (and instantly on tap) */
    .bn-pill { position: absolute; top: .4rem; bottom: .4rem; left: calc(var(--bn-active) * 20% + 1.5%); width: 17%; border-radius: 1rem;
        background: linear-gradient(180deg, rgb(var(--brand-500, 249 115 22) / .16), rgb(var(--brand-500, 249 115 22) / .07)); box-shadow: inset 0 0 0 1px rgb(var(--brand-500, 249 115 22) / .18);
        transition: left .38s cubic-bezier(.34, 1.4, .64, 1), opacity .2s; }
    .bn-pill.is-off { opacity: 0; }
    .bn-tab { position: relative; z-index: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .2rem; height: 100%; color: #6b7280; -webkit-tap-highlight-color: transparent; }
    .dark .bn-tab { color: #9ca3af; }
    .bn-tab:active .bn-ico { transform: scale(.82); }
    .bn-ico { position: relative; width: 1.45rem; height: 1.45rem; display: flex; align-items: center; justify-content: center; transition: transform .25s cubic-bezier(.34, 1.56, .64, 1); }
    .bn-ico svg { width: 100%; height: 100%; }
    .bn-tab.is-on { color: rgb(var(--brand-600, 234 88 12)); }
    .dark .bn-tab.is-on { color: rgb(var(--brand-400, 251 146 60)); }
    .bn-tab.is-on .bn-ico { transform: translateY(-1px) scale(1.06); }
    .bn-label { font-size: .64rem; font-weight: 600; line-height: 1; letter-spacing: .01em; }
    .bn-tab.is-on .bn-label { font-weight: 800; }
    .bn-badge { position: absolute; top: -.35rem; right: -.55rem; min-width: 1rem; height: 1rem; padding: 0 .2rem; border-radius: 999px; background: #ef4444; color: #fff; font-size: .56rem; font-weight: 800; display: flex; align-items: center; justify-content: center; box-shadow: 0 0 0 2px #fff; }
    .dark .bn-badge { box-shadow: 0 0 0 2px #111827; }
    .bn-avatar { width: 1.35rem; height: 1.35rem; border-radius: 999px; display: flex; align-items: center; justify-content: center; font-size: .6rem; font-weight: 800; color: #fff; background: linear-gradient(135deg, rgb(var(--brand-400)), rgb(var(--brand-600))); }
    /* the cart orb */
    .bn-cart { position: relative; z-index: 2; display: flex; flex-direction: column; align-items: center; gap: .25rem; margin-top: -1.65rem; padding-bottom: .5rem; -webkit-tap-highlight-color: transparent; }
    .bn-cart .bn-label { color: #4b5563; font-weight: 700; } .dark .bn-cart .bn-label { color: #d1d5db; }
    .bn-cart .bn-label.is-on { color: rgb(var(--brand-600, 234 88 12)); }
    .bn-orb { position: relative; width: 3.55rem; height: 3.55rem; border-radius: 999px; display: flex; align-items: center; justify-content: center; background-image: var(--bn-cart);
        box-shadow: 0 10px 22px -6px rgba(15, 23, 42, .45), 0 0 0 4px rgba(255, 255, 255, .95); transition: transform .3s cubic-bezier(.34, 1.56, .64, 1); }
    .dark .bn-orb { box-shadow: 0 10px 22px -6px rgba(0, 0, 0, .8), 0 0 0 4px rgba(17, 24, 39, .95); }
    .bn-cart:active .bn-orb { transform: scale(.88); }
    .bn-orb-icon { position: relative; z-index: 1; width: 1.5rem; height: 1.5rem; }
    /* a spinning conic glow round the orb while there's something in the cart */
    .bn-orb-ring { position: absolute; inset: -6px; border-radius: 999px; opacity: 0; transition: opacity .4s;
        background: conic-gradient(from 0deg, transparent 0 55%, rgb(var(--brand-300) / .95) 70%, rgb(var(--brand-500) / .9) 85%, transparent 100%);
        -webkit-mask: radial-gradient(farthest-side, transparent calc(100% - 3px), #000 calc(100% - 2px)); mask: radial-gradient(farthest-side, transparent calc(100% - 3px), #000 calc(100% - 2px)); }
    .bn-orb.has-items .bn-orb-ring { opacity: 1; animation: bnSpin 3.2s linear infinite; }
    @keyframes bnSpin { to { transform: rotate(360deg); } }
    .bn-count { position: absolute; z-index: 2; top: -.25rem; right: -.3rem; min-width: 1.3rem; height: 1.3rem; padding: 0 .25rem; border-radius: 999px; background: #fff; color: #dc2626; font-size: .68rem; font-weight: 900;
        display: flex; align-items: center; justify-content: center; box-shadow: 0 0 0 2px #ef4444, 0 4px 10px -2px rgba(239, 68, 68, .6); }
    .bn-count.hidden { display: none; }
    @media (prefers-reduced-motion: reduce) { .bn-orb.has-items .bn-orb-ring { animation: none; } .bn-pill { transition: none; } }
</style>
<script>
    // move the highlight the moment a tab is tapped — the next page renders it in the same place
    (function () {
        if (window.__bnTap) return; window.__bnTap = 1;
        document.addEventListener('click', function (e) {
            var a = e.target.closest && e.target.closest('[data-bottom-nav] [data-bn-i]');
            if (!a) return;
            var nav = a.closest('[data-bottom-nav]'), i = +a.dataset.bnI, pill = nav.querySelector('.bn-pill');
            nav.style.setProperty('--bn-active', i);
            pill && pill.classList.toggle('is-off', i === 2);
            nav.querySelectorAll('.bn-tab').forEach(function (t) { t.classList.toggle('is-on', t === a); });
        });
    })();
</script>
