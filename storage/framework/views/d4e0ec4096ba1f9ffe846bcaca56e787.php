<?php $__env->startSection('title', 'Home - ' . setting('site_name', 'ShopVista')); ?>

<?php $__env->startSection('content'); ?>


<div class="max-w-[1200px] mx-auto px-4 pt-4" x-data="{
    current: 0,
    total: <?php echo e(max($banners->count(), 1)); ?>,
    init() {
        <?php if($banners->count() > 1): ?>
        setInterval(() => { this.current = (this.current + 1) % this.total }, 5000);
        <?php endif; ?>
    }
}">
    <div class="relative rounded-xl overflow-hidden bg-gray-200 aspect-[2/1] md:aspect-[5/1]">
        <?php if($banners->count() > 0): ?>
            <?php $__currentLoopData = $banners; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $banner): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div x-show="current === <?php echo e($i); ?>" x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="absolute inset-0">
                <?php if($banner->image): ?>
                    <a href="<?php echo e($banner->button_link ?: '#'); ?>">
                        <img src="<?php echo e(Storage::url($banner->image)); ?>" alt="<?php echo e($banner->title); ?>" class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-gradient-to-r from-black/50 to-transparent"></div>
                        <div class="absolute inset-0 flex items-center px-5 md:px-14">
                            <div>
                                <?php if($banner->subtitle): ?><p class="text-white/80 text-sm font-medium mb-2"><?php echo e($banner->subtitle); ?></p><?php endif; ?>
                                <h2 class="text-white text-xl md:text-4xl font-extrabold mb-2 leading-tight"><?php echo e($banner->title); ?></h2>
                                <?php if($banner->description): ?><p class="text-white/70 text-sm mb-4 hidden md:block max-w-md"><?php echo e($banner->description); ?></p><?php endif; ?>
                                <?php if($banner->button_text): ?>
                                    <span class="inline-block bg-white text-gray-900 px-6 py-2 rounded-full text-sm font-bold hover:bg-gray-100 transition"><?php echo e($banner->button_text); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                <?php endif; ?>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php else: ?>
            
            <div class="absolute inset-0 bg-gradient-to-r from-orange-500 via-red-500 to-pink-500">
                <div class="absolute inset-0 flex items-center px-5 md:px-14">
                    <div>
                        <p class="text-white/80 text-sm font-medium mb-2">Welcome to <?php echo e(setting('site_name', 'ShopVista')); ?></p>
                        <h2 class="text-white text-xl md:text-5xl font-extrabold mb-3 leading-tight">Discover Amazing Deals</h2>
                        <p class="text-white/70 text-sm mb-5 hidden md:block">Shop thousands of products at unbeatable prices</p>
                        <a href="<?php echo e(route('shop.index')); ?>" class="inline-block bg-white text-gray-900 px-8 py-2.5 rounded-full text-sm font-bold hover:bg-gray-100 transition shadow-lg">Shop Now</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        
        <?php if($banners->count() > 1): ?>
        <button @click="current = (current - 1 + total) % total" class="absolute left-2 md:left-3 top-1/2 -translate-y-1/2 w-10 h-10 md:w-9 md:h-9 bg-black/30 hover:bg-black/50 text-white rounded-full flex items-center justify-center transition backdrop-blur-sm" aria-label="Previous">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <button @click="current = (current + 1) % total" class="absolute right-2 md:right-3 top-1/2 -translate-y-1/2 w-10 h-10 md:w-9 md:h-9 bg-black/30 hover:bg-black/50 text-white rounded-full flex items-center justify-center transition backdrop-blur-sm" aria-label="Next">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </button>
        <div class="absolute bottom-3 left-1/2 -translate-x-1/2 flex gap-1.5">
            <?php $__currentLoopData = $banners; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $banner): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <button @click="current = <?php echo e($i); ?>" class="w-2.5 h-2.5 rounded-full transition-all duration-300" aria-label="Go to slide <?php echo e($i + 1); ?>"
                    :class="current === <?php echo e($i); ?> ? 'bg-white w-5' : 'bg-white/50'"></button>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <?php endif; ?>
    </div>
</div>


