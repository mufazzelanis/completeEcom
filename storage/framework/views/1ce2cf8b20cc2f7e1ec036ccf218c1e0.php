<?php $__env->startSection('title', 'Edit Review'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-xl">
    <a href="<?php echo e(route('admin.reviews.index')); ?>" class="text-indigo-600 hover:text-indigo-700 text-sm flex items-center gap-2 mb-6">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        <span>Back to Reviews</span>
    </a>

    <?php if($errors->any()): ?>
    <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6">
        <ul class="list-disc list-inside space-y-1">
            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li class="text-sm text-red-600"><?php echo e($e); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
    </div>
    <?php endif; ?>

    <div class="bg-white rounded-2xl shadow-sm p-6">
        <div class="flex items-center gap-3 mb-5 pb-5 border-b border-gray-100">
            <?php if($review->product?->image): ?>
            <img src="<?php echo e(\Illuminate\Support\Facades\Storage::url($review->product->image)); ?>" class="w-14 h-14 object-cover rounded-xl border border-gray-100">
            <?php endif; ?>
            <div>
                <p class="font-semibold text-gray-800"><?php echo e($review->product->name ?? 'Unknown product'); ?></p>
                <p class="text-xs text-gray-400">by <?php echo e($review->user->name ?? 'Unknown customer'); ?> &middot; <?php echo e($review->created_at->format('d M Y')); ?></p>
            </div>
        </div>

        <form action="<?php echo e(route('admin.reviews.update', $review)); ?>" method="POST" class="space-y-5">
            <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Rating</label>
                <div class="flex items-center gap-1" x-data="{ rating: <?php echo e(old('rating', $review->rating)); ?> }">
                    <?php for($i = 1; $i <= 5; $i++): ?>
                    <label class="cursor-pointer">
                        <input type="radio" name="rating" value="<?php echo e($i); ?>" class="sr-only" x-on:change="rating = <?php echo e($i); ?>" <?php echo e(old('rating', $review->rating) == $i ? 'checked' : ''); ?>>
                        <svg class="w-8 h-8 transition" :class="rating >= <?php echo e($i); ?> ? 'text-yellow-400' : 'text-gray-200'" fill="currentColor" viewBox="0 0 20 20"
                            x-on:click="rating = <?php echo e($i); ?>">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                    </label>
                    <?php endfor; ?>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Review Text</label>
                <textarea name="comment" rows="5" maxlength="1000" placeholder="No comment left by the customer..."
                    class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none"><?php echo e(old('comment', $review->comment)); ?></textarea>
                <p class="text-xs text-gray-400 mt-1">Editing this replaces exactly what the customer sees on the product page.</p>
            </div>

            <div class="flex items-center space-x-3">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="is_approved" value="1" <?php echo e(old('is_approved', $review->is_approved) ? 'checked' : ''); ?> class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-indigo-600 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full"></div>
                </label>
                <span class="text-sm text-gray-700">Approved (visible on the storefront)</span>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2.5 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition">Save Changes</button>
                <a href="<?php echo e(route('admin.reviews.index')); ?>" class="bg-gray-100 text-gray-600 px-6 py-2.5 rounded-xl text-sm font-semibold hover:bg-gray-200 transition">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\azad-ecom\resources\views/admin/reviews/edit.blade.php ENDPATH**/ ?>