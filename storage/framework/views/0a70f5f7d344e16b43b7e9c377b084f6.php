<?php $__env->startSection('title', 'Stock Reasons'); ?>

<?php $__env->startSection('content'); ?>
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Stock Reasons</h1>
        <p class="text-sm text-gray-500 mt-1">Manage the reason options shown when adjusting stock</p>
    </div>
    <a href="<?php echo e(route('admin.stock-adjustments.index')); ?>" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
        View Stock History →
    </a>
</div>

<?php if(session('success')): ?>
<div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm"><?php echo e(session('success')); ?></div>
<?php endif; ?>

<div class="space-y-6">
    
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">Add New Reason</h3>
        <form action="<?php echo e(route('admin.stock-reasons.store')); ?>" method="POST" class="space-y-4">
            <?php echo csrf_field(); ?>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reason Label <span class="text-red-500">*</span></label>
                    <input type="text" name="label" value="<?php echo e(old('label')); ?>" required placeholder="e.g. Damaged in warehouse"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 <?php $__errorArgs = ['label'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-400 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                    <?php $__errorArgs = ['label'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Applies To</label>
                    <select name="type" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="any" <?php echo e(old('type') == 'any' ? 'selected' : ''); ?>>Any Type</option>
                        <option value="return_in" <?php echo e(old('type') == 'return_in' ? 'selected' : ''); ?>>Customer Return</option>
                        <option value="manual_in" <?php echo e(old('type') == 'manual_in' ? 'selected' : ''); ?>>Manual Stock In</option>
                        <option value="purchase_in" <?php echo e(old('type') == 'purchase_in' ? 'selected' : ''); ?>>Purchase Received</option>
                        <option value="damage_out" <?php echo e(old('type') == 'damage_out' ? 'selected' : ''); ?>>Damage / Loss</option>
                        <option value="manual_out" <?php echo e(old('type') == 'manual_out' ? 'selected' : ''); ?>>Manual Stock Out</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sort Order</label>
                    <input type="number" name="sort_order" value="<?php echo e(old('sort_order', 0)); ?>"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>
            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" checked class="rounded text-indigo-600">
                    <span class="text-sm font-medium text-gray-700">Active</span>
                </label>
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2.5 rounded-xl text-sm font-medium hover:bg-indigo-700 transition">Add Reason</button>
            </div>
        </form>
    </div>

    
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <div class="divide-y divide-gray-50">
            <?php $__currentLoopData = $reasons; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reason): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div x-data="{ editing: false }" class="px-6 py-4">
                <div x-show="!editing">
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex-1 min-w-0 flex items-center gap-3">
                            <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-600"><?php echo e($reason->typeLabel()); ?></span>
                            <p class="font-medium text-gray-800 text-sm truncate"><?php echo e($reason->label); ?></p>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <span class="text-xs text-gray-400">#<?php echo e($reason->sort_order); ?></span>
                            <form action="<?php echo e(route('admin.stock-reasons.toggle', $reason)); ?>" method="POST">
                                <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                                <button type="submit" class="px-2 py-1 rounded-full text-xs font-medium <?php echo e($reason->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'); ?> hover:opacity-80 transition">
                                    <?php echo e($reason->is_active ? 'Active' : 'Inactive'); ?>

                                </button>
                            </form>
                            <button @click="editing = true" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Edit</button>
                            <form action="<?php echo e(route('admin.stock-reasons.destroy', $reason)); ?>" method="POST" onsubmit="return confirm('Delete this reason?')">
                                <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                <button class="text-red-500 hover:text-red-700 text-sm font-medium">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div x-show="editing" x-cloak>
                    <form action="<?php echo e(route('admin.stock-reasons.update', $reason)); ?>" method="POST" class="flex flex-wrap gap-3 items-center">
                        <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
                        <input type="text" name="label" value="<?php echo e($reason->label); ?>" required
                            class="flex-1 min-w-[200px] border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <select name="type" class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="any" <?php echo e($reason->type == 'any' ? 'selected' : ''); ?>>Any Type</option>
                            <option value="return_in" <?php echo e($reason->type == 'return_in' ? 'selected' : ''); ?>>Customer Return</option>
                            <option value="manual_in" <?php echo e($reason->type == 'manual_in' ? 'selected' : ''); ?>>Manual Stock In</option>
                            <option value="purchase_in" <?php echo e($reason->type == 'purchase_in' ? 'selected' : ''); ?>>Purchase Received</option>
                            <option value="damage_out" <?php echo e($reason->type == 'damage_out' ? 'selected' : ''); ?>>Damage / Loss</option>
                            <option value="manual_out" <?php echo e($reason->type == 'manual_out' ? 'selected' : ''); ?>>Manual Stock Out</option>
                        </select>
                        <input type="number" name="sort_order" value="<?php echo e($reason->sort_order); ?>"
                            class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 w-24">
                        <label class="flex items-center gap-1.5">
                            <input type="checkbox" name="is_active" value="1" <?php echo e($reason->is_active ? 'checked' : ''); ?> class="rounded text-indigo-600">
                            <span class="text-sm text-gray-600">Active</span>
                        </label>
                        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-indigo-700 transition">Save</button>
                        <button type="button" @click="editing = false" class="px-4 py-2 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50">Cancel</button>
                    </form>
                </div>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

            <?php if($reasons->isEmpty()): ?>
            <div class="px-6 py-12 text-center text-gray-400">No stock reasons yet. Add one above.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>[x-cloak]{display:none!important}</style>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\completeEcom\resources\views/admin/stock_reasons/index.blade.php ENDPATH**/ ?>