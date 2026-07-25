<?php $__env->startSection('title', 'Security'); ?>
<?php $__env->startSection('pageTitle', 'Security'); ?>

<?php $__env->startSection('content'); ?>
<h1 class="text-xl font-bold text-gray-800 mb-5">Security</h1>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2">
        
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden mb-5">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-gray-800">Login Activity</h2>
                <p class="text-xs text-gray-400 mt-0.5">Recent sign-ins to your account (last 20)</p>
            </div>
            <?php if($activities->isEmpty()): ?>
            <div class="px-5 py-8 text-center text-gray-400 text-sm">No login records yet.</div>
            <?php else: ?>
            <div class="divide-y divide-gray-50">
                <?php $__currentLoopData = $activities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $activity): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="flex items-center gap-4 px-5 py-3.5 <?php echo e($activity->is_current ? 'bg-green-50/50' : ''); ?>">
                    <div class="w-9 h-9 bg-gray-100 rounded-xl flex items-center justify-center flex-shrink-0">
                        <?php if($activity->device === 'Mobile'): ?>
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        <?php else: ?>
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-800 flex items-center gap-2">
                            <?php echo e($activity->browser); ?> on <?php echo e($activity->platform); ?>

                            <?php if($activity->is_current): ?><span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-semibold">Current</span><?php endif; ?>
                        </p>
                        <p class="text-xs text-gray-400"><?php echo e($activity->ip_address); ?> · <?php echo e($activity->device); ?></p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="text-xs text-gray-500"><?php echo e($activity->created_at->format('M d, Y')); ?></p>
                        <p class="text-xs text-gray-400"><?php echo e($activity->created_at->format('H:i')); ?></p>
                    </div>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="space-y-4">
        
        <div class="bg-white rounded-2xl shadow-sm p-5">
            <h3 class="font-semibold text-gray-800 text-sm mb-3">Security Tips</h3>
            <ul class="space-y-2 text-xs text-gray-600">
                <?php $__currentLoopData = [
                    'Use a strong, unique password',
                    'Never share your password',
                    'Log out on shared devices',
                    'Check login activity regularly',
                    'Keep your email updated',
                ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tip): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <li class="flex items-start gap-2">
                    <svg class="w-3.5 h-3.5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                    <?php echo e($tip); ?>

                </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </div>

        <div class="bg-white rounded-2xl shadow-sm p-5">
            <h3 class="font-semibold text-gray-800 text-sm mb-3">Quick Actions</h3>
            <div class="space-y-2">
                <a href="<?php echo e(route('account.profile')); ?>" class="flex items-center gap-3 text-sm text-gray-600 hover:text-indigo-600 transition py-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                    Change Password
                </a>
                <form action="<?php echo e(route('logout')); ?>" method="POST">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="flex items-center gap-3 text-sm text-red-500 hover:text-red-700 transition py-1 w-full">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        Sign Out All Devices
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.account', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\completeEcom\resources\views/account/security/index.blade.php ENDPATH**/ ?>