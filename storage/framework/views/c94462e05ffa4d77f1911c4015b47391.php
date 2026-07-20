<?php $__env->startSection('title', 'Edit Category'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-2xl">
    <a href="<?php echo e(route('admin.categories.index')); ?>" class="text-indigo-600 hover:text-indigo-700 text-sm flex items-center space-x-2 mb-6">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        <span>Back to Categories</span>
    </a>

    <div class="bg-white rounded-2xl shadow-sm p-8">
        <form action="<?php echo e(route('admin.categories.update', $category)); ?>" method="POST" enctype="multipart/form-data">
            <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
            <div class="space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                    <input type="text" name="name" value="<?php echo e(old('name', $category->name)); ?>"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-400 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                    <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Slug</label>
                    <input type="text" name="slug" value="<?php echo e(old('slug', $category->slug)); ?>"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" rows="3"
                        class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"><?php echo e(old('description', $category->description)); ?></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category Image</label>
                    <?php if($category->image): ?>
                        <div class="mb-2">
                            <img src="<?php echo e(Storage::url($category->image)); ?>" class="w-20 h-20 object-cover rounded-xl">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="image" accept="image/*"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div class="flex items-center space-x-3">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" <?php echo e($category->is_active ? 'checked' : ''); ?> class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                    </label>
                    <span class="text-sm font-medium text-gray-700">Active</span>
                </div>

                <div class="flex justify-end space-x-3 pt-4">
                    <a href="<?php echo e(route('admin.categories.index')); ?>" class="px-6 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50 transition">Cancel</a>
                    <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-medium hover:bg-indigo-700 transition">Update Category</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\azad-ecom\resources\views/admin/categories/edit.blade.php ENDPATH**/ ?>