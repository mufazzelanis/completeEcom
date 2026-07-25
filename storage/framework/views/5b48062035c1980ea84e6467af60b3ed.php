<?php
$siteName = setting('site_name', 'ShopVista');
$logoUrl  = setting_file_url('site_logo');
$unread   = \App\Models\UserNotification::where('user_id', auth()->id())->where('is_read', false)->count();
$cartCount = \App\Models\Cart::where('user_id', auth()->id())->sum('quantity');
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', 'My Account'); ?> - <?php echo e($siteName); ?></title>
    <?php if($faviconUrl = setting_file_url('favicon')): ?><link rel="icon" href="<?php echo e($faviconUrl); ?>"><?php endif; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="bg-gray-50 font-sans antialiased" x-data="{ menuOpen: false }">


<nav class="bg-white shadow-sm sticky top-0 z-50 border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-14 flex items-center justify-between">
        <a href="<?php echo e(route('home')); ?>" class="flex items-center gap-2">
            <?php if($logoUrl): ?>
                <img src="<?php echo e($logoUrl); ?>" alt="<?php echo e($siteName); ?>" class="h-7 max-w-[120px] object-contain">
            <?php else: ?>
                <div class="w-7 h-7 bg-orange-500 rounded-lg flex items-center justify-center">
                    <span class="text-white font-bold text-sm"><?php echo e(strtoupper(substr($siteName, 0, 1))); ?></span>
                </div>
                <span class="font-bold text-orange-600"><?php echo e($siteName); ?></span>
            <?php endif; ?>
        </a>

        <div class="flex items-center gap-3">
            <a href="<?php echo e(route('account.notifications')); ?>" class="relative text-gray-500 hover:text-orange-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                <?php if($unread > 0): ?><span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center font-bold"><?php echo e($unread > 9 ? '9+' : $unread); ?></span><?php endif; ?>
            </a>
            <a href="<?php echo e(route('cart.index')); ?>" class="relative text-gray-500 hover:text-orange-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <?php if($cartCount > 0): ?><span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center font-bold"><?php echo e($cartCount > 9 ? '9+' : $cartCount); ?></span><?php endif; ?>
            </a>
            <a href="<?php echo e(route('shop.index')); ?>" class="text-sm text-gray-500 hover:text-orange-600 transition hidden sm:block">Store</a>

            
            <button @click="menuOpen = !menuOpen" class="flex items-center gap-2 pl-1 pr-1 py-1 rounded-full hover:bg-gray-100 transition">
                <div class="w-7 h-7 bg-gradient-to-br from-orange-400 to-red-500 rounded-full flex items-center justify-center flex-shrink-0">
                    <span class="text-white font-bold text-xs"><?php echo e(strtoupper(substr(auth()->user()->name, 0, 1))); ?></span>
                </div>
                <svg class="w-3.5 h-3.5 text-gray-400 transition-transform" :class="menuOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
        </div>
    </div>
