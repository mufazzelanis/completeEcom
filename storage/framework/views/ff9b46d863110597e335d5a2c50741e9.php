<?php $__env->startSection('title', 'Homepage Sections'); ?>

<?php $__env->startSection('content'); ?>
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Homepage Sections</h1>
        <p class="text-sm text-gray-500 mt-1">Control what shows on your storefront homepage — Featured Products, Top Selling, New Arrivals, and any custom section you add.</p>
    </div>
    <a href="<?php echo e(route('admin.home-sections.create')); ?>" class="bg-orange-600 text-white px-5 py-2.5 rounded-xl text-sm font-semibold hover:bg-orange-700 transition flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Add Section
    </a>
</div>

<?php if(session('success')): ?><div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm"><?php echo e(session('success')); ?></div><?php endif; ?>
<?php if(session('error')): ?><div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm"><?php echo e(session('error')); ?></div><?php endif; ?>

<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-100 text-xs text-gray-500 uppercase">
            <tr>
                <th class="px-6 py-3 text-left w-20">Order</th>
                <th class="px-6 py-3 text-left">Section</th>
                <th class="px-6 py-3 text-left">Source</th>
                <th class="px-6 py-3 text-center">Products</th>
                <th class="px-6 py-3 text-center">Status</th>
                <th class="px-6 py-3 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            <?php $__empty_1 = true; $__currentLoopData = $sections; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $section): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-3">
                    <div class="flex flex-col gap-1">
                        <form action="<?php echo e(route('admin.home-sections.move-up', $section)); ?>" method="POST">
                            <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                            <button type="submit" class="text-gray-400 hover:text-orange-600 <?php echo e($loop->first ? 'invisible' : ''); ?>" title="Move up">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                            </button>
                        </form>
                        <form action="<?php echo e(route('admin.home-sections.move-down', $section)); ?>" method="POST">
                            <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                            <button type="submit" class="text-gray-400 hover:text-orange-600 <?php echo e($loop->last ? 'invisible' : ''); ?>" title="Move down">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                        </form>
                    </div>
                </td>
                <td class="px-6 py-3">
                    <p class="text-sm font-semibold text-gray-800"><?php echo e($section->title); ?></p>
                    <?php if($section->subtitle): ?><p class="text-xs text-gray-400"><?php echo e($section->subtitle); ?></p><?php endif; ?>
                </td>
                <td class="px-6 py-3 text-sm text-gray-600">
                    <?php echo e(ucwords(str_replace('_', ' ', $section->source_type))); ?>

                    <?php if($section->category): ?>
                        <span class="text-xs text-gray-400">→ <?php echo e($section->category->name); ?></span>
                    <?php endif; ?>
                </td>
                <td class="px-6 py-3 text-center text-sm text-gray-600"><?php echo e($section->product_limit); ?></td>
                <td class="px-6 py-3 text-center">
                    <form action="<?php echo e(route('admin.home-sections.toggle', $section)); ?>" method="POST">
                        <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                        <button type="submit" class="text-xs px-2.5 py-1 rounded-full font-medium <?php echo e($section->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'); ?>">
                            <?php echo e($section->is_active ? 'Visible' : 'Hidden'); ?>

                        </button>
                    </form>
                </td>
                <td class="px-6 py-3 text-right flex items-center justify-end gap-3">
                    <a href="<?php echo e(route('admin.home-sections.edit', $section)); ?>" class="text-orange-600 text-sm hover:text-orange-800">Edit</a>
                    <form action="<?php echo e(route('admin.home-sections.destroy', $section)); ?>" method="POST" onsubmit="return confirm('Remove this homepage section?')">
                        <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                        <button class="text-red-500 text-sm hover:text-red-700">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr><td colspan="6" class="px-6 py-12 text-center text-gray-400 text-sm">No homepage sections yet — click "Add Section" to create one.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>


<div class="bg-white rounded-2xl shadow-sm p-6 mt-6 max-w-xl">
    <h3 class="font-semibold text-gray-800 mb-1">"Just For You" Section</h3>
    <p class="text-xs text-gray-400 mb-4">The extra product grid that appears below your sections above, with a button linking to the full shop.</p>
    <form action="<?php echo e(route('admin.home-sections.just-for-you')); ?>" method="POST" class="space-y-4">
        <?php echo csrf_field(); ?>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Section Heading</label>
            <input type="text" name="just_for_you_title" maxlength="60" value="<?php echo e(setting('just_for_you_title', 'Just For You')); ?>"
                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Button Text</label>
            <input type="text" name="just_for_you_button_text" maxlength="60" value="<?php echo e(setting('just_for_you_button_text', 'View More Products')); ?>"
                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
        </div>
        <button type="submit" class="bg-orange-600 text-white px-5 py-2.5 rounded-xl text-sm font-semibold hover:bg-orange-700 transition">Save</button>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\completeEcom\resources\views/admin/home-sections/index.blade.php ENDPATH**/ ?>