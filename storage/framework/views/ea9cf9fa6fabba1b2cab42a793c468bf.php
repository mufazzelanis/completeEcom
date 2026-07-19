<?php $__env->startSection('title', 'Dashboard'); ?>

<?php $__env->startSection('content'); ?>

<!-- Stats Grid -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-8">
    <?php $__currentLoopData = [
        ['label' => 'Total Orders', 'value' => $stats['total_orders'], 'icon' => 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z', 'color' => 'blue', 'sub' => $stats['pending_orders'] . ' pending'],
        ['label' => 'Total Products', 'value' => $stats['total_products'], 'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4', 'color' => 'green', 'sub' => $stats['total_categories'] . ' categories'],
        ['label' => 'Customers', 'value' => $stats['total_users'], 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z', 'color' => 'purple', 'sub' => $stats['today_orders'] . ' orders today'],
        ['label' => 'Revenue', 'value' => '৳' . number_format($stats['total_revenue']), 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'yellow', 'sub' => 'Paid orders'],
    ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $stat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center
                    <?php echo e($stat['color'] === 'blue' ? 'bg-blue-100' : ($stat['color'] === 'green' ? 'bg-green-100' : ($stat['color'] === 'purple' ? 'bg-purple-100' : 'bg-yellow-100'))); ?>">
                    <svg class="w-6 h-6 <?php echo e($stat['color'] === 'blue' ? 'text-blue-600' : ($stat['color'] === 'green' ? 'text-green-600' : ($stat['color'] === 'purple' ? 'text-purple-600' : 'text-yellow-600'))); ?>"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo e($stat['icon']); ?>"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-900"><?php echo e($stat['value']); ?></p>
            <p class="text-sm text-gray-500 mt-1"><?php echo e($stat['label']); ?></p>
            <p class="text-xs text-gray-400 mt-1"><?php echo e($stat['sub']); ?></p>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Recent Orders -->
    <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm">
        <div class="flex items-center justify-between p-6 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">Recent Orders</h2>
            <a href="<?php echo e(route('admin.orders.index')); ?>" class="text-orange-600 text-sm hover:text-orange-700">View All</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="text-xs text-gray-500 uppercase tracking-wider border-b border-gray-100">
                        <th class="px-6 py-3 text-left">Order</th>
                        <th class="px-6 py-3 text-left">Customer</th>
                        <th class="px-6 py-3 text-left">Total</th>
                        <th class="px-6 py-3 text-left">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php $__currentLoopData = $recentOrders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4">
                                <a href="<?php echo e(route('admin.orders.show', $order->id)); ?>" class="font-medium text-orange-600 text-sm hover:text-orange-700"><?php echo e($order->order_number); ?></a>
                                <p class="text-xs text-gray-400"><?php echo e($order->created_at->diffForHumans()); ?></p>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?php echo e($order->user->name ?? 'Guest'); ?></td>
                            <td class="px-6 py-4 text-sm font-semibold text-gray-900">৳<?php echo e(number_format($order->total)); ?></td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded-full text-xs font-medium capitalize <?php echo e($order->status_badge); ?>"><?php echo e($order->status); ?></span>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Top Products -->
    <div class="bg-white rounded-2xl shadow-sm">
        <div class="flex items-center justify-between p-6 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">Top Products</h2>
                <a href="<?php echo e(route('admin.products.index')); ?>" class="text-orange-600 text-sm hover:text-orange-700">View All</a>
        </div>
        <div class="p-6 space-y-4">
            <?php $__currentLoopData = $topProducts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="flex items-center space-x-4">
                    <div class="w-10 h-10 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0">
                        <?php if($product->image): ?>
                            <img src="<?php echo e(Storage::url($product->image)); ?>" class="w-full h-full object-cover">
                        <?php endif; ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-800 truncate"><?php echo e($product->name); ?></p>
                        <p class="text-xs text-gray-400"><?php echo e($product->views); ?> views</p>
                    </div>
                    <span class="text-sm font-bold text-gray-900">৳<?php echo e(number_format($product->price)); ?></span>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\completeEcom\resources\views/admin/dashboard.blade.php ENDPATH**/ ?>