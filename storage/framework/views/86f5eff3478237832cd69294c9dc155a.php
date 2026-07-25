<?php $__env->startSection('report-title', 'Sales Report'); ?>

<?php $__env->startSection('report-content'); ?>


<div class="bg-white rounded-2xl shadow-sm p-4 mb-6">
    <form action="<?php echo e(route('admin.reports.sales')); ?>" method="GET" class="flex flex-wrap items-center gap-3">
        <div class="flex items-center gap-2">
            <label class="text-sm text-gray-500">From</label>
            <input type="date" name="from" value="<?php echo e($from->toDateString()); ?>" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div class="flex items-center gap-2">
            <label class="text-sm text-gray-500">To</label>
            <input type="date" name="to" value="<?php echo e($to->toDateString()); ?>" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <select name="group_by" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="day"   <?php echo e($groupBy === 'day'   ? 'selected' : ''); ?>>Daily</option>
            <option value="week"  <?php echo e($groupBy === 'week'  ? 'selected' : ''); ?>>Weekly</option>
            <option value="month" <?php echo e($groupBy === 'month' ? 'selected' : ''); ?>>Monthly</option>
        </select>
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm hover:bg-indigo-700 transition">Apply</button>
        <a href="<?php echo e(route('admin.reports.sales.download', ['from'=>$from->toDateString(),'to'=>$to->toDateString(),'group_by'=>$groupBy])); ?>"
           class="flex items-center gap-2 bg-green-600 text-white px-4 py-2 rounded-xl text-sm hover:bg-green-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Export Excel
        </a>
        <div class="flex gap-2 ml-auto">
            <?php $__currentLoopData = [['7d','7 Days'],['30d','30 Days'],['90d','90 Days'],['ytd','Year to Date']]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$preset,$label]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <a href="<?php echo e(route('admin.reports.sales', ['from' => match($preset){ '7d'=>now()->subDays(6)->toDateString(), '30d'=>now()->subDays(29)->toDateString(), '90d'=>now()->subDays(89)->toDateString(), 'ytd'=>now()->startOfYear()->toDateString() }, 'to' => now()->toDateString(), 'group_by' => $groupBy])); ?>"
               class="px-3 py-2 text-xs rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 transition"><?php echo e($label); ?></a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </form>
</div>


<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <?php
    $cards = [
        ['Total Revenue', '৳'.number_format($summary->total_revenue ?? 0, 2), 'text-green-600', 'bg-green-50'],
        ['Total Orders', number_format($summary->total_orders ?? 0), 'text-indigo-600', 'bg-indigo-50'],
        ['Avg Order Value', '৳'.number_format($summary->avg_order_value ?? 0, 2), 'text-blue-600', 'bg-blue-50'],
        ['Total Discounts', '৳'.number_format($summary->total_discounts ?? 0, 2), 'text-orange-600', 'bg-orange-50'],
    ];
    ?>
    <?php $__currentLoopData = $cards; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$label, $value, $tc, $bg]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2"><?php echo e($label); ?></p>
        <p class="text-2xl font-bold <?php echo e($tc); ?>"><?php echo e($value); ?></p>
    </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>

<div class="grid grid-cols-3 gap-6 mb-6">
    
    <div class="col-span-2 bg-white rounded-2xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Sales Trend</h3>
        <canvas id="salesTrendChart" height="120"></canvas>
    </div>

    
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4">By Payment Method</h3>
        <canvas id="paymentChart" height="180"></canvas>
        <div class="mt-4 space-y-2">
            <?php $__currentLoopData = $byPayment; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="flex items-center justify-between text-sm">
                <span class="text-gray-600 capitalize"><?php echo e($p->payment_method ?? 'Unknown'); ?></span>
                <span class="font-semibold text-gray-800">৳<?php echo e(number_format($p->revenue, 0)); ?></span>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
</div>

