<?php $__env->startSection('settings-title', 'Branding'); ?>

<?php $__env->startSection('settings-content'); ?>
<form method="POST" action="<?php echo e(route('admin.settings.update', 'branding')); ?>" enctype="multipart/form-data"
      x-data="{
          primary: '<?php echo e(setting('primary_color', '#ea580c')); ?>',
          accent: '<?php echo e(setting('accent_color', '#dc2626')); ?>',
      }">
<?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>


<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-3 border-b">Brand Identity</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Brand Name / Site Name</label>
            <input type="text" name="site_name" value="<?php echo e(setting('site_name', 'ShopVista')); ?>"
                   class="w-full border rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                   placeholder="Your Brand Name">
            <p class="text-xs text-gray-400 mt-1">Displayed in header, footer, browser tab, and emails.</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tagline</label>
            <input type="text" name="site_tagline" value="<?php echo e(setting('site_tagline', '')); ?>"
                   class="w-full border rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                   placeholder="Your one-stop shop">
            <p class="text-xs text-gray-400 mt-1">Short slogan shown below or near the brand name.</p>
        </div>
    </div>
</div>


<?php
$logos = [
    'site_logo'    => ['label' => 'Main Logo',        'desc' => 'Header logo (200×60 px)',       'h' => 'h-12', 'max' => 'max-w-[140px]'],
    'favicon'      => ['label' => 'Favicon',           'desc' => 'Browser tab icon (32×32 px)',   'h' => 'h-8',  'max' => 'max-w-[32px]'],
    'footer_logo'  => ['label' => 'Footer Logo',       'desc' => 'Footer logo (160×50 px)',       'h' => 'h-10', 'max' => 'max-w-[120px]'],
    'login_logo'   => ['label' => 'Login Page Logo',   'desc' => 'Login/register page (160×50 px)','h' => 'h-10', 'max' => 'max-w-[120px]'],
    'email_logo'   => ['label' => 'Email Logo',        'desc' => 'Email templates (180×55 px)',   'h' => 'h-10', 'max' => 'max-w-[120px]'],
    'invoice_logo' => ['label' => 'Invoice Logo',      'desc' => 'PDF invoices (200×60 px)',      'h' => 'h-12', 'max' => 'max-w-[140px]'],
];
?>

<div class="bg-white rounded-xl shadow-sm border p-6">
    <h2 class="text-base font-semibold text-gray-900 pb-3 border-b mb-5">Logos</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        <?php $__currentLoopData = $logos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $meta): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php $currentUrl = setting_file_url($key); ?>
        <div class="border rounded-xl p-4 space-y-3">
            <div>
                <p class="text-sm font-medium text-gray-800"><?php echo e($meta['label']); ?></p>
                <p class="text-xs text-gray-400 mt-0.5"><?php echo e($meta['desc']); ?></p>
            </div>
            <?php if($currentUrl): ?>
            <div class="flex items-center gap-3">
                <img src="<?php echo e($currentUrl); ?>" alt="<?php echo e($meta['label']); ?>"
                     class="<?php echo e($meta['h']); ?> <?php echo e($meta['max']); ?> object-contain rounded border bg-gray-50 p-1">
                <span class="text-xs text-green-600 font-medium">Uploaded</span>
            </div>
            <?php else: ?>
            <div class="<?php echo e($meta['h']); ?> flex items-center justify-center rounded border border-dashed border-gray-200 bg-gray-50">
                <span class="text-xs text-gray-400">No logo uploaded</span>
            </div>
            <?php endif; ?>
            <div>
                <input type="file" name="<?php echo e($key); ?>" accept="image/*"
                       class="block w-full text-xs text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100">
                <?php if($currentUrl): ?>
                <label class="inline-flex items-center gap-1.5 mt-2">
                    <input type="checkbox" name="delete_<?php echo e($key); ?>" value="1" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                    <span class="text-xs text-red-500">Remove logo</span>
                </label>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</div>


<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-3 border-b">Brand Colors</h2>
    <p class="text-xs text-gray-400 -mt-2">Primary re-themes the storefront's main buttons, links, and highlights. Secondary and Accent re-theme their respective secondary/highlight elements (badges, sale tags, etc.). Text re-themes the main body/heading text color. All four apply site-wide on the customer-facing storefront.</p>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-5">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Primary Color</label>
            <div class="flex items-center gap-2">
                <input type="color" name="primary_color" x-model="primary"
                       class="h-9 w-16 rounded border cursor-pointer">
                <input type="text" x-model="primary"
                       class="flex-1 border rounded px-2 py-1.5 text-xs text-gray-600 font-mono bg-gray-50">
            </div>
            <p class="text-xs text-gray-400 mt-1">Re-themes every button, link, and highlight across the storefront.</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Secondary Color</label>
            <div class="flex items-center gap-2">
                <input type="color" name="secondary_color" value="<?php echo e(setting('secondary_color', '#f97316')); ?>"
                       class="h-9 w-16 rounded border cursor-pointer">
                <input type="text" value="<?php echo e(setting('secondary_color', '#f97316')); ?>" readonly
                       class="flex-1 border rounded px-2 py-1.5 text-xs text-gray-600 font-mono bg-gray-50">
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Accent Color</label>
            <div class="flex items-center gap-2">
                <input type="color" name="accent_color" x-model="accent"
                       class="h-9 w-16 rounded border cursor-pointer">
                <input type="text" x-model="accent"
                       class="flex-1 border rounded px-2 py-1.5 text-xs text-gray-600 font-mono bg-gray-50">
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Text Color</label>
            <div class="flex items-center gap-2">
                <input type="color" name="text_color" value="<?php echo e(setting('text_color', '#1f2937')); ?>"
                       class="h-9 w-16 rounded border cursor-pointer">
                <input type="text" value="<?php echo e(setting('text_color', '#1f2937')); ?>" readonly
                       class="flex-1 border rounded px-2 py-1.5 text-xs text-gray-600 font-mono bg-gray-50">
            </div>
        </div>
    </div>

    
    <div class="border-t pt-4 mt-2">
        <p class="text-xs font-medium text-gray-500 mb-2">Preview:</p>
        <div class="rounded-xl border p-4 bg-gray-50">
            <div class="flex items-center gap-3 mb-3">
                <?php if(setting_file_url('site_logo')): ?>
                    <img src="<?php echo e(setting_file_url('site_logo')); ?>" class="h-8 object-contain">
                <?php else: ?>
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white font-bold text-sm"
                         :style="{ backgroundColor: primary }">
                        <?php echo e(strtoupper(substr(setting('site_name', 'S'), 0, 1))); ?>

                    </div>
                <?php endif; ?>
                <span class="font-bold text-lg" :style="{ color: primary }">
                    <?php echo e(setting('site_name', 'ShopVista')); ?>

                </span>
            </div>
            <div class="flex gap-2">
                <button type="button" class="px-4 py-1.5 rounded-lg text-white text-xs font-semibold"
                        :style="{ backgroundColor: primary }">
                    Primary Button
                </button>
                <button type="button" class="px-4 py-1.5 rounded-lg text-white text-xs font-semibold"
                        :style="{ backgroundColor: accent }">
                    Accent Button
                </button>
            </div>
        </div>
    </div>
</div>

<div class="flex justify-end">
    <button type="submit" class="px-6 py-2.5 bg-orange-600 text-white rounded-lg text-sm font-semibold hover:bg-orange-700 transition">Save Branding</button>
</div>
</form>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('admin.settings.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\azad-ecom\resources\views/admin/settings/branding.blade.php ENDPATH**/ ?>