<div class="bg-white mt-4">
    <div class="max-w-[1200px] mx-auto px-4 py-3">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <?php $__currentLoopData = [
                ['icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4', 'text' => 'Free Shipping', 'sub' => 'On orders over ৳2,000'],
                ['icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', 'text' => 'Secure Payment', 'sub' => '100% protected'],
                ['icon' => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15', 'text' => 'Easy Returns', 'sub' => '7-day return policy'],
                ['icon' => 'M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z', 'text' => '24/7 Support', 'sub' => 'Dedicated support'],
            ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $f): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-orange-50 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="<?php echo e($f['icon']); ?>"/>
                    </svg>
                </div>
                <div>
                    <p class="font-bold text-gray-800 text-xs"><?php echo e($f['text']); ?></p>
                    <p class="text-gray-400 text-[10px]"><?php echo e($f['sub']); ?></p>
                </div>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
</div>


<?php if($flashSale && $flashSaleProducts->count() > 0): ?>
<div class="bg-white mt-4" x-data="{
    hours: 0, minutes: 0, seconds: 0,
    end: '<?php echo e($flashSale->ends_at->toIso8601String()); ?>',
    init() {
        this.update();
        setInterval(() => this.update(), 1000);
    },
    update() {
        let diff = Math.max(0, Math.floor((new Date(this.end) - new Date()) / 1000));
        this.hours = Math.floor(diff / 3600);
        this.minutes = Math.floor((diff % 3600) / 60);
        this.seconds = diff % 60;
    }
}">
    <div class="max-w-[1200px] mx-auto px-4 py-5">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2">
                    <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    <h2 class="text-lg md:text-xl font-extrabold text-gray-900">Flash Sale</h2>
                </div>
                <div class="flex items-center gap-1.5">
                    <div class="bg-gray-900 text-white text-xs font-bold px-2 py-1 rounded min-w-[28px] text-center">
                        <span x-text="String(hours).padStart(2,'0')">00</span>
                    </div>
                    <span class="text-gray-900 font-bold text-xs">:</span>
                    <div class="bg-gray-900 text-white text-xs font-bold px-2 py-1 rounded min-w-[28px] text-center">
                        <span x-text="String(minutes).padStart(2,'0')">00</span>
                    </div>
                    <span class="text-gray-900 font-bold text-xs">:</span>
                    <div class="bg-gray-900 text-white text-xs font-bold px-2 py-1 rounded min-w-[28px] text-center">
                        <span x-text="String(seconds).padStart(2,'0')">00</span>
                    </div>
                </div>
            </div>
            <a href="<?php echo e(route('shop.index')); ?>?on_sale=1" class="text-orange-500 hover:text-orange-700 font-bold text-sm transition">SHOP ALL →</a>
        </div>
        <div class="flex gap-3 overflow-x-auto scrollbar-hide pb-2">
            <?php $__currentLoopData = $flashSaleProducts->take(10); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $fsp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php echo $__env->make('partials.flash-sale-card', ['fsp' => $fsp], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
</div>
<?php endif; ?>


<?php if($categories->count() > 0): ?>
<div class="bg-white mt-4">
    <div class="max-w-[1200px] mx-auto px-4 py-6">
        <div class="flex items-center justify-between mb-5">
            <h2 class="text-lg font-extrabold text-gray-900">Categories</h2>
            <a href="<?php echo e(route('shop.index')); ?>" class="text-orange-500 hover:text-orange-700 font-bold text-sm transition">VIEW ALL →</a>
        </div>
        <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-3">
            <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e(route('shop.category', $category->slug)); ?>"
                   class="group flex flex-col items-center p-3 rounded-xl hover:bg-orange-50 transition-all duration-200">
                    <div class="w-14 h-14 md:w-16 md:h-16 bg-gradient-to-br from-orange-100 to-orange-50 rounded-2xl flex items-center justify-center mb-2 group-hover:from-orange-200 group-hover:to-orange-100 transition-all group-hover:scale-110 duration-300 shadow-sm">
                        <span class="text-orange-500 font-extrabold text-xl"><?php echo e(strtoupper(substr($category->name, 0, 2))); ?></span>
                    </div>
                    <p class="text-[10px] md:text-xs font-semibold text-gray-700 text-center leading-tight group-hover:text-orange-600 transition line-clamp-2"><?php echo e($category->name); ?></p>
                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
</div>
<?php endif; ?>


<?php if($promoBanners->count() > 0): ?>
<div class="max-w-[1200px] mx-auto px-4 mt-4">
    <div class="grid grid-cols-1 md:grid-cols-<?php echo e(min($promoBanners->count(), 4)); ?> gap-3">
        <?php $__currentLoopData = $promoBanners; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $banner): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php if($banner->image): ?>
            <a href="<?php echo e($banner->button_link ?: '#'); ?>" class="relative rounded-xl overflow-hidden group block aspect-[2/1]">
                <img src="<?php echo e(Storage::url($banner->image)); ?>" alt="<?php echo e($banner->title); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                <div class="absolute bottom-0 left-0 p-4">
                    <h3 class="text-white font-bold text-sm md:text-base"><?php echo e($banner->title); ?></h3>
                    <?php if($banner->button_text): ?><span class="text-white/80 text-xs"><?php echo e($banner->button_text); ?> →</span><?php endif; ?>
                </div>
            </a>
            <?php endif; ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</div>
<?php endif; ?>