</nav>


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
            <img src="<?php echo e(auth()->user()->avatar_url); ?>" class="w-11 h-11 rounded-full object-cover flex-shrink-0">
            <div class="min-w-0">
                <p class="text-sm font-bold text-gray-900 truncate"><?php echo e(auth()->user()->name); ?></p>
                <p class="text-xs text-gray-500 truncate mt-0.5"><?php echo e(auth()->user()->email); ?></p>
            </div>
        </div>
        
        <div class="flex-1 overflow-y-auto py-2">
            <?php
                $mobileNav = fn(string $route, string $icon, string $label, string $match = '') =>
                    ['route' => $route, 'icon' => $icon, 'label' => $label, 'match' => $match ?: $route];
                $mobileItems = [
                    $mobileNav('account.dashboard', 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', 'Dashboard'),
                    $mobileNav('account.profile', 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z', 'My Profile'),
                    $mobileNav('account.addresses.index', 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z', 'Address Book', 'account.addresses.*'),
                    $mobileNav('orders.index', 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z', 'My Orders', 'orders.*'),
                    $mobileNav('account.returns.index', 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15', 'My Returns', 'account.returns.*'),
                    $mobileNav('wishlist.index', 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z', 'Wishlist', 'wishlist.*'),
                    $mobileNav('account.reviews.index', 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z', 'My Reviews', 'account.reviews.*'),
                    $mobileNav('account.referral', 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z', 'Referral Program'),
                    $mobileNav('vendor.apply', 'M3 3h18v4H3V3zm0 7h18v11H3V10zm4 4h4', 'Become a Seller'),
                    $mobileNav('account.support.index', 'M18 2a2 2 0 012 2v12a2 2 0 01-2 2H6l-4 4V4a2 2 0 012-2h14z', 'Support Tickets', 'account.support.*'),
                    $mobileNav('account.notifications', 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9', 'Notifications', 'account.notifications*'),
                    $mobileNav('account.security', 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', 'Security'),
                ];
            ?>
            <?php $__currentLoopData = $mobileItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php $isActive = request()->routeIs($item['match']); ?>
                <a href="<?php echo e(route($item['route'])); ?>" @click="menuOpen = false"
                   class="flex items-center gap-3 px-5 py-3 text-sm font-medium transition <?php echo e($isActive ? 'bg-orange-50 text-orange-600' : 'text-gray-700 hover:bg-gray-50'); ?>">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo e($item['icon']); ?>"/></svg>
                    <?php echo e($item['label']); ?>

                    <?php if($item['route'] === 'account.notifications' && $unread > 0): ?>
                        <span class="ml-auto bg-red-500 text-white text-xs rounded-full px-1.5 py-0.5 font-semibold leading-none"><?php echo e($unread); ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        
        <div class="border-t border-gray-100 px-5 py-3">
            <form method="POST" action="<?php echo e(route('logout')); ?>">
                <?php echo csrf_field(); ?>
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

        
        <aside class="w-64 flex-shrink-0 hidden lg:block">
            <div class="space-y-1 sticky top-20">
                
                <div class="bg-white rounded-2xl p-4 mb-3 flex items-center gap-3 shadow-sm">
                    <img src="<?php echo e(auth()->user()->avatar_url); ?>" class="w-11 h-11 rounded-full object-cover flex-shrink-0">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-800 truncate"><?php echo e(auth()->user()->name); ?></p>
                        <p class="text-xs text-gray-400 truncate"><?php echo e(auth()->user()->email); ?></p>
                    </div>
                </div>

                <nav class="bg-white rounded-2xl shadow-sm overflow-hidden">
                    <?php
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
                    ?>

                    <?php $inGroup = false; ?>
                    <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php if(isset($item['group'])): ?>
                            <?php if($inGroup): ?></div><?php endif; ?>
                            <div class="px-3 py-2 border-b border-gray-50">
                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider"><?php echo e($item['group']); ?></p>
                            </div>
                            <div>
                            <?php $inGroup = true; ?>
                        <?php else: ?>
                            <?php
                                $isActive = request()->routeIs($item['match']);
                            ?>
                            <a href="<?php echo e(route($item['route'])); ?>"
                                class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium transition <?php echo e($isActive ? 'bg-orange-50 text-orange-600 border-r-2 border-orange-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'); ?>">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo e($item['icon']); ?>"/>
                                </svg>
                                <span><?php echo e($item['label']); ?></span>
                                <?php if($item['route'] === 'account.notifications' && $unread > 0): ?>
                                    <span class="ml-auto bg-red-500 text-white text-xs rounded-full px-1.5 py-0.5 font-semibold leading-none"><?php echo e($unread); ?></span>
                                <?php endif; ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php if($inGroup): ?></div><?php endif; ?>

                    <div class="border-t border-gray-100">
                        <form method="POST" action="<?php echo e(route('logout')); ?>">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="w-full flex items-center gap-3 px-4 py-3 text-sm font-medium text-red-500 hover:bg-red-50 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                Logout
                            </button>
                        </form>
                    </div>
            </nav>
            </div>
        </aside>

        
        <main class="flex-1 min-w-0">
            
            <div class="flex items-center gap-3 mb-4 lg:hidden">
                <a href="<?php echo e(route('shop.index')); ?>" class="w-9 h-9 bg-white rounded-xl shadow-sm flex items-center justify-center text-gray-500 hover:text-orange-600 transition flex-shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </a>
                <h1 class="text-lg font-bold text-gray-800 truncate"><?php echo $__env->yieldContent('pageTitle', 'My Account'); ?></h1>
            </div>
            <?php if(session('success')): ?>
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                 class="mb-4 bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm flex items-center justify-between">
                <span><?php echo e(session('success')); ?></span>
                <button @click="show = false" class="text-green-400 hover:text-green-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <?php endif; ?>
            <?php if(session('error')): ?>
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm"><?php echo e(session('error')); ?></div>
            <?php endif; ?>
            <?php echo $__env->yieldContent('content'); ?>
        </main>

    </div>
</div>

</body>
</html>
<?php /**PATH C:\xampp\htdocs\completeEcom\resources\views/layouts/account.blade.php ENDPATH**/ ?>