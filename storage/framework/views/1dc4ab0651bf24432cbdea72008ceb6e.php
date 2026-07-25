<?php $__env->startSection('title', 'Stock Adjustments'); ?>

<?php $__env->startSection('content'); ?>
<div class="flex items-center justify-between mb-6">
    <p class="text-sm text-gray-500">Returns, damage, and manual stock adjustments</p>
    <a href="<?php echo e(route('admin.stock-adjustments.create')); ?>" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-indigo-700 transition flex items-center space-x-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        <span>New Adjustment</span>
    </a>
</div>

<?php if(session('success')): ?><div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm"><?php echo e(session('success')); ?></div><?php endif; ?>

<div class="bg-white rounded-2xl shadow-sm p-4 mb-4">
    <form method="GET" class="flex flex-wrap gap-3">
        <select name="product" class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">All Products</option>
            <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($p->id); ?>" <?php echo e(request('product') == $p->id ? 'selected' : ''); ?>><?php echo e($p->name); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <select name="type" class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">All Types</option>
            <option value="return_in"   <?php echo e(request('type')=='return_in'   ? 'selected':''); ?>>Customer Return</option>
            <option value="damage_out"  <?php echo e(request('type')=='damage_out'  ? 'selected':''); ?>>Damage / Loss</option>
            <option value="manual_in"   <?php echo e(request('type')=='manual_in'   ? 'selected':''); ?>>Manual Stock In</option>
            <option value="manual_out"  <?php echo e(request('type')=='manual_out'  ? 'selected':''); ?>>Manual Stock Out</option>
            <option value="purchase_in" <?php echo e(request('type')=='purchase_in' ? 'selected':''); ?>>Purchase Received</option>
        </select>
        <input type="date" name="date_from" value="<?php echo e(request('date_from')); ?>"
            class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        <input type="date" name="date_to" value="<?php echo e(request('date_to')); ?>"
            class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm hover:bg-indigo-700 transition">Filter</button>
        <?php if(request()->hasAny(['product','type','date_from','date_to'])): ?>
            <a href="<?php echo e(route('admin.stock-adjustments.index')); ?>" class="px-4 py-2 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50 transition">Clear</a>
        <?php endif; ?>
    </form>
</div>

<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr class="text-xs text-gray-500 uppercase tracking-wider">
                <th class="px-6 py-3 text-left">Date</th>
                <th class="px-6 py-3 text-left">Product</th>
                <th class="px-6 py-3 text-center">Type</th>
                <th class="px-6 py-3 text-center">Qty Change</th>
                <th class="px-6 py-3 text-center">Before</th>
                <th class="px-6 py-3 text-center">After</th>
                <th class="px-6 py-3 text-left">Reason</th>
                <th class="px-6 py-3 text-left">By</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            <?php $__empty_1 = true; $__currentLoopData = $adjustments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $adj): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr class="hover:bg-gray-50 transition">
                <td class="px-6 py-3 text-xs text-gray-500 whitespace-nowrap"><?php echo e($adj->created_at->format('d M Y H:i')); ?></td>
                <td class="px-6 py-3">
                    <p class="text-sm font-medium text-gray-900"><?php echo e($adj->product->name); ?></p>
                    <?php if($adj->reference): ?><p class="text-xs text-gray-400"><?php echo e($adj->reference); ?></p><?php endif; ?>
                </td>
                <td class="px-6 py-3 text-center">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?php echo e($adj->typeBadge()); ?>"><?php echo e($adj->typeLabel()); ?></span>
                </td>
                <td class="px-6 py-3 text-center">
                    <span class="text-sm font-bold <?php echo e($adj->quantity > 0 ? 'text-green-600' : 'text-red-600'); ?>">
                        <?php echo e($adj->quantity > 0 ? '+' : ''); ?><?php echo e($adj->quantity); ?>

                    </span>
                </td>
                <td class="px-6 py-3 text-center text-sm text-gray-500"><?php echo e($adj->stock_before); ?></td>
                <td class="px-6 py-3 text-center text-sm font-medium text-gray-800"><?php echo e($adj->stock_after); ?></td>
                <td class="px-6 py-3 text-sm text-gray-600 max-w-xs truncate" title="<?php echo e($adj->reason); ?>"><?php echo e($adj->reason); ?></td>
                <td class="px-6 py-3 text-sm text-gray-500"><?php echo e($adj->adjustedBy->name); ?></td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr><td colspan="8" class="px-6 py-12 text-center text-gray-400 text-sm">No adjustments found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php if($adjustments->hasPages()): ?>
    <div class="px-6 py-4 border-t border-gray-100"><?php echo e($adjustments->withQueryString()->links()); ?></div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\completeEcom\resources\views/admin/stock_adjustments/index.blade.php ENDPATH**/ ?>