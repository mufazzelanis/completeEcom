<?php $__env->startSection('title', 'Orders'); ?>

<?php $__env->startSection('content'); ?>
<div class="flex flex-wrap items-center gap-4 mb-6">
    <form action="<?php echo e(route('admin.orders.index')); ?>" method="GET" class="flex items-center space-x-3 flex-wrap gap-2">
        <input type="text" name="search" value="<?php echo e(request('search')); ?>" placeholder="Search order or customer..."
            class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 w-56">
        <select name="status" class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none">
            <option value="">All Status</option>
            <?php $__currentLoopData = ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($s); ?>" <?php echo e(request('status') === $s ? 'selected' : ''); ?>><?php echo e(ucfirst($s)); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <option value="cancelled,refunded" <?php echo e(request('status') === 'cancelled,refunded' ? 'selected' : ''); ?>>Cancelled / Refunded</option>
        </select>
        <select name="payment_status" class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none">
            <option value="">All Payments</option>
            <?php $__currentLoopData = ['pending', 'paid', 'failed', 'refunded']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ps): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($ps); ?>" <?php echo e(request('payment_status') === $ps ? 'selected' : ''); ?>><?php echo e(ucfirst($ps)); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-xl text-sm hover:bg-gray-700 transition">Filter</button>
    </form>
    <?php if($flaggedCount > 0): ?>
    <a href="<?php echo e(route('admin.orders.index', ['fraud' => 1])); ?>"
       class="flex items-center gap-2 bg-red-50 border border-red-200 text-red-700 px-3 py-2 rounded-xl text-sm font-medium hover:bg-red-100 transition <?php echo e(request('fraud') ? 'ring-2 ring-red-400' : ''); ?>">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
        Fraud Flagged <span class="bg-red-600 text-white text-xs px-1.5 py-0.5 rounded-full font-bold"><?php echo e($flaggedCount); ?></span>
    </a>
    <?php endif; ?>
</div>

<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr class="text-xs text-gray-500 uppercase tracking-wider">
                <th class="px-6 py-3 text-left">Order</th>
                <th class="px-6 py-3 text-left">Customer</th>
                <th class="px-6 py-3 text-right">Total</th>
                <th class="px-6 py-3 text-center">Payment</th>
                <th class="px-6 py-3 text-center">Status</th>
                <th class="px-6 py-3 text-center">Date</th>
                <th class="px-6 py-3 text-center">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            <?php $__empty_1 = true; $__currentLoopData = $orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <a href="<?php echo e(route('admin.orders.show', $order->id)); ?>" class="font-medium text-indigo-600 text-sm hover:text-indigo-700"><?php echo e($order->order_number); ?></a>
                            <?php if($order->is_fraud_flagged): ?>
                            <span title="Fraud Flagged — Score: <?php echo e($order->fraud_score); ?>">
                                <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                            </span>
                            <?php elseif($order->fraud_checked_at && $order->fraud_score >= 20): ?>
                            <span class="text-xs text-yellow-500 font-medium" title="Medium fraud risk — Score: <?php echo e($order->fraud_score); ?>">⚠</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-700"><?php echo e($order->user->name ?? 'Guest'); ?></td>
                    <td class="px-6 py-4 text-right font-semibold text-gray-900 text-sm">৳<?php echo e(number_format($order->total)); ?></td>
                    <td class="px-6 py-4 text-center">
                        <span class="px-2 py-1 rounded-full text-xs font-medium capitalize <?php echo e($order->payment_status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'); ?>">
                            <?php echo e($order->payment_status); ?>

                        </span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="px-2 py-1 rounded-full text-xs font-medium capitalize <?php echo e($order->status_badge); ?>"><?php echo e($order->status); ?></span>
                    </td>
                    <td class="px-6 py-4 text-center text-xs text-gray-500"><?php echo e($order->created_at->format('M d, Y')); ?></td>
                    <td class="px-6 py-4 text-center">
                        <div class="flex items-center justify-center gap-3">
                            <a href="<?php echo e(route('admin.orders.show', $order->id)); ?>" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">View</a>
                            <a href="<?php echo e(route('admin.orders.invoice', $order->id)); ?>" class="text-red-500 hover:text-red-700 text-sm font-medium flex items-center gap-1" title="Download Invoice PDF">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                PDF
                            </a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="7" class="px-6 py-12 text-center text-gray-400">No orders found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <div class="px-6 py-4 border-t border-gray-100"><?php echo e($orders->links()); ?></div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\completeEcom\resources\views/admin/orders/index.blade.php ENDPATH**/ ?>