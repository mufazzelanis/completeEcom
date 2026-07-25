<?php $__env->startSection('title', 'Reviews'); ?>

<?php $__env->startSection('content'); ?>
<div class="flex items-center gap-4 mb-6">
    <form action="<?php echo e(route('admin.reviews.index')); ?>" method="GET" class="flex items-center space-x-3">
        <select name="status" class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">All Reviews</option>
            <option value="approved" <?php echo e(request('status') === 'approved' ? 'selected' : ''); ?>>Approved</option>
            <option value="pending" <?php echo e(request('status') === 'pending' ? 'selected' : ''); ?>>Pending</option>
        </select>
        <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-xl text-sm hover:bg-gray-700 transition">Filter</button>
    </form>
</div>

<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr class="text-xs text-gray-500 uppercase tracking-wider">
                <th class="px-6 py-3 text-left">User</th>
                <th class="px-6 py-3 text-left">Product</th>
                <th class="px-6 py-3 text-center">Rating</th>
                <th class="px-6 py-3 text-left">Comment</th>
                <th class="px-6 py-3 text-center">Status</th>
                <th class="px-6 py-3 text-center">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            <?php $__empty_1 = true; $__currentLoopData = $reviews; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $review): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-4 text-sm font-medium text-gray-800"><?php echo e($review->user->name); ?></td>
                    <td class="px-6 py-4 text-sm text-gray-600 max-w-36 truncate"><?php echo e($review->product->name); ?></td>
                    <td class="px-6 py-4 text-center">
                        <div class="flex justify-center">
                            <?php for($i = 1; $i <= 5; $i++): ?>
                                <svg class="w-4 h-4 <?php echo e($i <= $review->rating ? 'text-yellow-400' : 'text-gray-300'); ?>" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            <?php endfor; ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600 max-w-48 truncate"><?php echo e($review->comment ?? '-'); ?></td>
                    <td class="px-6 py-4 text-center">
                        <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo e($review->is_approved ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'); ?>">
                            <?php echo e($review->is_approved ? 'Approved' : 'Pending'); ?>

                        </span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <div class="flex items-center justify-center space-x-2">
                            <a href="<?php echo e(route('admin.reviews.edit', $review->id)); ?>" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Edit</a>
                            <form action="<?php echo e(route('admin.reviews.approve', $review->id)); ?>" method="POST">
                                <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                                <button type="submit" class="<?php echo e($review->is_approved ? 'text-yellow-600 hover:text-yellow-800' : 'text-green-600 hover:text-green-800'); ?> text-sm font-medium">
                                    <?php echo e($review->is_approved ? 'Unapprove' : 'Approve'); ?>

                                </button>
                            </form>
                            <form action="<?php echo e(route('admin.reviews.destroy', $review->id)); ?>" method="POST" onsubmit="return confirm('Delete review?')">
                                <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                <button type="submit" class="text-red-500 hover:text-red-700 text-sm font-medium">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="6" class="px-6 py-12 text-center text-gray-400">No reviews found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <div class="px-6 py-4 border-t border-gray-100"><?php echo e($reviews->links()); ?></div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\completeEcom\resources\views/admin/reviews/index.blade.php ENDPATH**/ ?>