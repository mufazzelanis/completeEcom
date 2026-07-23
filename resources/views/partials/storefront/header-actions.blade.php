{{-- Right Actions --}}
<div class="flex items-center gap-1 md:gap-3">
    {{-- Language Switcher --}}
    @if($showLanguageSwitcher ?? false)
    <div class="relative hidden md:block" x-data="{ open: false }">
        <button @click="open = !open" class="flex items-center gap-1.5 px-2 py-2 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition text-sm">
            <span>{{ $currentLanguage->flag_emoji ?? '🌐' }}</span>
            <span class="font-medium">{{ $currentLanguage->code ?? '' }}</span>
            <svg class="w-3 h-3 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div x-show="open" @click.outside="open = false" x-cloak x-transition
            class="absolute right-0 mt-2 w-44 bg-white dark:bg-gray-800 rounded-xl shadow-xl py-2 z-50 border border-gray-100 dark:border-gray-700">
            @foreach($activeLanguages as $lang)
                <a href="{{ request()->fullUrlWithQuery(['lang' => $lang->code]) }}"
                   class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-orange-50 dark:hover:bg-gray-700 transition {{ ($currentLanguage->code ?? '') === $lang->code ? 'text-orange-600 font-semibold' : 'text-gray-700 dark:text-gray-200' }}">
                    <span>{{ $lang->flag_emoji }}</span>
                    <span>{{ $lang->native_name }}</span>
                </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Theme Toggle --}}
    <button @click="$store.theme.toggle()" type="button"
        class="p-2 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition"
        :aria-label="$store.theme.dark ? 'Switch to light mode' : 'Switch to dark mode'">
        <svg x-show="!$store.theme.dark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
        <svg x-show="$store.theme.dark" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
    </button>

    {{-- Mobile Search --}}
    <a href="{{ route('shop.index') }}" class="md:hidden p-2 text-gray-600 dark:text-gray-300 hover:text-orange-500">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
    </a>

    {{-- Cart --}}
    <a href="{{ route('cart.index') }}" class="relative p-2 text-gray-600 dark:text-gray-300 hover:text-orange-500 transition group">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
        @php
            $cartCount = auth()->check()
                ? \App\Models\Cart::where('user_id', auth()->id())->sum('quantity')
                : \App\Models\Cart::where('session_id', session()->getId())->sum('quantity');
        @endphp
        @if($cartCount > 0)
            <span class="absolute -top-0.5 -right-0.5 bg-red-500 text-white text-[10px] rounded-full min-w-[18px] h-[18px] flex items-center justify-center font-bold pulse-badge">{{ $cartCount }}</span>
        @endif
    </a>

    {{-- Wishlist --}}
    @auth
    <a href="{{ route('wishlist.index') }}" class="hidden md:block p-2 text-gray-600 dark:text-gray-300 hover:text-red-500 transition">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
    </a>
    @endauth

    {{-- User Menu --}}
    @auth
        <div class="relative hidden md:block" x-data="{ open: false }">
            <button @click="open = !open" class="flex items-center gap-2 px-3 py-1.5 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 transition text-gray-700 dark:text-gray-200">
                <div class="w-7 h-7 bg-gradient-to-br from-orange-400 to-red-500 rounded-full flex items-center justify-center">
                    <span class="text-white font-bold text-xs">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                </div>
                <span class="font-medium text-sm max-w-[100px] truncate">{{ auth()->user()->name }}</span>
                <svg class="w-3.5 h-3.5 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open" @click.outside="open = false" x-cloak x-transition
                class="absolute right-0 mt-2 w-56 bg-white dark:bg-gray-800 rounded-xl shadow-xl py-2 z-50 border border-gray-100 dark:border-gray-700">
                <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                    <p class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ auth()->user()->email }}</p>
                </div>
                <a href="{{ route('account.dashboard') }}" class="flex items-center px-4 py-2.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-orange-50 dark:hover:bg-gray-700 hover:text-orange-600 transition gap-3">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    {{ t('header.my_account', 'My Account', [], 'header') }}
                </a>
                <a href="{{ route('orders.index') }}" class="flex items-center px-4 py-2.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-orange-50 dark:hover:bg-gray-700 hover:text-orange-600 transition gap-3">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    {{ t('header.my_orders', 'My Orders', [], 'header') }}
                </a>
                <a href="{{ route('wishlist.index') }}" class="flex items-center px-4 py-2.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-orange-50 dark:hover:bg-gray-700 hover:text-orange-600 transition gap-3">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                    {{ t('header.wishlist', 'Wishlist', [], 'header') }}
                </a>
                @if(auth()->user()->isAdmin())
                    <div class="border-t border-gray-100 dark:border-gray-700 mt-1 pt-1">
                        <a href="{{ route('admin.dashboard') }}" class="flex items-center px-4 py-2.5 text-sm text-orange-600 hover:bg-orange-50 dark:hover:bg-gray-700 font-medium gap-3">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            {{ t('header.admin_panel', 'Admin Panel', [], 'header') }}
                        </a>
                    </div>
                @endif
                <div class="border-t border-gray-100 dark:border-gray-700 mt-1 pt-1">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex items-center w-full px-4 py-2.5 text-sm text-red-500 hover:bg-red-50 dark:hover:bg-gray-700 transition gap-3">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            {{ t('header.logout', 'Logout', [], 'header') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @else
        <a href="{{ route('login') }}" class="hidden md:block text-gray-600 dark:text-gray-300 hover:text-orange-500 font-medium text-sm px-3 py-1.5 rounded hover:bg-gray-100 dark:hover:bg-gray-800 transition">{{ t('header.login', 'Login', [], 'header') }}</a>
        <a href="{{ route('register') }}" class="hidden md:block bg-orange-500 text-white px-4 py-1.5 rounded text-sm font-medium hover:bg-orange-600 transition">{{ t('header.signup', 'Sign Up', [], 'header') }}</a>
    @endauth

    {{-- Mobile Menu Toggle --}}
    <button @click="mobileOpen = !mobileOpen" class="md:hidden p-2 text-gray-600 dark:text-gray-300" aria-label="Toggle menu">
        <svg x-show="!mobileOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        <svg x-show="mobileOpen" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
    </button>
</div>
