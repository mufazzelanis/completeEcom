<?php $__env->startSection('title', 'Unsubscribed'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-lg mx-auto px-4 py-20 text-center">
    <div class="bg-white rounded-2xl shadow-sm p-10">
        <div class="w-14 h-14 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-7 h-7 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 6L6 18M6 6l12 12"/></svg>
        </div>
        <h1 class="text-xl font-bold text-gray-900 mb-2">You've been unsubscribed</h1>
        <p class="text-gray-500 text-sm">You won't receive newsletter emails from us anymore. You can resubscribe anytime from the homepage.</p>
        <a href="<?php echo e(route('home')); ?>" class="inline-block mt-6 bg-indigo-600 text-white px-6 py-2.5 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition">Back to Home</a>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\azad-ecom\resources\views/newsletter/unsubscribed.blade.php ENDPATH**/ ?>