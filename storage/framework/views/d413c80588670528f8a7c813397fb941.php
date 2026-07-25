<?php $__env->startSection('title', 'All Categories'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex items-center space-x-2 text-sm text-gray-500 mb-6">
        <a href="<?php echo e(route('home')); ?>" class="hover:text-orange-500">Home</a>
        <span>/</span>
        <span class="text-gray-900 font-medium">Categories</span>
    </div>

    <div class="mb-6">
        <h1 class="text-xl md:text-2xl font-extrabold text-gray-900">All Categories</h1>
        <p class="text-gray-400 text-sm mt-1">Browse every category and find what you need</p>
    </div>

    <?php if($categories->count() > 0): ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow duration-200 p-5">
                    <a href="<?php echo e(route('shop.category', $category->slug)); ?>" class="flex items-center gap-4 group">
                        <div class="w-16 h-16 bg-gradient-to-br from-orange-100 to-orange-50 rounded-2xl flex items-center justify-center flex-shrink-0 overflow-hidden group-hover:from-orange-200 group-hover:to-orange-100 transition-all">
                            <?php if($category->image): ?>
                                <img src="<?php echo e(Storage::url($category->image)); ?>" alt="<?php echo e($category->name); ?>" class="w-full h-full object-cover">
                            <?php else: ?>
                                <span class="text-orange-500 font-extrabold text-xl"><?php echo e(strtoupper(substr($category->name, 0, 2))); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="min-w-0">
                            <h2 class="font-bold text-gray-900 group-hover:text-orange-600 transition truncate"><?php echo e($category->name); ?></h2>
                            <p class="text-gray-400 text-xs mt-0.5"><?php echo e($category->products_count); ?> <?php echo e(Str::plural('product', $category->products_count)); ?></p>
                        </div>
                    </a>

                    <?php if($category->children->count() > 0): ?>
                        <div class="flex flex-wrap gap-2 mt-4 pt-4 border-t border-gray-50">
                            <?php $__currentLoopData = $category->children; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $child): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <a href="<?php echo e(route('shop.category', $child->slug)); ?>"
                                   class="text-xs font-medium text-gray-600 bg-gray-50 hover:bg-orange-50 hover:text-orange-600 px-3 py-1.5 rounded-full transition">
                                    <?php echo e($child->name); ?> <span class="text-gray-400">(<?php echo e($child->products_count); ?>)</span>
                                </a>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php else: ?>
        <div class="text-center py-16 text-gray-400">No categories found.</div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\completeEcom\resources\views/shop/categories.blade.php ENDPATH**/ ?>