<div class="grid grid-cols-2 gap-6 mb-6">
    
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Orders by Status</h3>
        <table class="w-full text-sm">
            <thead><tr class="text-xs text-gray-500 uppercase border-b border-gray-100"><th class="pb-2 text-left">Status</th><th class="pb-2 text-right">Orders</th><th class="pb-2 text-right">Revenue</th></tr></thead>
            <tbody class="divide-y divide-gray-50">
                <?php $__currentLoopData = $byStatus; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td class="py-2"><span class="capitalize px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600"><?php echo e($s->status); ?></span></td>
                    <td class="py-2 text-right font-medium"><?php echo e(number_format($s->count)); ?></td>
                    <td class="py-2 text-right text-gray-600">৳<?php echo e(number_format($s->revenue, 2)); ?></td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
        <?php if($cancelledRevenue > 0): ?>
        <p class="text-xs text-red-500 mt-3">* ৳<?php echo e(number_format($cancelledRevenue,2)); ?> lost to cancelled/refunded orders</p>
        <?php endif; ?>
    </div>

    
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Revenue by Category</h3>
        <?php $totalCatRev = $categoryRevenueTotal; ?>
        <div class="space-y-3">
            <?php $__empty_1 = true; $__currentLoopData = $byCategory; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php $pct = $totalCatRev > 0 ? round(($cat->revenue / $totalCatRev) * 100) : 0; ?>
            <div>
                <div class="flex items-center justify-between text-sm mb-1">
                    <span class="text-gray-700"><?php echo e($cat->category); ?></span>
                    <span class="font-semibold text-gray-800">৳<?php echo e(number_format($cat->revenue,0)); ?> <span class="text-gray-400 font-normal">(<?php echo e($pct); ?>%)</span></span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-1.5">
                    <div class="bg-indigo-500 h-1.5 rounded-full" style="width:<?php echo e($pct); ?>%"></div>
                </div>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <p class="text-gray-400 text-sm text-center py-4">No category data for this period.</p>
            <?php endif; ?>
        </div>
    </div>
</div>


<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-800">Sales Data Table</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr class="text-xs text-gray-500 uppercase tracking-wider">
                <th class="px-6 py-3 text-left">Period</th>
                <th class="px-6 py-3 text-right">Orders</th>
                <th class="px-6 py-3 text-right">Revenue</th>
                <th class="px-6 py-3 text-right">Discounts</th>
                <th class="px-6 py-3 text-right">Shipping</th>
                <th class="px-6 py-3 text-right">Avg Order</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-50">
                <?php $__empty_1 = true; $__currentLoopData = $salesTrend; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3 font-medium text-gray-700"><?php echo e($row->period); ?></td>
                    <td class="px-6 py-3 text-right text-gray-600"><?php echo e(number_format($row->orders)); ?></td>
                    <td class="px-6 py-3 text-right font-semibold text-gray-800">৳<?php echo e(number_format($row->revenue,2)); ?></td>
                    <td class="px-6 py-3 text-right text-orange-600">-৳<?php echo e(number_format($row->discounts,2)); ?></td>
                    <td class="px-6 py-3 text-right text-gray-500">৳<?php echo e(number_format($row->shipping,2)); ?></td>
                    <td class="px-6 py-3 text-right text-gray-600">৳<?php echo e(number_format($row->avg_order,2)); ?></td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="6" class="px-6 py-10 text-center text-gray-400">No sales data for this period.</td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot class="bg-gray-50 border-t border-gray-100 font-semibold">
                <tr>
                    <td class="px-6 py-3 text-gray-700">Total</td>
                    <td class="px-6 py-3 text-right text-gray-800"><?php echo e(number_format($summary->total_orders ?? 0)); ?></td>
                    <td class="px-6 py-3 text-right text-green-700">৳<?php echo e(number_format($summary->total_revenue ?? 0,2)); ?></td>
                    <td class="px-6 py-3 text-right text-orange-600">-৳<?php echo e(number_format($summary->total_discounts ?? 0,2)); ?></td>
                    <td class="px-6 py-3 text-right text-gray-600">৳<?php echo e(number_format($summary->total_shipping ?? 0,2)); ?></td>
                    <td class="px-6 py-3 text-right text-gray-700">৳<?php echo e(number_format($summary->avg_order_value ?? 0,2)); ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>


