<?php $__env->startSection('title', 'Coupons'); ?>

<?php $__env->startSection('content'); ?>
<div class="flex items-center justify-between mb-6">
    <p class="text-sm text-gray-500">Manage discount coupons</p>
    <a href="<?php echo e(route('admin.coupons.create')); ?>" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-indigo-700 transition flex items-center space-x-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        <span>Add Coupon</span>
    </a>
</div>

<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr class="text-xs text-gray-500 uppercase tracking-wider">
                <th class="px-6 py-3 text-left">Code</th>
                <th class="px-6 py-3 text-left">Type</th>
                <th class="px-6 py-3 text-right">Value</th>
                <th class="px-6 py-3 text-right">Min Order</th>
                <th class="px-6 py-3 text-center">Used/Max</th>
                <th class="px-6 py-3 text-center">Expires</th>
                <th class="px-6 py-3 text-center">Status</th>
                <th class="px-6 py-3 text-center">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            <?php $__empty_1 = true; $__currentLoopData = $coupons; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $coupon): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-4">
                        <span class="font-mono font-semibold text-gray-900 bg-gray-100 px-2 py-1 rounded text-sm"><?php echo e($coupon->code); ?></span>
                    </td>
                    <td class="px-6 py-4 text-sm capitalize text-gray-600"><?php echo e($coupon->type); ?></td>
                    <td class="px-6 py-4 text-right font-semibold text-gray-900 text-sm">
                        <?php echo e($coupon->type === 'percentage' ? $coupon->value . '%' : '৳' . number_format($coupon->value)); ?>

                    </td>
                    <td class="px-6 py-4 text-right text-sm text-gray-600">৳<?php echo e(number_format($coupon->min_order_amount)); ?></td>
                    <td class="px-6 py-4 text-center text-sm text-gray-600"><?php echo e($coupon->used_count); ?>/<?php echo e($coupon->max_uses ?? '∞'); ?></td>
                    <td class="px-6 py-4 text-center text-xs text-gray-500"><?php echo e($coupon->expires_at ? $coupon->expires_at->format('M d, Y') : 'Never'); ?></td>
                    <td class="px-6 py-4 text-center">
                        <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo e($coupon->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'); ?>">
                            <?php echo e($coupon->is_active ? 'Active' : 'Inactive'); ?>

                        </span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <div class="flex items-center justify-center space-x-2">
                            <a href="<?php echo e(route('admin.coupons.edit', $coupon->id)); ?>" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Edit</a>
                            <form action="<?php echo e(route('admin.coupons.destroy', $coupon->id)); ?>" method="POST" onsubmit="return confirm('Delete coupon?')">
                                <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                <button type="submit" class="text-red-500 hover:text-red-700 text-sm font-medium">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="8" class="px-6 py-12 text-center text-gray-400">No coupons found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <div class="px-6 py-4 border-t border-gray-100"><?php echo e($coupons->links()); ?></div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\completeEcom\resources\views/admin/coupons/index.blade.php ENDPATH**/ ?>