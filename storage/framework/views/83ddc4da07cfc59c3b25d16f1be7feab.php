<?php $__env->startSection('settings-title', 'Theme & Design'); ?>

<?php $__env->startSection('settings-content'); ?>
<form method="POST" action="<?php echo e(route('admin.settings.update', 'theme')); ?>">
<?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Color Scheme</h2>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <?php
        $colors = [
            'primary_color'    => ['label' => 'Primary Color',    'default' => '#6366f1'],
            'secondary_color'  => ['label' => 'Secondary Color',  'default' => '#ec4899'],
            'accent_color'     => ['label' => 'Accent Color',     'default' => '#f59e0b'],
            'text_color'       => ['label' => 'Body Text Color',  'default' => '#111827'],
        ];
        ?>
        <?php $__currentLoopData = $colors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $meta): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1"><?php echo e($meta['label']); ?></label>
            <div class="flex items-center gap-2">
                <input type="color" name="<?php echo e($key); ?>" value="<?php echo e(setting($key, $meta['default'])); ?>"
                       class="h-9 w-14 rounded border cursor-pointer flex-shrink-0">
                <input type="text" value="<?php echo e(setting($key, $meta['default'])); ?>" readonly
                       class="flex-1 border rounded px-2 py-1.5 text-xs text-gray-600 font-mono">
            </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Typography</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Font Family</label>
            <select name="font_family" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
                <?php $__currentLoopData = [
                    'Inter, sans-serif' => 'Inter',
                    'Roboto, sans-serif' => 'Roboto',
                    'Poppins, sans-serif' => 'Poppins',
                    'Nunito, sans-serif' => 'Nunito',
                    'Open Sans, sans-serif' => 'Open Sans',
                    'Lato, sans-serif' => 'Lato',
                    'system-ui, sans-serif' => 'System Default',
                ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $name): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($value); ?>" <?php if(setting('font_family','Inter, sans-serif')===$value): echo 'selected'; endif; ?>><?php echo e($name); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Button Style</label>
            <select name="button_style" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
                <option value="rounded" <?php if(setting('button_style','rounded')==='rounded'): echo 'selected'; endif; ?>>Rounded (default)</option>
                <option value="square" <?php if(setting('button_style','rounded')==='square'): echo 'selected'; endif; ?>>Square Corners</option>
                <option value="pill" <?php if(setting('button_style','rounded')==='pill'): echo 'selected'; endif; ?>>Pill Shape</option>
            </select>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Custom Code</h2>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Custom CSS
            <span class="text-xs font-normal text-gray-400 ml-1">(injected in &lt;head&gt;)</span>
        </label>
        <textarea name="custom_css" rows="8"
                  class="w-full border rounded-lg px-3 py-2 text-xs font-mono focus:ring-2 focus:ring-orange-500"
                  placeholder="/* Your custom CSS here */
:root {
  --color-primary: #6366f1;
}"><?php echo e(setting('custom_css', '')); ?></textarea>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Custom JavaScript
            <span class="text-xs font-normal text-gray-400 ml-1">(injected before &lt;/body&gt;)</span>
        </label>
        <textarea name="custom_js" rows="8"
                  class="w-full border rounded-lg px-3 py-2 text-xs font-mono focus:ring-2 focus:ring-orange-500"
                  placeholder="// Your custom JS here
console.log('ShopVista loaded');"><?php echo e(setting('custom_js', '')); ?></textarea>
    </div>
</div>

<div class="flex justify-end">
    <button type="submit" class="px-6 py-2 bg-orange-600 text-white rounded-lg text-sm font-semibold hover:bg-orange-700 transition">Save Theme</button>
</div>
</form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.settings.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\completeEcom\resources\views/admin/settings/theme.blade.php ENDPATH**/ ?>