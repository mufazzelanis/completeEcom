<?php $__env->startSection('title', 'Vendor Details'); ?>

<?php $__env->startSection('content'); ?>
<a href="<?php echo e(route('admin.vendors.index')); ?>" class="text-indigo-600 hover:text-indigo-700 text-sm flex items-center space-x-2 mb-6">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
    <span>Back to Vendors</span>
</a>

<?php if(session('success')): ?><div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm"><?php echo e(session('success')); ?></div><?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="bg-white rounded-2xl shadow-sm p-6 text-center">
        <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <span class="text-indigo-600 font-bold text-2xl"><?php echo e(strtoupper(substr($vendor->business_name, 0, 1))); ?></span>
        </div>
        <h2 class="text-lg font-bold text-gray-900"><?php echo e($vendor->business_name); ?></h2>
        <p class="text-gray-500 text-sm"><?php echo e($vendor->email); ?></p>
        <p class="text-gray-500 text-sm mt-1"><?php echo e($vendor->phone); ?></p>
        <div class="mt-4">
            <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo e($vendor->statusBadge()); ?>"><?php echo e(ucfirst($vendor->status)); ?></span>
        </div>
        <p class="text-xs text-gray-400 mt-3">Applied <?php echo e($vendor->created_at->format('M d, Y')); ?></p>
        <?php if($vendor->approved_at): ?>
        <p class="text-xs text-gray-400">Approved <?php echo e($vendor->approved_at->format('M d, Y')); ?><?php if($vendor->approver): ?> by <?php echo e($vendor->approver->name); ?><?php endif; ?></p>
        <?php endif; ?>

        <div class="mt-5 flex flex-col gap-2">
            <?php if($vendor->status === 'pending'): ?>
            <form action="<?php echo e(route('admin.vendors.approve', $vendor)); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <button class="w-full bg-green-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-green-700 transition">Approve</button>
            </form>
            <form action="<?php echo e(route('admin.vendors.reject', $vendor)); ?>" method="POST" onsubmit="return confirm('Reject this application?')">
                <?php echo csrf_field(); ?>
                <button class="w-full bg-red-50 text-red-600 px-4 py-2 rounded-xl text-sm font-medium hover:bg-red-100 transition">Reject</button>
            </form>
            <?php elseif($vendor->status === 'approved'): ?>
            <form action="<?php echo e(route('admin.vendors.suspend', $vendor)); ?>" method="POST" onsubmit="return confirm('Suspend this vendor?')">
                <?php echo csrf_field(); ?>
                <button class="w-full bg-orange-50 text-orange-600 px-4 py-2 rounded-xl text-sm font-medium hover:bg-orange-100 transition">Suspend</button>
            </form>
            <?php elseif(in_array($vendor->status, ['suspended', 'rejected'])): ?>
            <form action="<?php echo e(route('admin.vendors.approve', $vendor)); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <button class="w-full bg-green-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-green-700 transition">Re-approve</button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <h2 class="font-semibold text-gray-800 mb-3">Business Details</h2>
            <p class="text-sm text-gray-600 whitespace-pre-line"><?php echo e($vendor->description ?: 'No description provided.'); ?></p>
            <?php if($vendor->status === 'rejected' && $vendor->rejection_reason): ?>
            <p class="text-sm text-red-600 mt-3"><strong>Rejection reason:</strong> <?php echo e($vendor->rejection_reason); ?></p>
            <?php endif; ?>
        </div>

        <div class="bg-white rounded-2xl shadow-sm p-6">
            <h2 class="font-semibold text-gray-800 mb-1">Products</h2>
            <p class="text-sm text-gray-500"><?php echo e($vendor->products_count); ?> product(s) listed under this vendor.</p>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\completeEcom\resources\views/admin/vendors/show.blade.php ENDPATH**/ ?>