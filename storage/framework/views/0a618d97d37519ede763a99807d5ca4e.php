<?php $__env->startSection('title', 'Two-Factor Authentication'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-xl">
    <h1 class="text-2xl font-bold text-gray-900 mb-2">Set Up Two-Factor Authentication</h1>
    <p class="text-sm text-gray-500 mb-6">Your admin account requires two-factor authentication. We've sent a 6-digit code to <strong><?php echo e($email); ?></strong> — enter it below to finish setup.</p>

    <?php if(session('error')): ?><div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm"><?php echo e(session('error')); ?></div><?php endif; ?>

    <?php if($devCode): ?>
    <div class="mb-6 bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-xl text-sm">
        <strong>Dev only</strong> (this box never appears in production, and is only ever populated when <code>APP_ENV</code> isn't <code>production</code>) — your code is
        <span class="font-mono font-bold tracking-widest"><?php echo e($devCode); ?></span>
    </div>
    <?php endif; ?>

    <div class="bg-white rounded-2xl shadow-sm p-8 space-y-6">
        <form action="<?php echo e(route('admin.two-factor.confirm')); ?>" method="POST" class="max-w-xs mx-auto">
            <?php echo csrf_field(); ?>
            <label class="block text-sm font-medium text-gray-700 mb-1 text-center">Enter the 6-digit code</label>
            <input type="text" name="code" inputmode="numeric" maxlength="6" autocomplete="one-time-code" autofocus placeholder="123456"
                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-center text-lg tracking-widest font-mono focus:outline-none focus:ring-2 focus:ring-orange-500 <?php $__errorArgs = ['code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-400 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
            <?php $__errorArgs = ['code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-red-500 text-xs mt-1 text-center"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            <button type="submit" class="w-full mt-4 bg-orange-600 text-white py-2.5 rounded-xl text-sm font-semibold hover:bg-orange-700 transition">Verify & Enable</button>
        </form>
        <p class="text-center text-xs text-gray-400">
            Didn't get the email? <a href="<?php echo e(route('admin.two-factor.show')); ?>" class="text-orange-600 hover:text-orange-700 font-medium">Send a new code</a>
        </p>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\completeEcom\resources\views/admin/two-factor/setup.blade.php ENDPATH**/ ?>