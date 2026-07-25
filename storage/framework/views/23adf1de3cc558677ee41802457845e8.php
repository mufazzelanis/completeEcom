<?php $__env->startSection('title', 'Users'); ?>

<?php $__env->startSection('content'); ?>
<div class="flex items-center justify-between mb-6">
    <form action="<?php echo e(route('admin.users.index')); ?>" method="GET" class="flex items-center space-x-3">
        <input type="text" name="search" value="<?php echo e(request('search')); ?>" placeholder="Search name or email..."
            class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 w-56">
        <select name="role" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">All Roles</option>
            <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($role->name); ?>" <?php echo e(request('role') === $role->name ? 'selected' : ''); ?>><?php echo e($role->display_name); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-xl text-sm hover:bg-gray-700 transition">Search</button>
        <?php if(request('search') || request('role')): ?>
            <a href="<?php echo e(route('admin.users.index')); ?>" class="text-sm text-gray-500 hover:text-gray-700">Clear</a>
        <?php endif; ?>
    </form>
    <a href="<?php echo e(route('admin.users.create')); ?>"
        class="flex items-center space-x-2 bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-indigo-700 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        <span>Create User</span>
    </a>
</div>

<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr class="text-xs text-gray-500 uppercase tracking-wider">
                <th class="px-6 py-3 text-left">User</th>
                <th class="px-6 py-3 text-left">Email</th>
                <th class="px-6 py-3 text-center">Role</th>
                <th class="px-6 py-3 text-center">Orders</th>
                <th class="px-6 py-3 text-center">Points</th>
                <th class="px-6 py-3 text-center">Status</th>
                <th class="px-6 py-3 text-center">Joined</th>
                <th class="px-6 py-3 text-center">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            <?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-9 h-9 bg-indigo-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <span class="text-indigo-600 font-semibold text-sm"><?php echo e(strtoupper(substr($user->name, 0, 1))); ?></span>
                            </div>
                            <span class="font-medium text-gray-800 text-sm"><?php echo e($user->name); ?></span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600"><?php echo e($user->email); ?></td>
                    <td class="px-6 py-4 text-center">
                        <span class="px-2.5 py-1 rounded-full text-xs font-medium <?php echo e(\App\Models\User::roleBadgeClass($user->role)); ?>">
                            <?php echo e(\App\Models\User::roleLabel($user->role)); ?>

                        </span>
                    </td>
                    <td class="px-6 py-4 text-center font-semibold text-gray-800"><?php echo e($user->orders_count); ?></td>
                    <td class="px-6 py-4 text-center font-semibold text-purple-600"><?php echo e(number_format($user->points_balance)); ?></td>
                    <td class="px-6 py-4 text-center">
                        <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo e($user->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'); ?>">
                            <?php echo e($user->is_active ? 'Active' : 'Inactive'); ?>

                        </span>
                    </td>
                    <td class="px-6 py-4 text-center text-xs text-gray-500"><?php echo e($user->created_at->format('M d, Y')); ?></td>
                    <td class="px-6 py-4 text-center">
                        <div class="flex items-center justify-center space-x-2">
                            <a href="<?php echo e(route('admin.users.show', $user->id)); ?>" class="text-indigo-600 hover:text-indigo-800 text-sm">View</a>
                            <a href="<?php echo e(route('admin.users.edit', $user->id)); ?>" class="text-gray-600 hover:text-gray-800 text-sm">Edit</a>
                            <form action="<?php echo e(route('admin.users.destroy', $user->id)); ?>" method="POST" onsubmit="return confirm('Delete this user?')">
                                <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                <button type="submit" class="text-red-500 hover:text-red-700 text-sm">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="8" class="px-6 py-12 text-center text-gray-400">No users found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <div class="px-6 py-4 border-t border-gray-100"><?php echo e($users->links()); ?></div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\completeEcom\resources\views/admin/users/index.blade.php ENDPATH**/ ?>