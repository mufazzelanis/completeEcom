
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
<?php /**PATH C:\xampp\htdocs\azad-ecom\resources\views/partials/storefront/header-logo.blade.php ENDPATH**/ ?>