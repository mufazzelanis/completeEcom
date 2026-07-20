<?php
$siteName     = setting('site_name', 'ShopVista');
$siteTagline  = setting('site_tagline', 'Your one-stop shop for everything you need.');
$logoUrl      = setting_file_url('site_logo');
$faviconUrl   = setting_file_url('favicon');
$primaryColor = setting('primary_color', '#f97316');
$gaId         = setting('google_analytics_id', '');
$gtmId        = setting('google_tag_manager_id', '');
$pixelId      = setting('facebook_pixel_id', '');
$customCss    = setting('custom_css', '');
$announcementEnabled = setting('announcement_enabled', '0') === '1';
$announcementText    = setting('announcement_text', '');
?>
<!DOCTYPE html>
<html lang="<?php echo e(setting('default_language', 'en')); ?>" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <meta name="description" content="<?php echo $__env->yieldContent('meta_description', setting('seo_meta_description', $siteTagline)); ?>">
    <title><?php echo $__env->yieldContent('title', setting('seo_meta_title', $siteName . ' – Online Store')); ?></title>
    <?php if($faviconUrl): ?><link rel="icon" href="<?php echo e($faviconUrl); ?>"><?php endif; ?>
    <?php if(setting('google_site_verification')): ?><meta name="google-site-verification" content="<?php echo e(setting('google_site_verification')); ?>"><?php endif; ?>
    <script>
        // Applied before first paint so there's never a flash of the wrong theme.
        (function () {
            var stored = localStorage.getItem('site-theme');
            var isDark = stored ? stored === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.classList.toggle('dark', isDark);
        })();
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: { 50:'#fff7ed',100:'#ffedd5',200:'#fed7aa',300:'#fdba74',400:'#fb923c',500:'#f97316',600:'#ea580c',700:'#c2410c',800:'#9a3412',900:'#7c2d12' },
                    }
                }
            }
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
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
    <style>
        [x-cloak]{display:none!important}
        .scrollbar-hide::-webkit-scrollbar{display:none}
        .scrollbar-hide{-ms-overflow-style:none;scrollbar-width:none}
        .carousel-container{scroll-snap-type:x mandatory;-webkit-overflow-scrolling:touch}
        .carousel-container > *{scroll-snap-align:start}
        @keyframes slideIn{from{opacity:0;transform:translateX(30px)}to{opacity:1;transform:translateX(0)}}
        .animate-slide-in{animation:slideIn .4s ease-out}
        @keyframes pulse-badge{0%,100%{transform:scale(1)}50%{transform:scale(1.05)}}
        .pulse-badge{animation:pulse-badge 2s infinite}
        .fade-in{animation:fadeIn .3s ease-in}
        @keyframes fadeIn{from{opacity:0}to{opacity:1}}
    </style>
    <?php if($customCss): ?><style><?php echo e($customCss); ?></style><?php endif; ?>
    <?php if($gaId): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo e($gaId); ?>"></script>
    <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','<?php echo e($gaId); ?>');</script>
    <?php endif; ?>
    <?php if($gtmId): ?>
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','<?php echo e($gtmId); ?>');</script>
    <?php endif; ?>
    <?php if($pixelId): ?>
    <script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','<?php echo e($pixelId); ?>');fbq('track','PageView');</script>
    <?php endif; ?>
</head>
<body class="bg-gray-100 dark:bg-gray-950 font-sans antialiased transition-colors">
<?php if($gtmId): ?><noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo e($gtmId); ?>" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript><?php endif; ?>

<?php
$navCategories = \App\Models\Category::with(['children' => fn($q) => $q->where('is_active', true)->orderBy('sort_order')])
    ->whereNull('parent_id')->where('is_active', true)->orderBy('sort_order')->take(12)->get();
?>


<div class="bg-gray-800 text-gray-300 text-xs hidden md:block">
    <div class="max-w-[1200px] mx-auto px-4 flex items-center justify-between h-8">
        <div class="flex items-center gap-4">
            <span>Welcome to <?php echo e($siteName); ?></span>
            <span class="text-gray-600">|</span>
            <a href="<?php echo e(route('vendor.apply')); ?>" class="hover:text-white transition">Sell on <?php echo e($siteName); ?></a>
        </div>
        <div class="flex items-center gap-4">
            <a href="<?php echo e(route('guest.order.track.form')); ?>" class="hover:text-white transition">Track Order</a>
            <span class="text-gray-600">|</span>
            <a href="<?php echo e(route('faq')); ?>" class="hover:text-white transition">Help Center</a>
        </div>
    </div>
