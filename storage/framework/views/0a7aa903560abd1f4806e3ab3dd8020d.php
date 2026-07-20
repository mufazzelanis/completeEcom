<?php $__env->startSection('title', isset($category) ? $category->name : 'Shop'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex items-center space-x-2 text-sm text-gray-500 mb-6">
        <a href="<?php echo e(route('home')); ?>" class="hover:text-orange-500">Home</a>
        <span>/</span>
        <?php if(isset($category)): ?>
            <a href="<?php echo e(route('shop.index')); ?>" class="hover:text-orange-500">Shop</a>
            <span>/</span>
            <span class="text-gray-900 font-medium"><?php echo e($category->name); ?></span>
        <?php else: ?>
            <span class="text-gray-900 font-medium">Shop</span>
        <?php endif; ?>
    </div>

    <?php
        $activeFilters = collect([
            'search'   => request('search') ? 'Search: "'.request('search').'"' : null,
            'category' => request()->query('category') ? 'Category: '.request()->query('category') : null,
            'brand'    => request('brand') ? 'Brand: '.request('brand') : null,
            'tag'      => request('tag') ? 'Tag: '.request('tag') : null,
            'min_price'=> request('min_price') ? 'Min ৳'.request('min_price') : null,
            'max_price'=> request('max_price') ? 'Max ৳'.request('max_price') : null,
            'featured' => request('featured') ? 'Featured Only' : null,
            'in_stock' => request('in_stock') ? 'In Stock Only' : null,
            'on_sale'  => request('on_sale') ? 'On Sale Only' : null,
        ])->filter()->count();
    ?>

    
    <div class="lg:hidden mb-4" x-data="{ open: false }">
        <button @click="open = !open" class="w-full bg-white border border-gray-200 rounded-xl px-4 py-3 flex items-center justify-between shadow-sm">
            <span class="flex items-center gap-2 font-semibold text-gray-800 text-sm">
                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                Filters
                <?php if($activeFilters > 0): ?>
                    <span class="bg-orange-500 text-white text-xs px-2 py-0.5 rounded-full"><?php echo e($activeFilters); ?></span>
                <?php endif; ?>
            </span>
            <svg class="w-5 h-5 text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div x-show="open" x-collapse x-cloak class="mt-2">
            <?php echo $__env->make('shop._filters', ['activeFilters' => $activeFilters], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
    </div>

    <div class="flex flex-col lg:flex-row gap-6 lg:gap-8">
        
        <aside class="hidden lg:block lg:w-64 flex-shrink-0">
            <div class="bg-white rounded-2xl shadow-sm p-5 lg:sticky lg:top-24">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-gray-900">Filters</h3>
                    <?php if($activeFilters > 0): ?>
                        <a href="<?php echo e(route('shop.index')); ?>" class="text-xs text-red-500 hover:text-red-700 font-medium">Clear all (<?php echo e($activeFilters); ?>)</a>
                    <?php endif; ?>
                </div>
                <?php echo $__env->make('shop._filters', ['activeFilters' => $activeFilters], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>
        </aside>

        <div class="flex-1 min-w-0">
            <?php if($activeFilters > 0): ?>
            <div class="flex flex-wrap items-center gap-2 mb-4">
                <span class="text-xs text-gray-500 font-medium">Active:</span>
                <?php if(request('search')): ?>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-orange-50 text-orange-700 rounded-full text-xs font-medium">
                        "<?php echo e(request('search')); ?>"
                        <a href="<?php echo e(request()->fullUrlWithQuery(['search' => null, 'page' => null])); ?>" class="hover:text-orange-900">&times;</a>
                    </span>
                <?php endif; ?>
                <?php if(request()->query('category')): ?>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-orange-50 text-orange-700 rounded-full text-xs font-medium">
                        <?php echo e(request()->query('category')); ?>

                        <a href="<?php echo e(request()->fullUrlWithQuery(['category' => null, 'page' => null])); ?>" class="hover:text-orange-900">&times;</a>
                    </span>
                <?php endif; ?>
                <?php if(request('brand')): ?>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-orange-50 text-orange-700 rounded-full text-xs font-medium">
                        Brand: <?php echo e(request('brand')); ?>

                        <a href="<?php echo e(request()->fullUrlWithQuery(['brand' => null, 'page' => null])); ?>" class="hover:text-orange-900">&times;</a>
                    </span>
                <?php endif; ?>
                <?php if(request('tag')): ?>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-orange-50 text-orange-700 rounded-full text-xs font-medium">
                        #<?php echo e(request('tag')); ?>

                        <a href="<?php echo e(request()->fullUrlWithQuery(['tag' => null, 'page' => null])); ?>" class="hover:text-orange-900">&times;</a>
                    </span>
                <?php endif; ?>
                <?php if(request('on_sale')): ?>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-red-50 text-red-600 rounded-full text-xs font-medium">
                        On Sale
                        <a href="<?php echo e(request()->fullUrlWithQuery(['on_sale' => null, 'page' => null])); ?>" class="hover:text-red-800">&times;</a>
                    </span>
                <?php endif; ?>
                <?php if(request('in_stock')): ?>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-green-50 text-green-600 rounded-full text-xs font-medium">
                        In Stock
                        <a href="<?php echo e(request()->fullUrlWithQuery(['in_stock' => null, 'page' => null])); ?>" class="hover:text-green-800">&times;</a>
                    </span>
                <?php endif; ?>
                <?php if(request('featured')): ?>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-yellow-50 text-yellow-700 rounded-full text-xs font-medium">
                        Featured
                        <a href="<?php echo e(request()->fullUrlWithQuery(['featured' => null, 'page' => null])); ?>" class="hover:text-yellow-900">&times;</a>
                    </span>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="flex items-center justify-between mb-4">
                <p class="text-gray-600 text-sm">
                    Showing <span class="font-semibold text-gray-900"><?php echo e($products->firstItem() ?? 0); ?></span>–<span class="font-semibold text-gray-900"><?php echo e($products->lastItem() ?? 0); ?></span>
                    of <span class="font-semibold text-gray-900"><?php echo e($products->total()); ?></span> results
                </p>
            </div>

            <?php if($products->isEmpty()): ?>
                <div class="bg-white rounded-2xl shadow-sm p-8 md:p-16 text-center">
                    <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-700 mb-2">No products found</h3>
                    <p class="text-gray-500 text-sm mb-4">Try adjusting your search or filter criteria</p>
                    <a href="<?php echo e(route('shop.index')); ?>" class="text-orange-500 hover:text-orange-700 text-sm font-medium">Clear all filters</a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-4">
                    <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php echo $__env->make('partials.product-card', ['product' => $product], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>

                <div class="mt-8">
                    <?php echo e($products->links()); ?>

                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\azad-ecom\resources\views/shop/index.blade.php ENDPATH**/ ?>