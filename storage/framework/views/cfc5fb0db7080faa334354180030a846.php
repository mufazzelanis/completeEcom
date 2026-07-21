<?php $__env->startSection('title', 'Notification Logs'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Notification Logs</h1>
        <a href="<?php echo e(route('admin.notifications.index')); ?>" class="text-sm text-indigo-600 hover:underline">&larr; Back to Overview</a>
    </div>

    
    <form method="GET" class="bg-white rounded-xl shadow-sm border p-4 flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs text-gray-500 mb-1">Channel</label>
            <select name="channel" class="border rounded-lg px-3 py-2 text-sm">
                <option value="">All Channels</option>
                <?php $__currentLoopData = ['email','sms','push','whatsapp']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($ch); ?>" <?php if(request('channel') === $ch): echo 'selected'; endif; ?>><?php echo e(strtoupper($ch)); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Status</label>
            <select name="status" class="border rounded-lg px-3 py-2 text-sm">
                <option value="">All</option>
                <option value="sent" <?php if(request('status') === 'sent'): echo 'selected'; endif; ?>>Sent</option>
                <option value="failed" <?php if(request('status') === 'failed'): echo 'selected'; endif; ?>>Failed</option>
                <option value="skipped" <?php if(request('status') === 'skipped'): echo 'selected'; endif; ?>>Skipped</option>
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Event</label>
            <select name="event" class="border rounded-lg px-3 py-2 text-sm">
                <option value="">All Events</option>
                <?php $__currentLoopData = array_keys(config('notifications.events', [])); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ev): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($ev); ?>" <?php if(request('event') === $ev): echo 'selected'; endif; ?>><?php echo e(str_replace('_', ' ', $ev)); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">Filter</button>
        <a href="<?php echo e(route('admin.notifications.logs')); ?>" class="px-4 py-2 bg-gray-100 text-gray-600 rounded-lg text-sm hover:bg-gray-200">Reset</a>
    </form>

    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                    <tr>
                        <th class="px-4 py-3 text-left">User</th>
                        <th class="px-4 py-3 text-left">Event</th>
                        <th class="px-4 py-3 text-left">Channel</th>
                        <th class="px-4 py-3 text-left">Recipient</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left">Error</th>
                        <th class="px-4 py-3 text-left">Sent At</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php $__empty_1 = true; $__currentLoopData = $logs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-900"><?php echo e($log->user?->name ?? '—'); ?></td>
                        <td class="px-4 py-3">
                            <span class="inline-block bg-gray-100 text-gray-700 text-xs px-2 py-0.5 rounded"><?php echo e($log->event_type); ?></span>
                        </td>
                        <td class="px-4 py-3">
                            <?php $cc = ['email'=>'blue','sms'=>'green','push'=>'purple','whatsapp'=>'emerald'][$log->channel] ?? 'gray'; ?>
                            <span class="inline-block bg-<?php echo e($cc); ?>-100 text-<?php echo e($cc); ?>-700 text-xs px-2 py-0.5 rounded-full font-medium"><?php echo e(strtoupper($log->channel)); ?></span>
                        </td>
                        <td class="px-4 py-3 text-gray-500 max-w-xs truncate"><?php echo e($log->recipient); ?></td>
                        <td class="px-4 py-3">
                            <span class="inline-block text-xs px-2 py-0.5 rounded-full
                                <?php echo e($log->status === 'sent' ? 'bg-green-100 text-green-700' : ($log->status === 'failed' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600')); ?>">
                                <?php echo e(ucfirst($log->status)); ?>

                            </span>
                        </td>
                        <td class="px-4 py-3 text-red-500 text-xs max-w-xs truncate" title="<?php echo e($log->error); ?>"><?php echo e($log->error ?? '—'); ?></td>
                        <td class="px-4 py-3 text-gray-500 whitespace-nowrap"><?php echo e($log->sent_at?->format('d M Y H:i')); ?></td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-400">No logs found.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if($logs->hasPages()): ?>
        <div class="px-4 py-3 border-t"><?php echo e($logs->withQueryString()->links()); ?></div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\azad-ecom\resources\views/admin/notifications/logs.blade.php ENDPATH**/ ?>