</div>


<?php if($announcementEnabled && $announcementText): ?>
<div style="background: linear-gradient(90deg, #f97316, #ea580c);" class="text-sm py-1.5 text-center font-medium px-4 text-white" x-data="{ show: true }" x-show="show">
    <?php echo $announcementText; ?>

    <button @click="show = false" class="ml-3 opacity-70 hover:opacity-100 text-lg leading-none">&times;</button>
</div>
<?php endif; ?>


<header class="bg-white dark:bg-gray-900 shadow-sm sticky top-0 z-50 transition-colors" x-data="{ mobileOpen: false }">
    <div class="max-w-[1200px] mx-auto px-4">
        <div class="flex items-center justify-between h-14 md:h-16 gap-4">
            
            <a href="<?php echo e(route('home')); ?>" class="flex-shrink-0 flex items-center gap-2">
                <?php if($logoUrl): ?>
                    <img src="<?php echo e($logoUrl); ?>" alt="<?php echo e($siteName); ?>" class="h-8 md:h-10 max-w-[140px] object-contain">
                <?php else: ?>
                    <div class="w-8 h-8 bg-gradient-to-br from-orange-500 to-red-500 rounded-lg flex items-center justify-center">
                        <span class="text-white font-bold text-lg"><?php echo e(strtoupper(substr($siteName,0,1))); ?></span>
                    </div>
                    <span class="text-lg md:text-xl font-extrabold text-gray-800 dark:text-gray-100 hidden sm:block"><?php echo e($siteName); ?></span>
                <?php endif; ?>
            </a>

            
            <div class="hidden md:flex flex-1 max-w-2xl">
                <div class="w-full relative" x-data="{
                    query: '<?php echo e(addslashes(request('search', ''))); ?>',
                    results: { products: [], categories: [] },
                    open: false,
                    async fetchSuggestions() {
                        if (this.query.length < 2) { this.open = false; return; }
                        try {
                            const res = await fetch('/search/suggest?q=' + encodeURIComponent(this.query));
                            this.results = await res.json();
                            this.open = this.results.products.length > 0 || this.results.categories.length > 0;
                        } catch(e) {}
                    }
                }" @click.outside="open = false">
                    <form action="<?php echo e(route('shop.index')); ?>" method="GET" class="w-full flex" @submit="open = false">
                        <input type="text" name="search"
                            x-model="query"
                            @input.debounce.300ms="fetchSuggestions()"
                            @focus="query.length > 1 && fetchSuggestions()"
                            @keydown.escape="open = false"
                            placeholder="Search in <?php echo e($siteName); ?>"
                            class="w-full border-2 border-orange-400 rounded-l-md px-4 py-2 focus:outline-none focus:border-orange-500 text-sm bg-orange-50/50"
                            autocomplete="off">
                        <button type="submit" class="bg-orange-500 text-white px-6 py-2 rounded-r-md hover:bg-orange-600 flex-shrink-0 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </button>
                    </form>
                    
                    <div x-show="open" x-cloak x-transition
                         class="absolute top-full left-0 right-0 bg-white dark:bg-gray-800 rounded-b-lg shadow-2xl border border-gray-100 dark:border-gray-700 z-[200] overflow-hidden fade-in">
                        <template x-if="results.categories && results.categories.length > 0">
                            <div class="border-b border-gray-100 dark:border-gray-700">
                                <p class="px-4 pt-3 pb-1 text-[10px] font-bold text-orange-400 uppercase tracking-wider">Categories</p>
                                <template x-for="cat in results.categories" :key="cat.url">
                                    <a :href="cat.url" @click="open = false"
                                       class="flex items-center px-4 py-2 hover:bg-orange-50 dark:hover:bg-gray-700 gap-2 text-sm text-gray-700 dark:text-gray-200 hover:text-orange-600 transition">
                                        <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                        <span x-text="cat.name"></span>
                                    </a>
                                </template>
                            </div>
                        </template>
                        <template x-if="results.products && results.products.length > 0">
                            <div>
                                <p class="px-4 pt-3 pb-1 text-[10px] font-bold text-orange-400 uppercase tracking-wider">Products</p>
                                <template x-for="product in results.products" :key="product.url">
                                    <a :href="product.url" @click="open = false"
                                       class="flex items-center px-4 py-2.5 hover:bg-orange-50 dark:hover:bg-gray-700 gap-3 transition">
                                        <div class="w-10 h-10 bg-gray-100 rounded overflow-hidden flex-shrink-0 flex items-center justify-center">
                                            <img x-show="product.image" :src="product.image" :alt="product.name" class="w-full h-full object-cover">
                                            <svg x-show="!product.image" class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-800 dark:text-gray-100 truncate" x-text="product.name"></p>
                                            <p class="text-xs font-bold text-orange-500" x-text="product.price"></p>
                                        </div>
                                    </a>
                                </template>
                                <div class="px-4 py-2.5 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                                    <a :href="'<?php echo e(route('shop.index')); ?>?search=' + encodeURIComponent(query)"
                                       class="text-xs text-orange-500 hover:text-orange-700 font-semibold">
                                        See all results for "<span x-text="query"></span>" →
                                    </a>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            
            <div class="flex items-center gap-1 md:gap-3">
                
                <button @click="$store.theme.toggle()" type="button"
                    class="p-2 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                    :aria-label="$store.theme.dark ? 'Switch to light mode' : 'Switch to dark mode'">
                    <svg x-show="!$store.theme.dark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    <svg x-show="$store.theme.dark" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </button>

                
                <a href="<?php echo e(route('shop.index')); ?>" class="md:hidden p-2 text-gray-600 dark:text-gray-300 hover:text-orange-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </a>

                
                <a href="<?php echo e(route('cart.index')); ?>" class="relative p-2 text-gray-600 dark:text-gray-300 hover:text-orange-500 transition group">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    <?php
                        $cartCount = auth()->check()
                            ? \App\Models\Cart::where('user_id', auth()->id())->sum('quantity')
                            : \App\Models\Cart::where('session_id', session()->getId())->sum('quantity');
                    ?>
                    <?php if($cartCount > 0): ?>
                        <span class="absolute -top-0.5 -right-0.5 bg-red-500 text-white text-[10px] rounded-full min-w-[18px] h-[18px] flex items-center justify-center font-bold pulse-badge"><?php echo e($cartCount); ?></span>
                    <?php endif; ?>
                </a>

                
                <?php if(auth()->guard()->check()): ?>
                <a href="<?php echo e(route('wishlist.index')); ?>" class="hidden md:block p-2 text-gray-600 dark:text-gray-300 hover:text-red-500 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                </a>
                <?php endif; ?>

                
                <?php if(auth()->guard()->check()): ?>
                    <div class="relative hidden md:block" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center gap-2 px-3 py-1.5 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 transition text-gray-700 dark:text-gray-200">
                            <div class="w-7 h-7 bg-gradient-to-br from-orange-400 to-red-500 rounded-full flex items-center justify-center">
                                <span class="text-white font-bold text-xs"><?php echo e(strtoupper(substr(auth()->user()->name, 0, 1))); ?></span>
                            </div>
                            <span class="font-medium text-sm max-w-[100px] truncate"><?php echo e(auth()->user()->name); ?></span>
                            <svg class="w-3.5 h-3.5 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" @click.outside="open = false" x-cloak x-transition
                            class="absolute right-0 mt-2 w-56 bg-white dark:bg-gray-800 rounded-xl shadow-xl py-2 z-50 border border-gray-100 dark:border-gray-700">
                            <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                                <p class="text-sm font-bold text-gray-900 dark:text-gray-100"><?php echo e(auth()->user()->name); ?></p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5"><?php echo e(auth()->user()->email); ?></p>
                            </div>
                            <a href="<?php echo e(route('account.dashboard')); ?>" class="flex items-center px-4 py-2.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-orange-50 dark:hover:bg-gray-700 hover:text-orange-600 transition gap-3">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                My Account
                            </a>
                            <a href="<?php echo e(route('orders.index')); ?>" class="flex items-center px-4 py-2.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-orange-50 dark:hover:bg-gray-700 hover:text-orange-600 transition gap-3">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                My Orders
                            </a>
                            <a href="<?php echo e(route('wishlist.index')); ?>" class="flex items-center px-4 py-2.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-orange-50 dark:hover:bg-gray-700 hover:text-orange-600 transition gap-3">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                                Wishlist
                            </a>
                            <?php if(auth()->user()->isAdmin()): ?>
                                <div class="border-t border-gray-100 dark:border-gray-700 mt-1 pt-1">
                                    <a href="<?php echo e(route('admin.dashboard')); ?>" class="flex items-center px-4 py-2.5 text-sm text-orange-600 hover:bg-orange-50 dark:hover:bg-gray-700 font-medium gap-3">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        Admin Panel
                                    </a>
                                </div>
                            <?php endif; ?>
                            <div class="border-t border-gray-100 dark:border-gray-700 mt-1 pt-1">
                                <form method="POST" action="<?php echo e(route('logout')); ?>">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="flex items-center w-full px-4 py-2.5 text-sm text-red-500 hover:bg-red-50 dark:hover:bg-gray-700 transition gap-3">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                        Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="<?php echo e(route('login')); ?>" class="hidden md:block text-gray-600 dark:text-gray-300 hover:text-orange-500 font-medium text-sm px-3 py-1.5 rounded hover:bg-gray-100 dark:hover:bg-gray-800 transition">Login</a>
                    <a href="<?php echo e(route('register')); ?>" class="hidden md:block bg-orange-500 text-white px-4 py-1.5 rounded text-sm font-medium hover:bg-orange-600 transition">Sign Up</a>
                <?php endif; ?>

                
                <button @click="mobileOpen = !mobileOpen" class="md:hidden p-2 text-gray-600 dark:text-gray-300" aria-label="Toggle menu">
                    <svg x-show="!mobileOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    <svg x-show="mobileOpen" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>
    </div>

    
    <div class="bg-orange-500 hidden md:block border-t border-orange-400">
        <div class="max-w-[1200px] mx-auto px-4 flex items-center overflow-x-auto scrollbar-hide">
            <?php $__currentLoopData = $navCategories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $navCat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="relative flex-shrink-0" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                    <a href="<?php echo e(route('shop.category', $navCat->slug)); ?>"
                       class="inline-flex items-center gap-1 text-sm text-white whitespace-nowrap hover:bg-orange-600 px-3 py-2.5 transition font-medium">
                        <?php echo e($navCat->name); ?>

                        <?php if($navCat->children->count() > 0): ?>
                            <svg class="w-3 h-3 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                        <?php endif; ?>
                    </a>
                    <?php if($navCat->children->count() > 0): ?>
                        <div x-show="open" x-cloak x-transition
                             class="absolute top-full left-0 bg-white text-gray-700 shadow-xl rounded-b-lg min-w-52 py-2 z-[150] border border-gray-100">
                            <?php $__currentLoopData = $navCat->children; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $child): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <a href="<?php echo e(route('shop.category', $child->slug)); ?>"
                                   class="block px-4 py-2 text-sm hover:bg-orange-50 hover:text-orange-600 whitespace-nowrap transition">
                                    <?php echo e($child->name); ?>

                                </a>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <a href="<?php echo e(route('shop.index')); ?>" class="inline-flex items-center text-sm text-white/80 whitespace-nowrap hover:text-white px-3 py-2.5 ml-auto font-medium transition">All Products →</a>
        </div>
    </div>

    
    <div x-show="mobileOpen" x-cloak x-transition class="md:hidden bg-white dark:bg-gray-900 border-t border-gray-100 dark:border-gray-700 shadow-xl">
        <div class="p-4">
            <form action="<?php echo e(route('shop.index')); ?>" method="GET" class="flex mb-4">
                <input type="text" name="search" placeholder="Search products..." class="flex-1 border-2 border-orange-400 rounded-l-md px-4 py-2 text-sm focus:outline-none focus:border-orange-500">
                <button type="submit" class="bg-orange-500 text-white px-4 py-2 rounded-r-md">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </button>
            </form>
            <?php if(auth()->guard()->check()): ?>
                <div class="flex items-center gap-3 pb-3 border-b border-gray-100 dark:border-gray-700 mb-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-orange-400 to-red-500 rounded-full flex items-center justify-center">
                        <span class="text-white font-bold"><?php echo e(strtoupper(substr(auth()->user()->name, 0, 1))); ?></span>
                    </div>
                    <div>
                        <p class="font-bold text-sm text-gray-900 dark:text-gray-100"><?php echo e(auth()->user()->name); ?></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400"><?php echo e(auth()->user()->email); ?></p>
                    </div>
                </div>
            <?php endif; ?>
            <nav class="space-y-1">
                <a href="<?php echo e(route('home')); ?>" class="block px-3 py-2.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-orange-50 dark:hover:bg-gray-800 hover:text-orange-600 rounded-lg transition">Home</a>
                <a href="<?php echo e(route('shop.index')); ?>" class="block px-3 py-2.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-orange-50 dark:hover:bg-gray-800 hover:text-orange-600 rounded-lg transition">Shop All</a>
                <?php
                    $mobileCategories = \App\Models\Category::whereNull('parent_id')->withCount('products')->orderBy('sort_order')->limit(8)->get();
                ?>
                <?php if($mobileCategories->count() > 0): ?>
                    <div x-data="{ showCats: false }">
                        <button @click="showCats = !showCats" class="flex items-center justify-between w-full px-3 py-2.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-orange-50 dark:hover:bg-gray-800 hover:text-orange-600 rounded-lg transition">
                            <span>Categories</span>
                            <svg class="w-4 h-4 transition-transform" :class="showCats ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="showCats" x-cloak class="pl-4 space-y-1 mt-1">
                            <?php $__currentLoopData = $mobileCategories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <a href="<?php echo e(route('shop.category', $cat->slug)); ?>" class="block px-3 py-2 text-xs text-gray-600 dark:text-gray-300 hover:bg-orange-50 dark:hover:bg-gray-800 hover:text-orange-600 rounded-lg transition"><?php echo e($cat->name); ?></a>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <a href="<?php echo e(route('shop.index')); ?>" class="block px-3 py-2 text-xs text-orange-500 font-medium hover:bg-orange-50 dark:hover:bg-gray-800 rounded-lg transition">View All Categories →</a>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if(auth()->guard()->check()): ?>
                    <a href="<?php echo e(route('orders.index')); ?>" class="block px-3 py-2.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-orange-50 dark:hover:bg-gray-800 hover:text-orange-600 rounded-lg transition">My Orders</a>
                    <a href="<?php echo e(route('wishlist.index')); ?>" class="block px-3 py-2.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-orange-50 dark:hover:bg-gray-800 hover:text-orange-600 rounded-lg transition">Wishlist</a>
                    <a href="<?php echo e(route('account.dashboard')); ?>" class="block px-3 py-2.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-orange-50 dark:hover:bg-gray-800 hover:text-orange-600 rounded-lg transition">My Account</a>
                    <form method="POST" action="<?php echo e(route('logout')); ?>">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="block w-full text-left px-3 py-2.5 text-sm text-red-500 hover:bg-red-50 dark:hover:bg-gray-800 rounded-lg transition">Logout</button>
                    </form>
                <?php else: ?>
                    <a href="<?php echo e(route('login')); ?>" class="block px-3 py-2.5 text-sm text-orange-600 font-medium hover:bg-orange-50 dark:hover:bg-gray-800 rounded-lg transition">Login</a>
                    <a href="<?php echo e(route('register')); ?>" class="block px-3 py-2.5 text-sm text-white bg-orange-500 text-center font-medium rounded-lg hover:bg-orange-600 transition">Sign Up</a>
                <?php endif; ?>
            </nav>
        </div>
    </div>
