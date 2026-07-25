<?php $__env->startSection('title', 'My Orders'); ?>
<?php $__env->startSection('pageTitle', 'My Orders'); ?>

<?php $__env->startSection('content'); ?>
<div class="flex items-center justify-between mb-5">
    <h1 class="text-xl font-bold text-gray-800">My Orders</h1>
    <div class="flex gap-1.5 overflow-x-auto pb-1 -mb-1">
        <?php $__currentLoopData = ['all' => 'All', 'pending' => 'Pending', 'processing' => 'Processing', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $val => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <a href="<?php echo e(route('orders.index', $val !== 'all' ? ['status' => $val] : [])); ?>"
            class="px-3 py-1.5 rounded-xl text-xs font-semibold transition whitespace-nowrap <?php echo e((request('status', 'all') === $val) ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100'); ?>">
            <?php echo e($label); ?>

        </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</div>

<?php if($orders->isEmpty()): ?>
<div class="bg-white rounded-2xl shadow-sm p-16 text-center">
    <svg class="w-16 h-16 text-gray-200 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
    <p class="text-gray-500 text-sm mb-4">No orders found.</p>
    <a href="<?php echo e(route('shop.index')); ?>" class="inline-block bg-indigo-600 text-white px-5 py-2 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition">Start Shopping</a>
</div>
<?php else: ?>
<div class="space-y-3">
    <?php $__currentLoopData = $orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <div class="bg-white rounded-2xl shadow-sm p-5 hover:shadow-md transition">
        
        <div class="flex items-start justify-between gap-3 mb-3">
            <div class="min-w-0">
                <p class="text-xs text-gray-400">Order #</p>
                <p class="font-bold text-gray-900 text-sm"><?php echo e($order->order_number); ?></p>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo e($order->status_badge); ?> capitalize flex-shrink-0"><?php echo e($order->status); ?></span>
        </div>

        
        <div class="grid grid-cols-3 gap-3 mb-3 text-center">
            <div>
                <p class="text-xs text-gray-400">Date</p>
                <p class="text-sm text-gray-700"><?php echo e($order->created_at->format('M d, Y')); ?></p>
            </div>
            <div>
                <p class="text-xs text-gray-400">Items</p>
                <p class="text-sm font-medium text-gray-700"><?php echo e($order->items->count()); ?></p>
            </div>
            <div>
                <p class="text-xs text-gray-400">Total</p>
                <p class="font-bold text-gray-900 text-sm">৳<?php echo e(number_format($order->total)); ?></p>
            </div>
        </div>

        
        <div class="flex items-center gap-2 overflow-x-auto border-t border-gray-100 pt-3">
            <?php $__currentLoopData = $order->items->take(5); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="flex items-center gap-2 flex-shrink-0">
                <div class="w-10 h-10 bg-gray-100 rounded-lg overflow-hidden">
                    <?php if($item->product?->image): ?>
                    <img src="<?php echo e(\Illuminate\Support\Facades\Storage::url($item->product->image)); ?>" class="w-full h-full object-cover">
                    <?php endif; ?>
                </div>
                <div>
                    <p class="text-xs text-gray-700 font-medium max-w-20 truncate"><?php echo e($item->product_name); ?></p>
                    <p class="text-xs text-gray-400">×<?php echo e($item->quantity); ?></p>
                </div>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <?php if($order->items->count() > 5): ?>
            <span class="text-xs text-gray-400 flex-shrink-0">+<?php echo e($order->items->count() - 5); ?> more</span>
            <?php endif; ?>
        </div>

        
        <div class="flex items-center gap-3 mt-3 pt-3 border-t border-gray-100">
            <a href="<?php echo e(route('orders.show', $order)); ?>" class="flex-1 text-center bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition text-xs font-semibold px-3 py-2 rounded-xl">View Details</a>
            <?php if(in_array($order->status, ['pending','processing'])): ?>
            <form action="<?php echo e(route('orders.cancel', $order)); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <button class="text-xs text-red-500 hover:text-red-700 font-medium px-3 py-2" onclick="return confirm('Cancel this order?')">Cancel</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>
<div class="mt-5"><?php echo e($orders->links()); ?></div>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.account', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\completeEcom\resources\views/account/orders/index.blade.php ENDPATH**/ ?>