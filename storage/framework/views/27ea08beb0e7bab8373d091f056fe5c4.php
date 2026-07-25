<?php $__env->startSection('settings-title', 'Footer Settings'); ?>

<?php $__env->startSection('settings-content'); ?>
<form method="POST" action="<?php echo e(route('admin.settings.update', 'footer')); ?>" enctype="multipart/form-data">
<?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Footer Content</h2>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Footer Description</label>
        <textarea name="footer_description" rows="3"
                  class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500"><?php echo e(setting('footer_description', 'Your one-stop shop for everything you need.')); ?></textarea>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Copyright Text</label>
        <input type="text" name="copyright_text" value="<?php echo e(setting('copyright_text', '© {year} ShopVista. All rights reserved.')); ?>"
               class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
        <p class="text-xs text-gray-400 mt-1">Use <code class="bg-gray-100 px-1 rounded">{year}</code> for the current year.</p>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Newsletter Section</h2>
    <label class="flex items-center gap-2 cursor-pointer">
        <input type="hidden" name="newsletter_enabled" value="0">
        <input type="checkbox" name="newsletter_enabled" value="1" class="rounded text-orange-600"
               <?php if(setting('newsletter_enabled','1') == '1'): echo 'checked'; endif; ?>>
        <span class="text-sm text-gray-700">Show Newsletter Signup in Footer</span>
    </label>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Newsletter Heading</label>
        <input type="text" name="newsletter_heading" value="<?php echo e(setting('newsletter_heading', 'Subscribe to our newsletter')); ?>"
               class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Newsletter Sub-text</label>
        <input type="text" name="newsletter_subtext" value="<?php echo e(setting('newsletter_subtext', 'Get the latest deals and updates.')); ?>"
               class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Footer Column Titles</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Column 2 Title</label>
            <input type="text" name="footer_col2_title" value="<?php echo e(setting('footer_col2_title', 'Quick Links')); ?>"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Column 3 Title</label>
            <input type="text" name="footer_col3_title" value="<?php echo e(setting('footer_col3_title', 'Customer Service')); ?>"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Column 4 Title</label>
            <input type="text" name="footer_col4_title" value="<?php echo e(setting('footer_col4_title', 'Contact')); ?>"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Payment Icons ("We Accept")</h2>
    <p class="text-xs text-gray-400">Upload small payment gateway logos (Visa, bKash, Nagad, etc.) shown in the footer "We Accept" section. Recommended: PNG with transparent background, ~80×30px.</p>

    <?php $iconSlots = [
        'payment_icon_1' => 'Icon 1 (e.g. Visa)',
        'payment_icon_2' => 'Icon 2 (e.g. Mastercard)',
        'payment_icon_3' => 'Icon 3 (e.g. bKash)',
        'payment_icon_4' => 'Icon 4 (e.g. Nagad)',
        'payment_icon_5' => 'Icon 5 (e.g. Rocket)',
        'payment_icon_6' => 'Icon 6 (e.g. COD)',
        'payment_icon_7' => 'Icon 7',
        'payment_icon_8' => 'Icon 8',
    ]; ?>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <?php $__currentLoopData = $iconSlots; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php $current = setting($key); ?>
            <div class="flex items-center gap-3 border rounded-lg px-3 py-2 bg-gray-50/50">
                <div class="w-14 h-8 flex items-center justify-center bg-white border rounded overflow-hidden flex-shrink-0">
                    <?php if($current): ?>
                        <img src="<?php echo e(setting_file_url($key)); ?>" class="max-w-full max-h-full object-contain">
                    <?php else: ?>
                        <span class="text-[10px] text-gray-300">No image</span>
                    <?php endif; ?>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs text-gray-500 mb-1"><?php echo e($label); ?></p>
                    <div class="flex items-center gap-2">
                        <input type="file" name="<?php echo e($key); ?>" accept="image/*"
                               class="text-xs border rounded px-2 py-1 w-full">
                        <?php if($current): ?>
                            <label class="flex items-center gap-1 text-xs text-red-500 cursor-pointer whitespace-nowrap">
                                <input type="checkbox" name="delete_<?php echo e($key); ?>" value="1" class="rounded text-red-500">
                                Remove
                            </label>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</div>

<div class="flex justify-end">
    <button type="submit" class="px-6 py-2 bg-orange-600 text-white rounded-lg text-sm font-semibold hover:bg-orange-700 transition">Save Footer</button>
</div>
</form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.settings.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\completeEcom\resources\views/admin/settings/footer.blade.php ENDPATH**/ ?>