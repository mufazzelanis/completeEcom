<?php $__env->startSection('report-title', 'Product Performance'); ?>

<?php $__env->startSection('report-content'); ?>


<div class="bg-white rounded-2xl shadow-sm p-4 mb-6">
    <form action="<?php echo e(route('admin.reports.products')); ?>" method="GET" class="flex flex-wrap items-center gap-3">
        <div class="flex items-center gap-2">
            <label class="text-sm text-gray-500">From</label>
            <input type="date" name="from" value="<?php echo e($from->toDateString()); ?>" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div class="flex items-center gap-2">
            <label class="text-sm text-gray-500">To</label>
            <input type="date" name="to" value="<?php echo e($to->toDateString()); ?>" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm hover:bg-indigo-700 transition">Apply</button>
        <a href="<?php echo e(route('admin.reports.products.download', ['from'=>$from->toDateString(),'to'=>$to->toDateString()])); ?>"
           class="flex items-center gap-2 bg-green-600 text-white px-4 py-2 rounded-xl text-sm hover:bg-green-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Export Excel
        </a>
        <div class="flex gap-2 ml-auto">
            <?php $__currentLoopData = [['30d','30 Days'],['90d','90 Days']]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$p,$l]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <a href="<?php echo e(route('admin.reports.products', ['from'=>match($p){'30d'=>now()->subDays(29)->toDateString(),'90d'=>now()->subDays(89)->toDateString()},'to'=>now()->toDateString()])); ?>"
               class="px-3 py-2 text-xs rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 transition"><?php echo e($l); ?></a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </form>
</div>

<div class="grid grid-cols-3 gap-6 mb-6">
    
    <div class="col-span-2 bg-white rounded-2xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-1">Top Products by Revenue</h3>
        <p class="text-xs text-gray-400 mb-4"><?php echo e($from->toDateString()); ?> – <?php echo e($to->toDateString()); ?></p>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="text-xs text-gray-500 uppercase border-b border-gray-100"><th class="pb-2 text-left">#</th><th class="pb-2 text-left">Product</th><th class="pb-2 text-right">Units</th><th class="pb-2 text-right">Orders</th><th class="pb-2 text-right">Revenue</th></tr></thead>
                <tbody class="divide-y divide-gray-50">
                    <?php $__empty_1 = true; $__currentLoopData = $topByRevenue; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="hover:bg-gray-50">
                        <td class="py-2.5 pr-3">
                            <span class="w-6 h-6 <?php echo e($i < 3 ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-500'); ?> rounded-full text-xs font-bold flex items-center justify-center"><?php echo e($i+1); ?></span>
                        </td>
                        <td class="py-2.5">
                            <a href="<?php echo e($p->product_id ? route('admin.products.edit', $p->product_id) : '#'); ?>" class="font-medium text-gray-800 hover:text-indigo-600"><?php echo e($p->product_name); ?></a>
                        </td>
                        <td class="py-2.5 text-right text-gray-600"><?php echo e(number_format($p->qty_sold)); ?></td>
                        <td class="py-2.5 text-right text-gray-600"><?php echo e(number_format($p->orders)); ?></td>
                        <td class="py-2.5 text-right font-semibold text-gray-800">৳<?php echo e(number_format($p->revenue,2)); ?></td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="5" class="py-8 text-center text-gray-400">No sales data for this period.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Category Performance</h3>
        <canvas id="catChart" height="220"></canvas>
        <div class="mt-3 space-y-1 max-h-32 overflow-y-auto">
            <?php $__currentLoopData = $categoryPerf->take(6); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="flex items-center justify-between text-xs">
                <span class="text-gray-600"><?php echo e($cat->category); ?></span>
                <span class="font-medium">৳<?php echo e(number_format($cat->revenue,0)); ?></span>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
</div>