<div class="bg-white rounded-2xl shadow-sm overflow-hidden mt-6">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-800">Stored Daily Sales Report</h3>
        <p class="text-xs text-gray-400 mt-1">One row per calendar day, saved in the <span class="font-mono">sales_reports</span> database table. It updates automatically whenever an order is placed, changed, or cancelled — this is a permanent record, not a live-only calculation.</p>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr class="text-xs text-gray-500 uppercase tracking-wider">
                <th class="px-6 py-3 text-left">Date</th>
                <th class="px-6 py-3 text-right">Orders</th>
                <th class="px-6 py-3 text-right">Revenue</th>
                <th class="px-6 py-3 text-right">Discounts</th>
                <th class="px-6 py-3 text-right">Shipping</th>
                <th class="px-6 py-3 text-right">Avg Order</th>
                <th class="px-6 py-3 text-right">Cancelled</th>
                <th class="px-6 py-3 text-left">Top Category</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-50">
                <?php $__empty_1 = true; $__currentLoopData = $dailyReports; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3 font-medium text-gray-700"><?php echo e($r->report_date->format('d M Y')); ?></td>
                    <td class="px-6 py-3 text-right text-gray-600"><?php echo e(number_format($r->total_orders)); ?></td>
                    <td class="px-6 py-3 text-right font-semibold text-gray-800">৳<?php echo e(number_format($r->total_revenue,2)); ?></td>
                    <td class="px-6 py-3 text-right text-orange-600">-৳<?php echo e(number_format($r->total_discounts,2)); ?></td>
                    <td class="px-6 py-3 text-right text-gray-500">৳<?php echo e(number_format($r->total_shipping,2)); ?></td>
                    <td class="px-6 py-3 text-right text-gray-600">৳<?php echo e(number_format($r->avg_order_value,2)); ?></td>
                    <td class="px-6 py-3 text-right text-red-500"><?php echo e($r->cancelled_orders); ?> (৳<?php echo e(number_format($r->cancelled_revenue,0)); ?>)</td>
                    <td class="px-6 py-3 text-gray-600"><?php echo e($r->top_category ?? '—'); ?></td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="8" class="px-6 py-10 text-center text-gray-400">No stored snapshots for this period yet — run <span class="font-mono">php artisan sales-report:rebuild</span> to backfill.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const trend = <?php echo json_encode($salesTrend, 15, 512) ?>;
    new Chart(document.getElementById('salesTrendChart'), {
        type: 'bar',
        data: {
            labels: trend.map(r => r.period),
            datasets: [{
                label: 'Revenue (৳)',
                data: trend.map(r => parseFloat(r.revenue)),
                backgroundColor: 'rgba(99,102,241,0.7)',
                borderRadius: 4,
            },{
                label: 'Discounts (৳)',
                data: trend.map(r => parseFloat(r.discounts)),
                backgroundColor: 'rgba(249,115,22,0.7)',
                borderRadius: 4,
            }]
        },
        options: { responsive: true, plugins: { legend: { labels: { font: { size:11 } } } }, scales: { x: { grid: { display:false }, ticks: { font: { size:10 }, maxTicksLimit:15 } }, y: { ticks: { callback: v => '৳'+v.toLocaleString(), font: { size:10 } } } } }
    });

    const pm = <?php echo json_encode($byPayment, 15, 512) ?>;
    new Chart(document.getElementById('paymentChart'), {
        type: 'doughnut',
        data: {
            labels: pm.map(p => p.payment_method || 'Unknown'),
            datasets: [{ data: pm.map(p => parseFloat(p.revenue)), backgroundColor: ['#6366f1','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4'], borderWidth: 2 }]
        },
        options: { responsive: true, plugins: { legend: { display: false } }, cutout: '60%' }
    });
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.reports.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\azad-ecom\resources\views/admin/reports/sales.blade.php ENDPATH**/ ?>