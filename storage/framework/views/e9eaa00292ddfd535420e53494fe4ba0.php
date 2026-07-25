<?php $__env->startSection('title', 'My Reviews'); ?>
<?php $__env->startSection('pageTitle', 'My Reviews'); ?>

<?php $__env->startSection('content'); ?>
<h1 class="text-xl font-bold text-gray-800 mb-5">My Reviews</h1>

<?php if($reviews->isEmpty()): ?>
<div class="bg-white rounded-2xl shadow-sm p-16 text-center">
    <svg class="w-16 h-16 text-gray-200 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
    <p class="text-gray-500 text-sm mb-4">You haven't reviewed any products yet.</p>
    <a href="<?php echo e(route('shop.index')); ?>" class="inline-block bg-indigo-600 text-white px-5 py-2 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition">Browse Products</a>
</div>
<?php else: ?>
<div class="space-y-4">
    <?php $__currentLoopData = $reviews; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $review): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <div class="bg-white rounded-2xl shadow-sm p-5 flex items-start gap-4">
        <?php if($review->product?->image): ?>
        <a href="<?php echo e(route('products.show', $review->product)); ?>">
            <img src="<?php echo e(\Illuminate\Support\Facades\Storage::url($review->product->image)); ?>" class="w-16 h-16 object-cover rounded-xl flex-shrink-0">
        </a>
        <?php else: ?>
        <div class="w-16 h-16 bg-gray-100 rounded-xl flex-shrink-0"></div>
        <?php endif; ?>
        <div class="flex-1 min-w-0">
            <a href="<?php echo e($review->product ? route('products.show', $review->product) : '#'); ?>" class="font-semibold text-gray-800 hover:text-indigo-600 text-sm transition">
                <?php echo e($review->product?->name ?? 'Deleted Product'); ?>

            </a>
            <div class="flex items-center gap-1 my-1">
                <?php for($i = 1; $i <= 5; $i++): ?>
                <svg class="w-4 h-4 <?php echo e($i <= $review->rating ? 'text-yellow-400' : 'text-gray-200'); ?>" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                <?php endfor; ?>
                <span class="text-xs text-gray-400 ml-1"><?php echo e($review->created_at->format('M d, Y')); ?></span>
                <span class="ml-2 text-xs px-2 py-0.5 rounded-full <?php echo e($review->is_approved ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'); ?> font-medium">
                    <?php echo e($review->is_approved ? 'Approved' : 'Pending'); ?>

                </span>
            </div>
            <?php if($review->comment): ?>
            <p class="text-sm text-gray-600"><?php echo e($review->comment); ?></p>
            <?php endif; ?>
        </div>
        <div class="flex items-center gap-2 flex-shrink-0">
            <a href="<?php echo e(route('account.reviews.edit', $review)); ?>" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">Edit</a>
            <form action="<?php echo e(route('account.reviews.destroy', $review)); ?>" method="POST" onsubmit="return confirm('Delete this review?')">
                <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                <button class="text-xs text-red-500 hover:text-red-700 font-medium">Delete</button>
            </form>
        </div>
    </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>
<div class="mt-5"><?php echo e($reviews->links()); ?></div>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.account', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\completeEcom\resources\views/account/reviews/index.blade.php ENDPATH**/ ?>