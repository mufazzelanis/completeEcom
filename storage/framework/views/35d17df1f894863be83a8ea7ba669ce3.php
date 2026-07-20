<?php $__env->startSection('title', 'Newsletter Subscribers'); ?>

<?php $__env->startSection('content'); ?>
<div class="flex items-center justify-between mb-6">
    <p class="text-sm text-gray-500"><?php echo e($activeCount); ?> active subscriber(s)</p>
</div>

<?php if(session('success')): ?><div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm"><?php echo e(session('success')); ?></div><?php endif; ?>

<div class="bg-white rounded-2xl shadow-sm p-4 mb-4">
    <form method="GET" class="flex gap-3">
        <input type="text" name="search" value="<?php echo e(request('search')); ?>" placeholder="Search email…"
            class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 flex-1 max-w-xs">
        <select name="status" class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">All statuses</option>
            <option value="active" <?php echo e(request('status') === 'active' ? 'selected' : ''); ?>>Active</option>
            <option value="unsubscribed" <?php echo e(request('status') === 'unsubscribed' ? 'selected' : ''); ?>>Unsubscribed</option>
        </select>
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm hover:bg-indigo-700 transition">Filter</button>
        <?php if(request('search') || request('status')): ?><a href="<?php echo e(route('admin.newsletter.index')); ?>" class="px-4 py-2 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50 transition">Clear</a><?php endif; ?>
    </form>
</div>

<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr class="text-xs text-gray-500 uppercase tracking-wider">
                <th class="px-6 py-3 text-left">Email</th>
                <th class="px-6 py-3 text-center">Status</th>
                <th class="px-6 py-3 text-left">Subscribed</th>
                <th class="px-6 py-3 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            <?php $__empty_1 = true; $__currentLoopData = $subscribers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $subscriber): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr class="hover:bg-gray-50 transition">
                <td class="px-6 py-4 text-sm text-gray-800"><?php echo e($subscriber->email); ?></td>
                <td class="px-6 py-4 text-center">
                    <?php if($subscriber->is_active): ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Active</span>
                    <?php else: ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Unsubscribed</span>
                    <?php endif; ?>
                </td>
                <td class="px-6 py-4 text-sm text-gray-500"><?php echo e($subscriber->subscribed_at?->format('M d, Y') ?? '—'); ?></td>
                <td class="px-6 py-4 text-right">
                    <form action="<?php echo e(route('admin.newsletter.destroy', $subscriber)); ?>" method="POST" onsubmit="return confirm('Remove this subscriber?')">
                        <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                        <button class="text-red-500 hover:text-red-700 text-sm font-medium">Remove</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr><td colspan="4" class="px-6 py-12 text-center text-gray-400 text-sm">No subscribers yet.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php if($subscribers->hasPages()): ?>
    <div class="px-6 py-4 border-t border-gray-100"><?php echo e($subscribers->links()); ?></div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\azad-ecom\resources\views/admin/newsletter/index.blade.php ENDPATH**/ ?>