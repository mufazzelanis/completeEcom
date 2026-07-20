<?php $__env->startSection('title', 'Categories'); ?>

<?php $__env->startSection('content'); ?>
<div class="flex items-center justify-between mb-6">
    <p class="text-sm text-gray-500">Manage product categories and subcategories</p>
    <a href="<?php echo e(route('admin.categories.create')); ?>" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-indigo-700 transition flex items-center space-x-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        <span>Add Category</span>
    </a>
</div>

<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr class="text-xs text-gray-500 uppercase tracking-wider">
                <th class="px-6 py-3 text-left">Name</th>
                <th class="px-6 py-3 text-left">Parent</th>
                <th class="px-6 py-3 text-center">Subcategories</th>
                <th class="px-6 py-3 text-center">Products</th>
                <th class="px-6 py-3 text-center">Status</th>
                <th class="px-6 py-3 text-center">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            <?php $__empty_1 = true; $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr class="hover:bg-gray-50 transition <?php echo e($category->parent_id ? 'bg-gray-50/50' : ''); ?>">
                    <td class="px-6 py-4">
                        <div class="flex items-center space-x-3">
                            <?php if($category->parent_id): ?>
                                <span class="text-gray-300 text-lg leading-none pl-4">└</span>
                            <?php endif; ?>
                            <?php if($category->image): ?>
                                <img src="<?php echo e(Storage::url($category->image)); ?>" class="w-9 h-9 rounded-lg object-cover flex-shrink-0">
                            <?php else: ?>
                                <div class="w-9 h-9 <?php echo e($category->parent_id ? 'bg-purple-100' : 'bg-indigo-100'); ?> rounded-lg flex items-center justify-center flex-shrink-0">
                                    <span class="<?php echo e($category->parent_id ? 'text-purple-600' : 'text-indigo-600'); ?> font-bold text-sm"><?php echo e(strtoupper(substr($category->name, 0, 1))); ?></span>
                                </div>
                            <?php endif; ?>
                            <div>
                                <p class="font-medium text-gray-800 text-sm"><?php echo e($category->name); ?></p>
                                <p class="text-xs text-gray-400"><?php echo e($category->slug); ?></p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        <?php if($category->parent): ?>
                            <span class="bg-indigo-50 text-indigo-600 px-2 py-0.5 rounded-full text-xs"><?php echo e($category->parent->name); ?></span>
                        <?php else: ?>
                            <span class="text-gray-300 text-xs">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="font-semibold text-gray-700 text-sm"><?php echo e($category->children_count); ?></span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="font-semibold text-gray-800"><?php echo e($category->products_count); ?></span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo e($category->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'); ?>">
                            <?php echo e($category->is_active ? 'Active' : 'Inactive'); ?>

                        </span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <div class="flex items-center justify-center space-x-2">
                            <a href="<?php echo e(route('admin.categories.edit', $category)); ?>" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Edit</a>
                            <form action="<?php echo e(route('admin.categories.destroy', $category)); ?>" method="POST"
                                onsubmit="return confirm('Delete \'<?php echo e($category->name); ?>\'? Products in this category may be affected.')">
                                <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                <button type="submit" class="text-red-500 hover:text-red-700 text-sm font-medium">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                        No categories found. <a href="<?php echo e(route('admin.categories.create')); ?>" class="text-indigo-600">Create one</a>.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <div class="px-6 py-4 border-t border-gray-100"><?php echo e($categories->links()); ?></div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\azad-ecom\resources\views/admin/categories/index.blade.php ENDPATH**/ ?>