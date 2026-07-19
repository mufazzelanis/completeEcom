<?php $__env->startSection('settings-title', 'SEO Settings'); ?>

<?php $__env->startSection('settings-content'); ?>
<form method="POST" action="<?php echo e(route('admin.settings.update', 'seo')); ?>">
<?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Default Meta Tags</h2>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Default Meta Title</label>
        <input type="text" name="seo_meta_title" value="<?php echo e(setting('seo_meta_title', setting('site_name','ShopVista') . ' – Online Store')); ?>"
               maxlength="70"
               class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
        <p class="text-xs text-gray-400 mt-1">Recommended: 50–60 characters</p>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Default Meta Description</label>
        <textarea name="seo_meta_description" rows="3" maxlength="160"
                  class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500"
                  placeholder="Shop the best products online..."><?php echo e(setting('seo_meta_description', '')); ?></textarea>
        <p class="text-xs text-gray-400 mt-1">Recommended: 150–160 characters</p>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Meta Keywords</label>
        <input type="text" name="seo_keywords" value="<?php echo e(setting('seo_keywords', '')); ?>"
               class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500"
               placeholder="online shop, buy online, bangladesh, ...">
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Analytics & Tracking</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Google Analytics ID</label>
            <input type="text" name="google_analytics_id" value="<?php echo e(setting('google_analytics_id', '')); ?>"
                   class="w-full border rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-orange-500"
                   placeholder="G-XXXXXXXXXX or UA-XXXXXXXXX-X">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Google Tag Manager ID</label>
            <input type="text" name="google_tag_manager_id" value="<?php echo e(setting('google_tag_manager_id', '')); ?>"
                   class="w-full border rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-orange-500"
                   placeholder="GTM-XXXXXXX">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Facebook Pixel ID</label>
            <input type="text" name="facebook_pixel_id" value="<?php echo e(setting('facebook_pixel_id', '')); ?>"
                   class="w-full border rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-orange-500"
                   placeholder="123456789012345">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Google Site Verification</label>
            <input type="text" name="google_site_verification" value="<?php echo e(setting('google_site_verification', '')); ?>"
                   class="w-full border rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-orange-500"
                   placeholder="verification meta content">
        </div>
    </div>
    <label class="flex items-center gap-2 cursor-pointer">
        <input type="hidden" name="sitemap_enabled" value="0">
        <input type="checkbox" name="sitemap_enabled" value="1" class="rounded text-orange-600"
               <?php if(setting('sitemap_enabled','1') == '1'): echo 'checked'; endif; ?>>
        <span class="text-sm text-gray-700">Enable Sitemap (/sitemap.xml)</span>
    </label>
</div>

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Open Graph / Social Share</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">OG Site Name</label>
            <input type="text" name="og_site_name" value="<?php echo e(setting('og_site_name', setting('site_name','ShopVista'))); ?>"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">OG Twitter Username</label>
            <input type="text" name="og_twitter_user" value="<?php echo e(setting('og_twitter_user', '')); ?>"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500"
                   placeholder="@yourstore">
        </div>
    </div>
</div>

<div class="flex justify-end">
    <button type="submit" class="px-6 py-2 bg-orange-600 text-white rounded-lg text-sm font-semibold hover:bg-orange-700 transition">Save SEO Settings</button>
</div>
</form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.settings.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\completeEcom\resources\views/admin/settings/seo.blade.php ENDPATH**/ ?>