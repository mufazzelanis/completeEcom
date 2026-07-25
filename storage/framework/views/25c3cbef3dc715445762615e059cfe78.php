<?php $__env->startSection('title', 'Payment Methods'); ?>

<?php $__env->startSection('content'); ?>
<div class="flex items-center justify-between mb-6">
    <p class="text-sm text-gray-500">Configure how customers pay at checkout</p>
    <a href="<?php echo e(route('admin.payment-methods.create')); ?>" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-indigo-700 transition flex items-center space-x-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        <span>Add Method</span>
    </a>
</div>

<?php if(session('success')): ?><div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm"><?php echo e(session('success')); ?></div><?php endif; ?>
<?php if(session('error')): ?><div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm"><?php echo e(session('error')); ?></div><?php endif; ?>

<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr class="text-xs text-gray-500 uppercase tracking-wider">
                <th class="px-6 py-3 text-left">Method</th>
                <th class="px-6 py-3 text-left">Type</th>
                <th class="px-6 py-3 text-left">Account / Number</th>
                <th class="px-6 py-3 text-center">Charge</th>
                <th class="px-6 py-3 text-center">Sort</th>
                <th class="px-6 py-3 text-center">Status</th>
                <th class="px-6 py-3 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            <?php $__empty_1 = true; $__currentLoopData = $methods; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $method): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr class="hover:bg-gray-50 transition <?php echo e($method->is_active ? '' : 'opacity-60'); ?>">
                <td class="px-6 py-4">
                    <div class="flex items-center space-x-3">
                        <?php if($method->logo): ?>
                            <img src="<?php echo e(Storage::url($method->logo)); ?>" class="w-8 h-8 object-contain rounded">
                        <?php else: ?>
                            <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center">
                                <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                            </div>
                        <?php endif; ?>
                        <div>
                            <p class="text-sm font-semibold text-gray-900"><?php echo e($method->name); ?></p>
                            <p class="text-xs text-gray-400 font-mono"><?php echo e($method->slug); ?></p>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo e($method->typeBadge()); ?>"><?php echo e($method->typeLabel()); ?></span>
                </td>
                <td class="px-6 py-4">
                    <?php if($method->account_number): ?>
                        <p class="text-sm text-gray-700 font-mono"><?php echo e($method->account_number); ?></p>
                        <?php if($method->account_name): ?><p class="text-xs text-gray-400"><?php echo e($method->account_name); ?></p><?php endif; ?>
                    <?php elseif($method->bank_name): ?>
                        <p class="text-sm text-gray-700"><?php echo e($method->bank_name); ?></p>
                    <?php else: ?>
                        <span class="text-gray-300 text-xs">—</span>
                    <?php endif; ?>
                </td>
                <td class="px-6 py-4 text-center text-sm text-gray-600">
                    <?php if($method->charge_type === 'none'): ?> <span class="text-gray-400">None</span>
                    <?php elseif($method->charge_type === 'fixed'): ?> ৳<?php echo e(number_format($method->charge_value, 2)); ?>

                    <?php else: ?> <?php echo e($method->charge_value); ?>%
                    <?php endif; ?>
                </td>
                <td class="px-6 py-4 text-center text-sm text-gray-500"><?php echo e($method->sort_order); ?></td>
                <td class="px-6 py-4 text-center">
                    <form action="<?php echo e(route('admin.payment-methods.toggle', $method)); ?>" method="POST">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors <?php echo e($method->is_active ? 'bg-indigo-600' : 'bg-gray-200'); ?>">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform <?php echo e($method->is_active ? 'translate-x-6' : 'translate-x-1'); ?>"></span>
                        </button>
                    </form>
                </td>
                <td class="px-6 py-4 text-right">
                    <div class="flex justify-end space-x-2">
                        <a href="<?php echo e(route('admin.payment-methods.edit', $method)); ?>" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Edit</a>
                        <form action="<?php echo e(route('admin.payment-methods.destroy', $method)); ?>" method="POST" onsubmit="return confirm('Delete this payment method?')">
                            <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                            <button class="text-red-500 hover:text-red-700 text-sm font-medium">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr><td colspan="7" class="px-6 py-12 text-center text-gray-400 text-sm">No payment methods configured.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\completeEcom\resources\views/admin/payment_methods/index.blade.php ENDPATH**/ ?>