<?php $__env->startSection('title', 'Order Details'); ?>

<?php $__env->startSection('content'); ?>
<?php
$riskColors = [
    'critical' => ['bg'=>'bg-red-600',    'text'=>'text-red-600',    'light'=>'bg-red-50',    'border'=>'border-red-400'],
    'high'     => ['bg'=>'bg-orange-500', 'text'=>'text-orange-600', 'light'=>'bg-orange-50', 'border'=>'border-orange-400'],
    'medium'   => ['bg'=>'bg-yellow-500', 'text'=>'text-yellow-600', 'light'=>'bg-yellow-50', 'border'=>'border-yellow-400'],
    'low'      => ['bg'=>'bg-green-500',  'text'=>'text-green-600',  'light'=>'bg-green-50',  'border'=>'border-green-400'],
];
$riskLevel = match(true) {
    ($order->fraud_score ?? 0) >= 60 => 'critical',
    ($order->fraud_score ?? 0) >= 50 => 'high',
    ($order->fraud_score ?? 0) >= 20 => 'medium',
    default => 'low',
};
$rc = $riskColors[$riskLevel];
?>

<div class="flex items-center justify-between mb-6">
    <div class="flex items-center space-x-4">
        <a href="<?php echo e(route('admin.orders.index')); ?>" class="text-indigo-600 hover:text-indigo-700 text-sm flex items-center space-x-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            <span>Back</span>
        </a>
        <h1 class="font-semibold text-gray-800"><?php echo e($order->order_number); ?></h1>
        <?php if($order->is_fraud_flagged): ?>
        <span class="flex items-center gap-1 bg-red-100 text-red-700 text-xs font-semibold px-2.5 py-1 rounded-full">
            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
            Fraud Flagged
        </span>
        <?php endif; ?>
    </div>
    <a href="<?php echo e(route('admin.orders.invoice', $order)); ?>"
       class="flex items-center gap-2 bg-red-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-red-700 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        Download Invoice PDF
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <h2 class="font-semibold text-gray-800 mb-4">Order Items</h2>
            <div class="space-y-4">
                <?php $__currentLoopData = $order->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="flex items-center space-x-4 pb-4 border-b border-gray-100 last:border-0 last:pb-0">
                        <div class="w-16 h-16 bg-gray-100 rounded-xl overflow-hidden flex-shrink-0">
                            <?php if($item->product && $item->product->image): ?>
                                <img src="<?php echo e(Storage::url($item->product->image)); ?>" class="w-full h-full object-cover">
                            <?php endif; ?>
                        </div>
                        <div class="flex-1">
                            <p class="font-medium text-gray-800 text-sm"><?php echo e($item->product_name); ?></p>
                            <p class="text-xs text-gray-400">৳<?php echo e(number_format($item->price)); ?> × <?php echo e($item->quantity); ?></p>
                        </div>
                        <p class="font-bold text-gray-900">৳<?php echo e(number_format($item->subtotal)); ?></p>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-6">
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h2 class="font-semibold text-gray-800 mb-3">Customer</h2>
                <p class="font-medium text-gray-800 text-sm"><?php echo e($order->user->name ?? 'Guest'); ?></p>
                <p class="text-sm text-gray-500"><?php echo e($order->user->email ?? $order->guest_email ?? 'N/A'); ?></p>
                <p class="text-sm text-gray-500"><?php echo e($order->user->phone ?? 'N/A'); ?></p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h2 class="font-semibold text-gray-800 mb-3">Shipping Address</h2>
                <div class="text-sm text-gray-600 space-y-1">
                    <p class="font-medium text-gray-800"><?php echo e($order->shipping_name); ?></p>
                    <p><?php echo e($order->shipping_phone); ?></p>
                    <p><?php echo e($order->shipping_address); ?></p>
                    <p><?php echo e($order->shipping_city); ?>, <?php echo e($order->shipping_state); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="space-y-6">
        
        <div class="bg-white rounded-2xl shadow-sm p-6 border-l-4 <?php echo e($rc['border']); ?>">
            <div class="flex items-start justify-between mb-3">
                <div>
                    <h2 class="font-semibold text-gray-800 flex items-center gap-2">
                        <svg class="w-4 h-4 <?php echo e($rc['text']); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        Fraud Analysis
                    </h2>
                    <?php if($order->fraud_checked_at): ?>
                    <p class="text-xs text-gray-400 mt-0.5">Last checked <?php echo e($order->fraud_checked_at->diffForHumans()); ?></p>
                    <?php endif; ?>
                </div>
                <form method="POST" action="<?php echo e(route('admin.orders.fraud-recheck', $order)); ?>">
                    <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                    <button type="submit" class="text-xs text-indigo-600 hover:text-indigo-800 flex items-center gap-1 px-2 py-1 border border-indigo-200 rounded-lg hover:bg-indigo-50 transition">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Re-check
                    </button>
                </form>
            </div>

            
            <div class="flex items-center gap-4 mb-4">
                <div class="flex-shrink-0 w-16 h-16 rounded-full <?php echo e($rc['light']); ?> flex flex-col items-center justify-center border-2 <?php echo e($rc['border']); ?>">
                    <span class="text-xl font-bold <?php echo e($rc['text']); ?>"><?php echo e($order->fraud_score ?? 0); ?></span>
                    <span class="text-xs <?php echo e($rc['text']); ?>">/ 100</span>
                </div>
                <div class="flex-1">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-sm font-semibold text-gray-700">Risk Score</span>
                        <span class="text-xs font-bold uppercase tracking-wide <?php echo e($rc['text']); ?>"><?php echo e(strtoupper($riskLevel)); ?></span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-2">
                        <div class="<?php echo e($rc['bg']); ?> h-2 rounded-full transition-all" style="width:<?php echo e($order->fraud_score ?? 0); ?>%"></div>
                    </div>
                    <div class="flex justify-between text-xs text-gray-300 mt-1">
                        <span>Low</span><span>Medium</span><span>High</span><span>Critical</span>
                    </div>
                </div>
            </div>

            
            <?php if(! empty($order->fraud_flags)): ?>
            <div class="space-y-1.5">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Risk Factors</p>
                <?php $__currentLoopData = $order->fraud_flags; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $flag): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="flex items-start gap-2 text-xs <?php echo e($rc['light']); ?> rounded-lg px-3 py-2">
                    <svg class="w-3.5 h-3.5 <?php echo e($rc['text']); ?> flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    <span class="text-gray-700"><?php echo e($flag); ?></span>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
            <?php else: ?>
            <div class="flex items-center gap-2 text-green-600 text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                No risk factors detected
            </div>
            <?php endif; ?>
        </div>

        <!-- Update Status -->
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <h2 class="font-semibold text-gray-800 mb-4">Update Status</h2>
            <form action="<?php echo e(route('admin.orders.status', $order->id)); ?>" method="POST">
                <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                <select name="status" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm mb-3 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <?php $__currentLoopData = ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($s); ?>" <?php echo e($order->status === $s ? 'selected' : ''); ?>><?php echo e(ucfirst($s)); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <button type="submit" class="w-full bg-indigo-600 text-white py-2 rounded-xl text-sm font-medium hover:bg-indigo-700 transition">Update Status</button>
            </form>
        </div>

        <!-- Update Payment -->
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <h2 class="font-semibold text-gray-800 mb-4">Payment Status</h2>
            <form action="<?php echo e(route('admin.orders.update', $order->id)); ?>" method="POST">
                <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
                <select name="payment_status" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm mb-3 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <?php $__currentLoopData = ['pending', 'paid', 'failed', 'refunded']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($s); ?>" <?php echo e($order->payment_status === $s ? 'selected' : ''); ?>><?php echo e(ucfirst($s)); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <button type="submit" class="w-full bg-green-600 text-white py-2 rounded-xl text-sm font-medium hover:bg-green-700 transition">Update Payment</button>
            </form>
        </div>

        <!-- Summary -->
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <h2 class="font-semibold text-gray-800 mb-4">Order Summary</h2>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between text-gray-600"><span>Subtotal</span><span>৳<?php echo e(number_format($order->subtotal)); ?></span></div>
                <?php if($order->discount > 0): ?>
                    <div class="flex justify-between text-green-600"><span>Discount</span><span>-৳<?php echo e(number_format($order->discount)); ?></span></div>
                <?php endif; ?>
                <div class="flex justify-between text-gray-600"><span>Shipping</span><span>৳<?php echo e(number_format($order->shipping)); ?></span></div>
                <div class="border-t border-gray-100 pt-2 flex justify-between font-bold text-gray-900">
                    <span>Total</span><span>৳<?php echo e(number_format($order->total)); ?></span>
                </div>
                <div class="pt-2 flex justify-between text-sm text-gray-500">
                    <span>Payment Method</span>
                    <span class="capitalize"><?php echo e($order->payment_method === 'cod' ? 'Cash on Delivery' : strtoupper($order->payment_method)); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\completeEcom\resources\views/admin/orders/show.blade.php ENDPATH**/ ?>