</header>


<?php if(session('success')): ?>
    <div class="fixed top-20 right-4 left-4 md:left-auto md:max-w-sm z-50 bg-green-500 text-white px-5 py-3 rounded-lg shadow-xl flex items-center space-x-3 fade-in" x-data x-init="setTimeout(() => $el.remove(), 4000)">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span class="text-sm font-medium"><?php echo e(session('success')); ?></span>
    </div>
<?php endif; ?>
<?php if(session('error')): ?>
    <div class="fixed top-20 right-4 left-4 md:left-auto md:max-w-sm z-50 bg-red-500 text-white px-5 py-3 rounded-lg shadow-xl flex items-center space-x-3 fade-in" x-data x-init="setTimeout(() => $el.remove(), 4000)">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        <span class="text-sm font-medium"><?php echo e(session('error')); ?></span>
    </div>
<?php endif; ?>

<main>
    <?php echo $__env->yieldContent('content'); ?>
</main>


<footer class="bg-gray-900 text-gray-300 mt-0">
    <?php
    $footerLogo  = setting_file_url('footer_logo', $logoUrl);
    $fbUrl       = setting('facebook_url', '');
    $ytUrl       = setting('youtube_url', '');
    $igUrl       = setting('instagram_url', '');
    $twUrl       = setting('twitter_url', '');
    $tkUrl       = setting('tiktok_url', '');
    $waUrl       = setting('whatsapp_link', '');
    $copyright   = str_replace('{year}', date('Y'), setting('copyright_text', '© {year} ' . $siteName . '. All rights reserved.'));
    ?>

    
    <div class="bg-gray-800 border-b border-gray-700">
        <div class="max-w-[1200px] mx-auto px-4 py-8">
            <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                <div>
                    <h3 class="text-white text-lg font-bold">Subscribe to Our Newsletter</h3>
                    <p class="text-gray-400 text-sm mt-1">Get updates on new arrivals, deals, and exclusive offers.</p>
                </div>
                <div class="w-full md:w-auto">
                    <form action="<?php echo e(route('newsletter.subscribe')); ?>" method="POST" class="flex w-full md:w-auto">
                        <?php echo csrf_field(); ?>
                        <input type="email" name="email" value="<?php echo e(old('email')); ?>" placeholder="Enter your email" required class="flex-1 md:w-72 px-4 py-2.5 rounded-l-lg bg-gray-700 text-white border border-gray-600 focus:outline-none focus:border-orange-500 text-sm placeholder-gray-500">
                        <button type="submit" class="bg-orange-500 text-white px-6 py-2.5 rounded-r-lg font-medium text-sm hover:bg-orange-600 transition whitespace-nowrap">Subscribe</button>
                    </form>
                    <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-red-400 text-xs mt-1.5"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-[1200px] mx-auto px-4 py-10">
        <div class="grid grid-cols-2 md:grid-cols-5 gap-8">
            
            <div class="col-span-2 md:col-span-1">
                <div class="flex items-center space-x-2 mb-4">
                    <?php if($footerLogo): ?>
                        <img src="<?php echo e($footerLogo); ?>" alt="<?php echo e($siteName); ?>" class="h-8 max-w-[120px] object-contain">
                    <?php else: ?>
                        <div class="w-8 h-8 bg-gradient-to-br from-orange-500 to-red-500 rounded-lg flex items-center justify-center">
                            <span class="text-white font-bold"><?php echo e(strtoupper(substr($siteName,0,1))); ?></span>
                        </div>
                        <span class="text-white text-lg font-bold"><?php echo e($siteName); ?></span>
                    <?php endif; ?>
                </div>
                <p class="text-sm text-gray-400 mb-4 leading-relaxed"><?php echo e($siteTagline); ?></p>
                
                <div class="flex items-center gap-2 mb-4">
                    <span class="text-xs text-gray-500">We accept:</span>
                </div>
                <div class="flex flex-wrap gap-1.5">
                    <?php $__currentLoopData = ['Visa', 'Mastercard', 'bKash', 'Nagad', 'COD']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $method): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <span class="bg-gray-800 text-gray-400 text-[10px] px-2 py-1 rounded font-medium border border-gray-700"><?php echo e($method); ?></span>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
                
                <div class="flex flex-wrap gap-2 mt-4">
                    <?php if($fbUrl): ?><a href="<?php echo e($fbUrl); ?>" target="_blank" rel="noopener" class="text-gray-500 hover:text-blue-400 transition" title="Facebook"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/></svg></a><?php endif; ?>
                    <?php if($igUrl): ?><a href="<?php echo e($igUrl); ?>" target="_blank" rel="noopener" class="text-gray-500 hover:text-pink-400 transition" title="Instagram"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg></a><?php endif; ?>
                    <?php if($ytUrl): ?><a href="<?php echo e($ytUrl); ?>" target="_blank" rel="noopener" class="text-gray-500 hover:text-red-400 transition" title="YouTube"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg></a><?php endif; ?>
                    <?php if($twUrl): ?><a href="<?php echo e($twUrl); ?>" target="_blank" rel="noopener" class="text-gray-500 hover:text-gray-200 transition" title="X"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg></a><?php endif; ?>
                    <?php if($waUrl): ?><a href="<?php echo e($waUrl); ?>" target="_blank" rel="noopener" class="text-gray-500 hover:text-green-400 transition" title="WhatsApp"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg></a><?php endif; ?>
                </div>
            </div>

            
            <div>
                <h4 class="text-white font-bold mb-4 text-sm"><?php echo e(setting('footer_col2_title', 'Quick Links')); ?></h4>
                <ul class="space-y-2.5 text-sm">
                    <li><a href="<?php echo e(route('home')); ?>" class="hover:text-orange-400 transition">Home</a></li>
                    <li><a href="<?php echo e(route('shop.index')); ?>" class="hover:text-orange-400 transition">Shop</a></li>
                    <li><a href="<?php echo e(route('blog.index')); ?>" class="hover:text-orange-400 transition">Blog</a></li>
                    <li><a href="<?php echo e(route('vendor.apply')); ?>" class="hover:text-orange-400 transition">Sell on <?php echo e($siteName); ?></a></li>
                </ul>
            </div>

            
            <div>
                <h4 class="text-white font-bold mb-4 text-sm"><?php echo e(setting('footer_col3_title', 'Customer Service')); ?></h4>
                <ul class="space-y-2.5 text-sm">
                    <li><a href="<?php echo e(route('contact')); ?>" class="hover:text-orange-400 transition">Contact Us</a></li>
                    <li><a href="<?php echo e(route('faq')); ?>" class="hover:text-orange-400 transition">FAQ</a></li>
                    <li><a href="<?php echo e(route('guest.order.track.form')); ?>" class="hover:text-orange-400 transition">Track Order</a></li>
                    <li><a href="<?php echo e(route('shop.index')); ?>" class="hover:text-orange-400 transition">Return Policy</a></li>
                </ul>
            </div>

            
            <div>
                <h4 class="text-white font-bold mb-4 text-sm"><?php echo e(setting('footer_col4_title', 'Contact')); ?></h4>
                <ul class="space-y-2.5 text-sm text-gray-400">
                    <?php if(setting('company_address')): ?>
                    <li class="flex items-start gap-2">
                        <svg class="w-4 h-4 mt-0.5 flex-shrink-0 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <?php echo e(setting('company_address')); ?>

                    </li>
                    <?php endif; ?>
                    <?php if(setting('company_phone')): ?>
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 flex-shrink-0 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        <?php echo e(setting('company_phone')); ?>

                    </li>
                    <?php endif; ?>
                    <?php if(setting('company_email') || setting('support_email')): ?>
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 flex-shrink-0 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <?php echo e(setting('support_email', setting('company_email'))); ?>

                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <div class="border-t border-gray-800 mt-8 pt-6 text-center text-xs text-gray-500">
            <?php echo $copyright; ?>

        </div>
    </div>
