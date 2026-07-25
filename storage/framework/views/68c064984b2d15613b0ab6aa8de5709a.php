<?php $__env->startSection('title', 'Audit Logs'); ?>

<?php $__env->startSection('content'); ?>
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold text-gray-800">Audit Logs</h1>
        <p class="text-sm text-gray-500 mt-0.5">Track all admin actions across the system</p>
    </div>
    <span class="text-sm text-gray-400"><?php echo e($logs->total()); ?> total entries</span>
</div>


<div class="bg-white rounded-2xl shadow-sm p-4 mb-6">
    <form action="<?php echo e(route('admin.audit-logs.index')); ?>" method="GET" class="flex flex-wrap items-end gap-3">
        <div class="flex-1 min-w-40">
            <label class="text-xs font-medium text-gray-500 uppercase mb-1 block">Search</label>
            <input type="text" name="search" value="<?php echo e(request('search')); ?>" placeholder="Search description..."
                class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div>
            <label class="text-xs font-medium text-gray-500 uppercase mb-1 block">Action</label>
            <select name="action" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">All Actions</option>
                <?php $__currentLoopData = $actions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($a); ?>" <?php echo e(request('action') === $a ? 'selected' : ''); ?>><?php echo e($a); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        <div>
            <label class="text-xs font-medium text-gray-500 uppercase mb-1 block">Admin</label>
            <select name="user_id" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">All Admins</option>
                <?php $__currentLoopData = $logUsers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($u->id); ?>" <?php echo e(request('user_id') == $u->id ? 'selected' : ''); ?>><?php echo e($u->name); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        <div>
            <label class="text-xs font-medium text-gray-500 uppercase mb-1 block">From</label>
            <input type="date" name="from" value="<?php echo e(request('from')); ?>"
                class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div>
            <label class="text-xs font-medium text-gray-500 uppercase mb-1 block">To</label>
            <input type="date" name="to" value="<?php echo e(request('to')); ?>"
                class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div class="flex gap-2">
            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm hover:bg-indigo-700 transition">Filter</button>
            <a href="<?php echo e(route('admin.audit-logs.index')); ?>" class="bg-gray-100 text-gray-600 px-4 py-2 rounded-xl text-sm hover:bg-gray-200 transition">Clear</a>
        </div>
    </form>
</div>


<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr class="text-xs text-gray-500 uppercase tracking-wider">
                <th class="px-5 py-3 text-left">Time</th>
                <th class="px-5 py-3 text-left">Admin</th>
                <th class="px-5 py-3 text-left">Action</th>
                <th class="px-5 py-3 text-left">Description</th>
                <th class="px-5 py-3 text-left">Model</th>
                <th class="px-5 py-3 text-left">Changes</th>
                <th class="px-5 py-3 text-left">IP</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50" x-data="{}">
            <?php $__empty_1 = true; $__currentLoopData = $logs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr class="hover:bg-gray-50 transition" x-data="{ open: false }">
                <td class="px-5 py-3 whitespace-nowrap">
                    <p class="font-medium text-gray-800 text-xs"><?php echo e($log->created_at->format('M d, Y')); ?></p>
                    <p class="text-gray-400 text-xs"><?php echo e($log->created_at->format('H:i:s')); ?></p>
                </td>
                <td class="px-5 py-3">
                    <?php if($log->user): ?>
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 bg-indigo-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <span class="text-indigo-600 text-xs font-bold"><?php echo e(strtoupper(substr($log->user->name, 0, 1))); ?></span>
                        </div>
                        <span class="text-gray-700 text-xs font-medium"><?php echo e($log->user->name); ?></span>
                    </div>
                    <?php else: ?>
                    <span class="text-gray-400 text-xs italic">System</span>
                    <?php endif; ?>
                </td>
                <td class="px-5 py-3">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?php echo e($log->action_color); ?>">
                        <?php echo e($log->action); ?>

                    </span>
                </td>
                <td class="px-5 py-3 text-gray-700 max-w-xs">
                    <p class="truncate text-xs"><?php echo e($log->description); ?></p>
                </td>
                <td class="px-5 py-3 text-xs text-gray-500">
                    <?php if($log->model_type): ?>
                    <p class="text-gray-600"><?php echo e(class_basename($log->model_type)); ?></p>
                    <?php if($log->model_id): ?><p class="text-gray-400">#<?php echo e($log->model_id); ?></p><?php endif; ?>
                    <?php else: ?>
                    <span class="text-gray-300">—</span>
                    <?php endif; ?>
                </td>
                <td class="px-5 py-3">
                    <?php if($log->old_values || $log->new_values): ?>
                    <button @click="open = !open" class="text-xs text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 transition-transform" :class="open ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        View diff
                    </button>
                    <div x-show="open" x-cloak x-transition class="mt-2 text-xs space-y-1 bg-gray-50 rounded-lg p-3 min-w-48">
                        <?php if($log->old_values): ?>
                        <div class="text-red-600 font-medium mb-1">Before:</div>
                        <?php $__currentLoopData = $log->old_values; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="flex gap-2">
                            <span class="text-gray-400 font-mono"><?php echo e($k); ?>:</span>
                            <span class="text-red-700 font-mono line-through"><?php echo e(is_array($v) ? json_encode($v) : $v); ?></span>
                        </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <?php endif; ?>
                        <?php if($log->new_values): ?>
                        <div class="text-green-600 font-medium mt-2 mb-1">After:</div>
                        <?php $__currentLoopData = $log->new_values; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="flex gap-2">
                            <span class="text-gray-400 font-mono"><?php echo e($k); ?>:</span>
                            <span class="text-green-700 font-mono"><?php echo e(is_array($v) ? json_encode($v) : $v); ?></span>
                        </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <span class="text-gray-300 text-xs">—</span>
                    <?php endif; ?>
                </td>
                <td class="px-5 py-3 text-xs text-gray-400 font-mono"><?php echo e($log->ip_address); ?></td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
                <td colspan="7" class="px-5 py-16 text-center">
                    <svg class="w-12 h-12 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    <p class="text-gray-400">No audit log entries found.</p>
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <div class="px-5 py-4 border-t border-gray-100"><?php echo e($logs->links()); ?></div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\completeEcom\resources\views/admin/audit-logs/index.blade.php ENDPATH**/ ?>