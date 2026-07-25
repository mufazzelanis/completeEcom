<?php $__env->startSection('settings-title', 'Page Settings'); ?>

<?php $__env->startSection('settings-content'); ?>
<form method="POST" action="<?php echo e(route('admin.settings.update', 'pages')); ?>">
<?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>

<?php
use App\Models\Page;
$pages = [];
try { $pages = Page::orderBy('title')->get(['id', 'title']); } catch (\Throwable) {}
?>

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Page Assignments</h2>
    <p class="text-sm text-gray-500">
        Pick which CMS page fills each built-in slot. Once assigned, that page shows up automatically at its dedicated URL
        (<span class="font-mono text-xs">/terms</span>, <span class="font-mono text-xs">/privacy</span>, <span class="font-mono text-xs">/about</span>)
        and in the storefront footer — no matter what slug you gave the page itself. FAQ and Contact already have working pages
        by default (via the <span class="font-mono text-xs">faq</span>/<span class="font-mono text-xs">contact</span> slugs);
        assigning here only matters if you want a *different* page to serve that role.
        Create pages first in <a href="<?php echo e(route('admin.pages.index')); ?>" class="text-orange-600 hover:underline">CMS → Pages</a>.
    </p>
    <?php
    $slots = [
        'terms_page_id'  => 'Terms & Conditions Page',
        'privacy_page_id'=> 'Privacy Policy Page',
        'about_page_id'  => 'About Us Page',
        'contact_page_id'=> 'Contact Page',
        'faq_page_id'    => 'FAQ Page',
    ];
    ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <?php $__currentLoopData = $slots; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1"><?php echo e($label); ?></label>
            <select name="<?php echo e($key); ?>" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
                <option value="">— None —</option>
                <?php $__currentLoopData = $pages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $page): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($page->id); ?>" <?php if((int)setting($key,0) === $page->id): echo 'selected'; endif; ?>><?php echo e($page->title); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Navigation URLs</h2>
    <p class="text-sm text-gray-500">Override where the footer's Shop/Blog/Contact/About links point — useful if you want them to open an external URL instead of the built-in page. Leave blank to use the normal built-in page.</p>
    <?php
    $navItems = [
        'nav_shop_url'    => 'Shop URL',
        'nav_blog_url'    => 'Blog URL',
        'nav_contact_url' => 'Contact URL',
        'nav_about_url'   => 'About Us URL',
    ];
    ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <?php $__currentLoopData = $navItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1"><?php echo e($label); ?></label>
            <input type="text" name="<?php echo e($key); ?>" value="<?php echo e(setting($key, '')); ?>"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500"
                   placeholder="/<?php echo e(str_replace(['nav_','_url'],['','/'],rtrim($key,'_url'))); ?>">
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</div>

<div class="flex justify-end">
    <button type="submit" class="px-6 py-2 bg-orange-600 text-white rounded-lg text-sm font-semibold hover:bg-orange-700 transition">Save Page Settings</button>
</div>
</form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.settings.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\completeEcom\resources\views/admin/settings/pages.blade.php ENDPATH**/ ?>