<?php if($featuredProducts->isNotEmpty()): ?>
<div class="bg-white mt-4">
    <div class="max-w-[1200px] mx-auto px-4 py-6">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h2 class="text-lg font-extrabold text-gray-900">Featured Products</h2>
                <p class="text-gray-400 text-xs mt-0.5">Handpicked just for you</p>
            </div>
            <a href="<?php echo e(route('shop.index')); ?>?featured=1" class="text-orange-500 hover:text-orange-700 font-bold text-sm transition">VIEW ALL →</a>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
            <?php $__currentLoopData = $featuredProducts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php echo $__env->make('partials.product-card', ['product' => $product], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
</div>
<?php endif; ?>


<?php if($topSelling->count() > 0): ?>
<div class="bg-white mt-4">
    <div class="max-w-[1200px] mx-auto px-4 py-6">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h2 class="text-lg font-extrabold text-gray-900">Top Selling</h2>
                <p class="text-gray-400 text-xs mt-0.5">Most popular products</p>
            </div>
            <a href="<?php echo e(route('shop.index')); ?>?sort=popular" class="text-orange-500 hover:text-orange-700 font-bold text-sm transition">VIEW ALL →</a>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
            <?php $__currentLoopData = $topSelling->take(8); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php echo $__env->make('partials.product-card', ['product' => $product], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
</div>
<?php endif; ?>


<?php if($onSale->count() > 0): ?>
<div class="bg-gradient-to-r from-red-500 to-orange-500 mt-4">
    <div class="max-w-[1200px] mx-auto px-4 py-6">
        <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-3">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                <h2 class="text-lg font-extrabold text-white">Deals & Offers</h2>
            </div>
            <a href="<?php echo e(route('shop.index')); ?>?on_sale=1" class="text-white/80 hover:text-white font-bold text-sm transition">VIEW ALL →</a>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
            <?php $__currentLoopData = $onSale->take(8); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php echo $__env->make('partials.product-card', ['product' => $product], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
</div>
<?php endif; ?>


<?php if($brands->count() > 0): ?>
<div class="bg-white mt-4">
    <div class="max-w-[1200px] mx-auto px-4 py-6">
        <div class="flex items-center justify-between mb-5">
            <h2 class="text-lg font-extrabold text-gray-900">Top Brands</h2>
            <a href="<?php echo e(route('shop.index')); ?>" class="text-orange-500 hover:text-orange-700 font-bold text-sm transition">VIEW ALL →</a>
        </div>
        <div class="flex gap-3 overflow-x-auto scrollbar-hide pb-2">
            <?php $__currentLoopData = $brands; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $brand): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e(route('shop.index')); ?>?brand=<?php echo e($brand->slug); ?>"
                   class="flex-shrink-0 w-32 h-20 bg-gray-50 border border-gray-100 rounded-xl flex items-center justify-center hover:border-orange-300 hover:shadow-md transition-all duration-200 group">
                    <?php if($brand->logo): ?>
                        <img src="<?php echo e(Storage::url($brand->logo)); ?>" alt="<?php echo e($brand->name); ?>" class="max-w-[80%] max-h-[60%] object-contain group-hover:scale-105 transition">
                    <?php else: ?>
                        <span class="text-gray-400 font-bold text-sm group-hover:text-orange-500 transition"><?php echo e($brand->name); ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
</div>
<?php endif; ?>


<?php if($newArrivals->count() > 0): ?>
<div class="bg-white mt-4">
    <div class="max-w-[1200px] mx-auto px-4 py-6">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h2 class="text-lg font-extrabold text-gray-900">New Arrivals</h2>
                <p class="text-gray-400 text-xs mt-0.5">Fresh finds every day</p>
            </div>
            <a href="<?php echo e(route('shop.index')); ?>?sort=latest" class="text-orange-500 hover:text-orange-700 font-bold text-sm transition">VIEW ALL →</a>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
            <?php $__currentLoopData = $newArrivals->take(8); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php echo $__env->make('partials.product-card', ['product' => $product], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
</div>
<?php endif; ?>


<?php if($newArrivals->count() > 8): ?>
<div class="bg-white mt-4">
    <div class="max-w-[1200px] mx-auto px-4 py-6">
        <div class="flex items-center justify-center mb-5">
            <div class="h-px bg-gray-200 flex-1"></div>
            <h2 class="text-lg font-extrabold text-gray-900 px-6">Just For You</h2>
            <div class="h-px bg-gray-200 flex-1"></div>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
            <?php $__currentLoopData = $newArrivals->slice(8, 10); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php echo $__env->make('partials.product-card', ['product' => $product], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <div class="text-center mt-6">
            <a href="<?php echo e(route('shop.index')); ?>" class="inline-block bg-orange-500 text-white px-10 py-2.5 rounded-lg font-bold text-sm hover:bg-orange-600 transition shadow-md">View More Products</a>
        </div>
    </div>
</div>
<?php endif; ?>


<div class="mt-4">
    <div class="max-w-[1200px] mx-auto px-4">
        <div class="bg-gradient-to-r from-gray-800 to-gray-900 rounded-xl p-6 md:p-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
                <?php $__currentLoopData = [
                    ['num' => '100%', 'label' => 'Genuine Products'],
                    ['num' => '7 Days', 'label' => 'Easy Returns'],
                    ['num' => '24/7', 'label' => 'Customer Support'],
                    ['num' => 'Secure', 'label' => 'Payment System'],
                ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $stat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div>
                    <p class="text-white text-lg md:text-2xl font-extrabold"><?php echo e($stat['num']); ?></p>
                    <p class="text-gray-400 text-xs mt-1"><?php echo e($stat['label']); ?></p>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\completeEcom\resources\views/home.blade.php ENDPATH**/ ?>