<div class="grid grid-cols-3 gap-6 mb-6">
    
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Top by Units Sold</h3>
        <div class="space-y-3">
            <?php $__empty_1 = true; $__currentLoopData = $topByQty; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div class="flex items-center gap-2">
                <span class="text-xs text-gray-400 w-5 text-center"><?php echo e($i+1); ?></span>
                <div class="flex-1 min-w-0">
                    <p class="text-sm text-gray-700 truncate"><?php echo e($p->product_name); ?></p>
                    <div class="w-full bg-gray-100 rounded-full h-1 mt-1">
                        <div class="bg-green-500 h-1 rounded-full" style="width:<?php echo e($topByQty->max('qty_sold') > 0 ? round(($p->qty_sold/$topByQty->max('qty_sold'))*100) : 0); ?>%"></div>
                    </div>
                </div>
                <span class="text-sm font-semibold text-gray-700 flex-shrink-0"><?php echo e(number_format($p->qty_sold)); ?></span>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <p class="text-gray-400 text-sm text-center py-4">No data.</p>
            <?php endif; ?>
        </div>
    </div>

    
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Most Viewed Products</h3>
        <div class="space-y-3">
            <?php $__currentLoopData = $mostViewed; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="flex items-center gap-2">
                <span class="text-xs text-gray-400 w-5 text-center"><?php echo e($i+1); ?></span>
                <div class="flex-1 min-w-0">
                    <a href="<?php echo e(route('admin.products.edit', $p)); ?>" class="text-sm text-gray-700 hover:text-indigo-600 block truncate"><?php echo e($p->name); ?></a>
                    <p class="text-xs text-gray-400"><?php echo e($p->category?->name); ?></p>
                </div>
                <span class="text-sm font-semibold text-indigo-600 flex-shrink-0"><?php echo e(number_format($p->views)); ?></span>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>

    
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-1">Never Sold (Active + In Stock)</h3>
        <p class="text-xs text-gray-400 mb-4">Products with stock that have never had an order</p>
        <?php $__empty_1 = true; $__currentLoopData = $neverSold; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <div class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
            <div class="min-w-0">
                <a href="<?php echo e(route('admin.products.edit', $p)); ?>" class="text-sm text-gray-700 hover:text-indigo-600 truncate block"><?php echo e($p->name); ?></a>
                <p class="text-xs text-gray-400"><?php echo e($p->category?->name); ?> · stock: <?php echo e($p->stock); ?></p>
            </div>
            <span class="text-sm text-gray-500 ml-2">৳<?php echo e(number_format($p->price,2)); ?></span>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <p class="text-green-600 text-sm text-center py-4">All products with stock have been sold!</p>
        <?php endif; ?>
    </div>
</div>


<?php if($lowStock->count()): ?>
<div class="bg-white rounded-2xl shadow-sm p-6 border-l-4 border-orange-400">
    <h3 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
        <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        Low Stock Alert (<?php echo e($lowStock->count()); ?> products)
    </h3>
    <div class="grid grid-cols-3 gap-3">
        <?php $__currentLoopData = $lowStock; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="flex items-center justify-between bg-orange-50 rounded-xl px-4 py-2.5">
            <div class="min-w-0">
                <p class="text-sm font-medium text-gray-800 truncate"><?php echo e($p->name); ?></p>
                <p class="text-xs text-gray-500"><?php echo e($p->category?->name); ?></p>
            </div>
            <span class="text-sm font-bold text-orange-600 ml-2 flex-shrink-0"><?php echo e($p->stock); ?> left</span>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const cats = <?php echo json_encode($categoryPerf->take(8), 15, 512) ?>;
    const palette = ['#6366f1','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#ec4899','#84cc16'];
    new Chart(document.getElementById('catChart'), {
        type: 'doughnut',
        data: {
            labels: cats.map(c => c.category),
            datasets: [{ data: cats.map(c => parseFloat(c.revenue)), backgroundColor: palette, borderWidth: 2 }]
        },
        options: { responsive: true, plugins: { legend: { display:false } }, cutout: '55%' }
    });
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.reports.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\completeEcom\resources\views/admin/reports/products.blade.php ENDPATH**/ ?>