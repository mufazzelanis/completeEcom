<?php $__env->startSection('title', 'Two-Factor Authentication'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-xl">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Two-Factor Authentication</h1>

    <?php if(session('success')): ?><div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm"><?php echo e(session('success')); ?></div><?php endif; ?>
    <?php if(session('error')): ?><div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm"><?php echo e(session('error')); ?></div><?php endif; ?>

    <div class="bg-white rounded-2xl shadow-sm p-6 mb-5 flex items-center gap-3">
        <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
            <p class="text-sm font-semibold text-gray-800">Two-factor authentication is enabled</p>
            <p class="text-xs text-gray-500">You have <?php echo e($recoveryCodesCount); ?> unused recovery code<?php echo e($recoveryCodesCount === 1 ? '' : 's'); ?> remaining.</p>
        </div>
    </div>

    <?php if(session('recovery_codes')): ?>
    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5 mb-5">
        <p class="text-sm font-semibold text-amber-800 mb-2">Save these recovery codes now — they won't be shown again</p>
        <p class="text-xs text-amber-700 mb-3">Each code can be used once, in place of your authenticator app, if you lose access to it.</p>
        <div class="grid grid-cols-2 gap-2 font-mono text-sm bg-white rounded-xl p-4">
            <?php $__currentLoopData = session('recovery_codes'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $code): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div><?php echo e($code); ?></div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="bg-white rounded-2xl shadow-sm p-6 mb-5">
        <h3 class="text-sm font-semibold text-gray-800 mb-3">Recovery Codes</h3>
        <form action="<?php echo e(route('admin.two-factor.recovery-codes')); ?>" method="POST" onsubmit="return confirm('Generate new recovery codes? Your old codes will stop working.')">
            <?php echo csrf_field(); ?>
            <button type="submit" class="text-sm text-orange-600 hover:text-orange-800 font-medium">Regenerate Recovery Codes</button>
        </form>
    </div>

    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h3 class="text-sm font-semibold text-gray-800 mb-3">Disable Two-Factor Authentication</h3>
        <form action="<?php echo e(route('admin.two-factor.disable')); ?>" method="POST" class="max-w-xs" onsubmit="return confirm('Disable two-factor authentication on your account?')">
            <?php echo csrf_field(); ?>
            <label class="block text-xs font-medium text-gray-500 mb-1">Confirm your password</label>
            <input type="password" name="password" required
                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-red-400 <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-400 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
            <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            <button type="submit" class="w-full mt-3 bg-red-600 text-white py-2.5 rounded-xl text-sm font-semibold hover:bg-red-700 transition">Disable 2FA</button>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\completeEcom\resources\views/admin/two-factor/show.blade.php ENDPATH**/ ?>