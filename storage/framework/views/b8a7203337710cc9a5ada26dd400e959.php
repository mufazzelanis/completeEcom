<?php $__env->startSection('settings-title', 'Order Settings'); ?>

<?php $__env->startSection('settings-content'); ?>
<form method="POST" action="<?php echo e(route('admin.settings.update', 'orders')); ?>">
<?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Order Configuration</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Order Number Prefix</label>
            <input type="text" name="order_prefix" value="<?php echo e(setting('order_prefix', 'ORD-')); ?>"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500"
                   placeholder="ORD-">
            <p class="text-xs text-gray-400 mt-1">Applied to all new orders. Example: ORD-2026-001</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Minimum Order Amount</label>
            <div class="relative">
                <span class="absolute left-3 top-2 text-gray-500 text-sm"><?php echo e(setting('currency_symbol','৳')); ?></span>
                <input type="number" name="min_order_amount" value="<?php echo e(setting('min_order_amount', '0')); ?>"
                       min="0" step="1"
                       class="w-full border rounded-lg pl-8 pr-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
            </div>
        </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2">
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="hidden" name="auto_confirm_orders" value="0">
            <input type="checkbox" name="auto_confirm_orders" value="1" class="rounded text-orange-600"
                   <?php if(setting('auto_confirm_orders','0') == '1'): echo 'checked'; endif; ?>>
            <span class="text-sm text-gray-700">Auto-Confirm Orders</span>
        </label>
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="hidden" name="auto_invoice" value="0">
            <input type="checkbox" name="auto_invoice" value="1" class="rounded text-orange-600"
                   <?php if(setting('auto_invoice','0') == '1'): echo 'checked'; endif; ?>>
            <span class="text-sm text-gray-700">Auto-Generate Invoice</span>
        </label>
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="hidden" name="guest_checkout" value="0">
            <input type="checkbox" name="guest_checkout" value="1" class="rounded text-orange-600"
                   <?php if(setting('guest_checkout','0') == '1'): echo 'checked'; endif; ?>>
            <span class="text-sm text-gray-700">Allow Guest Checkout</span>
        </label>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Policies</h2>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Return Policy</label>
        <textarea name="return_policy" rows="5"
                  class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500"
                  placeholder="Describe your return and refund policy..."><?php echo e(setting('return_policy', '')); ?></textarea>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Cancellation Policy</label>
        <textarea name="cancellation_policy" rows="4"
                  class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500"
                  placeholder="Describe your order cancellation policy..."><?php echo e(setting('cancellation_policy', '')); ?></textarea>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Privacy Policy (URL)</label>
        <input type="url" name="privacy_policy_url" value="<?php echo e(setting('privacy_policy_url', '')); ?>"
               class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500"
               placeholder="https://yoursite.com/privacy">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Terms & Conditions (URL)</label>
        <input type="url" name="terms_url" value="<?php echo e(setting('terms_url', '')); ?>"
               class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500"
               placeholder="https://yoursite.com/terms">
    </div>
</div>

<div class="flex justify-end">
    <button type="submit" class="px-6 py-2 bg-orange-600 text-white rounded-lg text-sm font-semibold hover:bg-orange-700 transition">Save Order Settings</button>
</div>
</form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.settings.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\completeEcom\resources\views/admin/settings/orders.blade.php ENDPATH**/ ?>