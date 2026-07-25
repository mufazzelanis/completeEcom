<?php $__env->startSection('title', 'Vendors'); ?>

<?php $__env->startSection('content'); ?>
<div class="flex items-center justify-between mb-6">
    <p class="text-sm text-gray-500">Manage marketplace sellers (<?php echo e($pendingCount); ?> pending)</p>
</div>

<?php if(session('success')): ?><div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm"><?php echo e(session('success')); ?></div><?php endif; ?>
<?php if(session('error')): ?><div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm"><?php echo e(session('error')); ?></div><?php endif; ?>

<div class="bg-white rounded-2xl shadow-sm p-4 mb-4">
    <form method="GET" class="flex gap-3">
        <input type="text" name="search" value="<?php echo e(request('search')); ?>" placeholder="Search business name, email…"
            class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 flex-1 max-w-xs">
        <select name="status" class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">All statuses</option>
            <?php $__currentLoopData = ['pending','approved','rejected','suspended']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($s); ?>" <?php echo e(request('status') === $s ? 'selected' : ''); ?>><?php echo e(ucfirst($s)); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm hover:bg-indigo-700 transition">Filter</button>
        <?php if(request('search') || request('status')): ?><a href="<?php echo e(route('admin.vendors.index')); ?>" class="px-4 py-2 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50 transition">Clear</a><?php endif; ?>
    </form>
</div>

<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr class="text-xs text-gray-500 uppercase tracking-wider">
                <th class="px-6 py-3 text-left">Business</th>
                <th class="px-6 py-3 text-left">Owner</th>
                <th class="px-6 py-3 text-center">Products</th>
                <th class="px-6 py-3 text-center">Status</th>
                <th class="px-6 py-3 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            <?php $__empty_1 = true; $__currentLoopData = $vendors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $vendor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr class="hover:bg-gray-50 transition">
                <td class="px-6 py-4">
                    <p class="text-sm font-medium text-gray-900"><?php echo e($vendor->business_name); ?></p>
                    <?php if($vendor->email): ?><p class="text-xs text-gray-400"><?php echo e($vendor->email); ?></p><?php endif; ?>
                </td>
                <td class="px-6 py-4">
                    <p class="text-sm text-gray-700"><?php echo e($vendor->user->name); ?></p>
                    <p class="text-xs text-gray-400"><?php echo e($vendor->user->email); ?></p>
                </td>
                <td class="px-6 py-4 text-center text-sm text-gray-600"><?php echo e($vendor->products_count); ?></td>
                <td class="px-6 py-4 text-center">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo e($vendor->statusBadge()); ?>"><?php echo e(ucfirst($vendor->status)); ?></span>
                </td>
                <td class="px-6 py-4 text-right">
                    <div class="flex justify-end items-center space-x-3">
                        <a href="<?php echo e(route('admin.vendors.show', $vendor)); ?>" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">View</a>
                        <?php if($vendor->status === 'pending'): ?>
                        <form action="<?php echo e(route('admin.vendors.approve', $vendor)); ?>" method="POST">
                            <?php echo csrf_field(); ?>
                            <button class="text-green-600 hover:text-green-800 text-sm font-medium">Approve</button>
                        </form>
                        <form action="<?php echo e(route('admin.vendors.reject', $vendor)); ?>" method="POST" onsubmit="return confirm('Reject this application?')">
                            <?php echo csrf_field(); ?>
                            <button class="text-red-500 hover:text-red-700 text-sm font-medium">Reject</button>
                        </form>
                        <?php elseif($vendor->status === 'approved'): ?>
                        <form action="<?php echo e(route('admin.vendors.suspend', $vendor)); ?>" method="POST" onsubmit="return confirm('Suspend this vendor?')">
                            <?php echo csrf_field(); ?>
                            <button class="text-orange-500 hover:text-orange-700 text-sm font-medium">Suspend</button>
                        </form>
                        <?php elseif(in_array($vendor->status, ['suspended','rejected'])): ?>
                        <form action="<?php echo e(route('admin.vendors.approve', $vendor)); ?>" method="POST">
                            <?php echo csrf_field(); ?>
                            <button class="text-green-600 hover:text-green-800 text-sm font-medium">Re-approve</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr><td colspan="5" class="px-6 py-12 text-center text-gray-400 text-sm">No vendors found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php if($vendors->hasPages()): ?>
    <div class="px-6 py-4 border-t border-gray-100"><?php echo e($vendors->links()); ?></div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\completeEcom\resources\views/admin/vendors/index.blade.php ENDPATH**/ ?>