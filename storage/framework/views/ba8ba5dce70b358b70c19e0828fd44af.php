<?php $__env->startSection('title', 'Referral Program'); ?>

<?php $__env->startSection('content'); ?>
<h1 class="text-xl font-bold text-gray-800 mb-1">Referral Program</h1>
<p class="text-sm text-gray-500 mb-5">Your points balance also includes points earned from your own purchases — use them for a discount at <a href="<?php echo e(route('cart.index')); ?>" class="text-indigo-600 hover:underline">checkout</a>.</p>


<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-5">
    <?php $__currentLoopData = [
        ['Points Balance', number_format($stats['points_balance']).' pts', 'text-purple-600', 'bg-purple-50'],
        ['Total Referrals', $stats['total_uses'], 'text-indigo-600', 'bg-indigo-50'],
        ['Points from Referrals', number_format($stats['total_earned']).' pts', 'text-green-600', 'bg-green-50'],
        ['Points Redeemed', number_format($stats['points_redeemed']).' pts', 'text-blue-600', 'bg-blue-50'],
    ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$label, $value, $color, $bg]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <div class="bg-white rounded-2xl shadow-sm p-4 text-center">
        <p class="text-xl font-bold <?php echo e($color); ?>"><?php echo e($value); ?></p>
        <p class="text-xs text-gray-500 mt-0.5"><?php echo e($label); ?></p>
    </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>


<div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl p-6 text-white mb-5"
     x-data="{ copied: false }">
    <h2 class="font-bold text-lg mb-1">Your Referral Code</h2>
    <p class="text-indigo-100 text-sm mb-4">Share this code with friends. When they make their first order, you both earn rewards!</p>
    <div class="flex items-center gap-3 bg-white/20 rounded-xl px-4 py-3">
        <span class="font-mono text-xl font-bold tracking-widest flex-1" id="ref-code"><?php echo e($referral->code); ?></span>
        <button @click="navigator.clipboard.writeText('<?php echo e($referral->code); ?>').then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
            class="bg-white text-indigo-600 text-xs font-semibold px-3 py-1.5 rounded-lg hover:bg-indigo-50 transition flex-shrink-0">
            <span x-show="!copied">Copy</span>
            <span x-show="copied" x-cloak>Copied!</span>
        </button>
    </div>
    <div class="mt-3 flex items-center gap-2">
        <p class="text-xs text-indigo-100">Share link:</p>
        <span class="text-xs text-white font-mono bg-white/20 px-2 py-1 rounded truncate max-w-xs" id="ref-link"><?php echo e(url('/')); ?>?ref=<?php echo e($referral->code); ?></span>
        <button @click="navigator.clipboard.writeText('<?php echo e(url('/')); ?>?ref=<?php echo e($referral->code); ?>')"
            class="text-xs text-indigo-200 hover:text-white transition">Copy</button>
    </div>
</div>


<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100">
        <h2 class="font-semibold text-gray-800">Points Activity</h2>
    </div>
    <?php if($pointTransactions->isEmpty()): ?>
    <div class="px-5 py-12 text-center text-gray-400 text-sm">No points activity yet. Refer a friend or make a purchase to start earning!</div>
    <?php else: ?>
    <table class="w-full text-sm">
        <thead class="bg-gray-50"><tr class="text-xs text-gray-500 uppercase">
            <th class="px-5 py-3 text-left">Type</th>
            <th class="px-5 py-3 text-right">Points</th>
            <th class="px-5 py-3 text-left">Description</th>
            <th class="px-5 py-3 text-center">Order</th>
            <th class="px-5 py-3 text-center">Date</th>
        </tr></thead>
        <tbody class="divide-y divide-gray-50">
            <?php $__currentLoopData = $pointTransactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tx): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr class="hover:bg-gray-50">
                <td class="px-5 py-3">
                    <span class="text-xs px-2 py-0.5 rounded-full font-semibold <?php echo e($tx->type_badge); ?>"><?php echo e($tx->type_label); ?></span>
                </td>
                <td class="px-5 py-3 text-right font-bold <?php echo e($tx->points >= 0 ? 'text-green-600' : 'text-red-600'); ?>">
                    <?php echo e($tx->points >= 0 ? '+' : ''); ?><?php echo e(number_format($tx->points)); ?>

                </td>
                <td class="px-5 py-3 text-gray-600"><?php echo e($tx->description); ?></td>
                <td class="px-5 py-3 text-center">
                    <?php if($tx->order): ?>
                        <a href="<?php echo e(route('orders.show', $tx->order)); ?>" class="text-indigo-600 hover:text-indigo-800 text-xs font-mono"><?php echo e($tx->order->order_number); ?></a>
                    <?php else: ?>
                        <span class="text-gray-300 text-xs">—</span>
                    <?php endif; ?>
                </td>
                <td class="px-5 py-3 text-center text-xs text-gray-400"><?php echo e($tx->created_at->format('M d, Y')); ?></td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>
    <div class="px-5 py-4 border-t border-gray-100"><?php echo e($pointTransactions->links()); ?></div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.account', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\completeEcom\resources\views/account/referral/index.blade.php ENDPATH**/ ?>