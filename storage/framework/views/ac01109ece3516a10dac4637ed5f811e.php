<?php $__env->startSection('title', 'Become a Seller'); ?>

<?php $__env->startSection('content'); ?>
<div class="flex items-center gap-4 mb-5">
    <h1 class="text-xl font-bold text-gray-800">Become a Seller</h1>
</div>

<?php if(session('success')): ?><div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm"><?php echo e(session('success')); ?></div><?php endif; ?>
<?php if(session('error')): ?><div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm"><?php echo e(session('error')); ?></div><?php endif; ?>

<div class="bg-white rounded-2xl shadow-sm p-6 max-w-2xl">
    <?php if($vendor): ?>
        <p class="text-sm text-gray-600 mb-3">You applied to become a seller as <strong><?php echo e($vendor->business_name); ?></strong>.</p>
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo e($vendor->statusBadge()); ?>"><?php echo e(ucfirst($vendor->status)); ?></span>
        <?php if($vendor->status === 'rejected' && $vendor->rejection_reason): ?>
            <p class="text-sm text-red-600 mt-3">Reason: <?php echo e($vendor->rejection_reason); ?></p>
        <?php endif; ?>
    <?php else: ?>
        <form action="<?php echo e(route('vendor.apply.store')); ?>" method="POST" class="space-y-4">
            <?php echo csrf_field(); ?>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Business Name <span class="text-red-500">*</span></label>
                <input type="text" name="business_name" value="<?php echo e(old('business_name')); ?>" required
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <?php $__errorArgs = ['business_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                    <input type="text" name="phone" value="<?php echo e(old('phone')); ?>"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Business Email</label>
                    <input type="email" name="email" value="<?php echo e(old('email')); ?>"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tell us about your business</label>
                <textarea name="description" rows="5" maxlength="2000"
                    placeholder="What do you sell? Where are your products made or sourced from?"
                    class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none"><?php echo e(old('description')); ?></textarea>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2.5 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition">Submit Application</button>
            </div>
        </form>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.account', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\completeEcom\resources\views/vendor-registration/create.blade.php ENDPATH**/ ?>