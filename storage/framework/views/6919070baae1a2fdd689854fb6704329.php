<?php $__env->startSection('title', 'Bulk Product Upload'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-3xl">
    <a href="<?php echo e(route('admin.products.index')); ?>" class="text-indigo-600 hover:text-indigo-700 text-sm flex items-center space-x-2 mb-6">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        <span>Back to Products</span>
    </a>

    <?php if(session('success')): ?><div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm"><?php echo e(session('success')); ?></div><?php endif; ?>
    <?php if(session('warning')): ?>
    <div class="mb-4 bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-xl text-sm">
        <p class="font-medium"><?php echo e(session('warning')); ?></p>
        <?php if(session('import_errors')): ?>
        <ul class="mt-2 space-y-1 text-xs">
            <?php $__currentLoopData = session('import_errors'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $err): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li>• <?php echo e($err); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    
    <div class="bg-blue-50 border border-blue-200 rounded-2xl p-6 mb-6">
        <h3 class="font-semibold text-blue-800 mb-3">How to use bulk upload</h3>
        <ol class="text-sm text-blue-700 space-y-1 list-decimal list-inside">
            <li>Download the CSV template below</li>
            <li>Fill in product data — one row per product</li>
            <li>The <strong>category_name</strong> must exactly match an existing top-level category</li>
            <li>Leave <strong>sale_price</strong> blank if there's no discount</li>
            <li>Upload the completed CSV file</li>
        </ol>

        <div class="mt-4 flex flex-wrap gap-3">
            <a href="<?php echo e(route('admin.products.bulk-upload.template')); ?>"
                class="inline-flex items-center space-x-2 px-4 py-2 bg-blue-600 text-white rounded-xl text-sm font-medium hover:bg-blue-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                <span>Download CSV Template</span>
            </a>
        </div>
    </div>

    
    <div class="bg-white rounded-2xl shadow-sm p-6 mb-6">
        <h3 class="font-semibold text-gray-800 mb-3">Column Reference</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs text-gray-500 uppercase border-b border-gray-100">
                        <th class="pb-2 text-left font-medium">Column</th>
                        <th class="pb-2 text-left font-medium">Required</th>
                        <th class="pb-2 text-left font-medium">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php $__currentLoopData = [
                        ['name', 'Yes', 'Product name'],
                        ['sku', 'No', 'Stock keeping unit'],
                        ['category_name', 'Yes', 'Must match existing category exactly'],
                        ['price', 'Yes', 'Base price (numeric)'],
                        ['sale_price', 'No', 'Leave blank for no discount'],
                        ['stock', 'No', 'Default 0 if blank'],
                        ['short_description', 'No', 'Brief summary'],
                        ['description', 'No', 'Full description'],
                        ['is_active', 'No', '1 = active, 0 = inactive (default 1)'],
                        ['is_featured', 'No', '1 = featured (default 0)'],
                    ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$col, $req, $note]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td class="py-2 font-mono text-indigo-600 text-xs"><?php echo e($col); ?></td>
                        <td class="py-2">
                            <?php if($req === 'Yes'): ?>
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700">Required</span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-500">Optional</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-2 text-xs text-gray-500"><?php echo e($note); ?></td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            <p class="text-xs text-gray-500 font-medium mb-2">Available Categories:</p>
            <div class="flex flex-wrap gap-2">
                <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <span class="px-2.5 py-1 bg-gray-100 text-gray-700 rounded-lg text-xs font-mono"><?php echo e($cat->name); ?></span>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    </div>

    
    <div class="bg-white rounded-2xl shadow-sm p-8">
        <h3 class="font-semibold text-gray-800 mb-4">Upload CSV File</h3>
        <?php if($errors->any()): ?>
        <div class="mb-5 bg-red-50 border border-red-200 rounded-xl p-4">
            <ul class="text-sm text-red-600 space-y-1"><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li>• <?php echo e($e); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ul>
        </div>
        <?php endif; ?>
        <form action="<?php echo e(route('admin.products.bulk-upload.import')); ?>" method="POST" enctype="multipart/form-data">
            <?php echo csrf_field(); ?>
            <div class="border-2 border-dashed border-gray-200 rounded-xl p-8 text-center hover:border-indigo-300 transition">
                <svg class="w-10 h-10 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                <p class="text-sm text-gray-500 mb-3">Select a CSV file to import products</p>
                <input type="file" name="csv_file" accept=".csv,.txt" required
                    class="block mx-auto text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
                <p class="text-xs text-gray-400 mt-2">Max file size: 500MB. CSV format only.</p>
            </div>
            <div class="flex justify-end mt-6">
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-medium hover:bg-indigo-700 transition flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    <span>Import Products</span>
                </button>
            </div>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\azad-ecom\resources\views/admin/products/bulk_upload.blade.php ENDPATH**/ ?>