</footer>


<div x-data="{ show: false }" @scroll.window="show = window.scrollY > 400"
     x-show="show" x-cloak x-transition
     class="fixed bottom-6 right-6 z-40">
    <button onclick="window.scrollTo({top:0,behavior:'smooth'})" class="w-11 h-11 bg-orange-500 text-white rounded-full shadow-lg hover:bg-orange-600 transition flex items-center justify-center">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/></svg>
    </button>
</div>


<script>
async function toggleWishlist(productId, btn) {
    try {
        const res = await fetch('/wishlist/toggle/' + productId, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });
        const data = await res.json();
        if (data.status === 'added') {
            btn.classList.add('text-red-500');
            btn.classList.remove('text-gray-400');
            btn.querySelector('svg').setAttribute('fill', 'currentColor');
        } else {
            btn.classList.remove('text-red-500');
            btn.classList.add('text-gray-400');
            btn.querySelector('svg').setAttribute('fill', 'none');
        }
    } catch (e) {
        window.location.href = '/login';
    }
}
</script>

<?php $customJs = setting('custom_js', ''); ?>
<?php if($customJs): ?><script><?php echo e($customJs); ?></script><?php endif; ?>
</body>
</html>
<?php /**PATH C:\xampp\htdocs\azad-ecom\resources\views/layouts/app.blade.php ENDPATH**/ ?>