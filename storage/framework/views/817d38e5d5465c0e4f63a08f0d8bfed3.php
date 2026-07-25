<?php $__env->startSection('title', 'Order Details'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-4xl mx-auto px-4 py-8">
    <div class="flex items-center space-x-4 mb-8">
        <a href="<?php echo e(route('orders.index')); ?>" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900"><?php echo e($order->order_number); ?></h1>
            <p class="text-sm text-gray-500">Placed on <?php echo e($order->created_at->format('M d, Y h:i A')); ?></p>
        </div>
        <span class="px-3 py-1 rounded-full text-sm font-semibold <?php echo e($order->status_badge); ?> capitalize ml-auto">
            <?php echo e($order->status); ?>

        </span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="md:col-span-2 space-y-6">
            <!-- Order Items -->
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h2 class="font-semibold text-gray-800 mb-4">Order Items</h2>
                <div class="space-y-4">
                    <?php $__currentLoopData = $order->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="flex items-center space-x-4">
                            <div class="w-16 h-16 bg-gray-100 rounded-xl overflow-hidden flex-shrink-0">
                                <?php if($item->product && $item->product->image): ?>
                                    <img src="<?php echo e(Storage::url($item->product->image)); ?>" class="w-full h-full object-cover">
                                <?php endif; ?>
                            </div>
                            <div class="flex-1">
                                <p class="font-semibold text-gray-800 text-sm"><?php echo e($item->product_name); ?></p>
                                <p class="text-gray-400 text-xs">৳<?php echo e(number_format($item->price)); ?> × <?php echo e($item->quantity); ?></p>
                            </div>
                            <p class="font-bold text-gray-900">৳<?php echo e(number_format($item->subtotal)); ?></p>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>

            <!-- Shipping Info -->
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h2 class="font-semibold text-gray-800 mb-4">Shipping Address</h2>
                <div class="text-sm text-gray-600 space-y-1">
                    <p class="font-semibold text-gray-800"><?php echo e($order->shipping_name); ?></p>
                    <p><?php echo e($order->shipping_phone); ?></p>
                    <p><?php echo e($order->shipping_address); ?></p>
                    <p><?php echo e($order->shipping_city); ?>, <?php echo e($order->shipping_state); ?> <?php echo e($order->shipping_zip); ?></p>
                    <p><?php echo e($order->shipping_country); ?></p>
                </div>
            </div>
        </div>

        <!-- Summary -->
        <div class="space-y-6">
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h2 class="font-semibold text-gray-800 mb-4">Payment Summary</h2>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between text-gray-600"><span>Subtotal</span><span>৳<?php echo e(number_format($order->subtotal)); ?></span></div>
                    <?php if(($order->discount - $order->points_discount_value) > 0): ?>
                        <div class="flex justify-between text-green-600"><span>Discount</span><span>-৳<?php echo e(number_format($order->discount - $order->points_discount_value)); ?></span></div>
                    <?php endif; ?>
                    <?php if($order->points_discount_value > 0): ?>
                        <div class="flex justify-between text-green-600"><span>Points Redeemed (<?php echo e(number_format($order->points_redeemed)); ?> pts)</span><span>-৳<?php echo e(number_format($order->points_discount_value)); ?></span></div>
                    <?php endif; ?>
                    <div class="flex justify-between text-gray-600"><span>Shipping</span><span>৳<?php echo e(number_format($order->shipping)); ?></span></div>
                    <div class="border-t border-gray-100 pt-2 flex justify-between font-bold text-gray-900">
                        <span>Total</span><span>৳<?php echo e(number_format($order->total)); ?></span>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-100">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Payment</span>
                        <span class="font-medium capitalize"><?php echo e($order->payment_method === 'cod' ? 'Cash on Delivery' : strtoupper($order->payment_method)); ?></span>
                    </div>
                    <div class="flex justify-between text-sm mt-2">
                        <span class="text-gray-500">Payment Status</span>
                        <span class="capitalize font-medium <?php echo e($order->payment_status === 'paid' ? 'text-green-600' : 'text-yellow-600'); ?>"><?php echo e($order->payment_status); ?></span>
                    </div>
                </div>
            </div>

            <?php if(in_array($order->status, ['pending', 'processing'])): ?>
                <form action="<?php echo e(route('orders.cancel', $order->id)); ?>" method="POST">
                    <?php echo csrf_field(); ?>
                    <button type="submit" onclick="return confirm('Cancel this order?')"
                        class="w-full border border-red-300 text-red-600 py-2.5 rounded-xl text-sm font-medium hover:bg-red-50 transition">
                        Cancel Order
                    </button>
                </form>
            <?php endif; ?>

            <?php if(in_array($order->status, ['delivered', 'completed'])): ?>
                <?php if($existingReturn): ?>
                    <a href="<?php echo e(route('account.returns.show', $existingReturn)); ?>" class="block bg-gray-50 hover:bg-gray-100 transition rounded-xl p-4 text-center">
                        <p class="text-xs text-gray-500 mb-1">Return Request</p>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold <?php echo e($existingReturn->statusBadge()); ?>">
                            <?php echo e($existingReturn->statusLabel()); ?>

                        </span>
                        <p class="text-xs text-gray-400 mt-1"><?php echo e($existingReturn->return_number); ?></p>
                    </a>
                <?php else: ?>
                    <a href="<?php echo e(route('orders.return.create', $order)); ?>"
                        class="flex items-center justify-center w-full border border-gray-300 text-gray-700 py-2.5 rounded-xl text-sm font-medium hover:bg-gray-50 transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                        Request Return
                    </a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\completeEcom\resources\views/orders/show.blade.php ENDPATH**/ ?>