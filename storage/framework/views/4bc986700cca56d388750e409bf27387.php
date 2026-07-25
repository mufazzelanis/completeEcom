<?php $__env->startSection('title', 'My Profile'); ?>
<?php $__env->startSection('pageTitle', 'My Profile'); ?>

<?php $__env->startSection('content'); ?>
<h1 class="text-xl font-bold text-gray-800 mb-5">My Profile</h1>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    
    <div class="bg-white rounded-2xl shadow-sm p-6 text-center">
        <div x-data="{ preview: null }" class="mb-4">
            <img :src="preview || '<?php echo e($user->avatar_url); ?>'" class="w-24 h-24 rounded-full object-cover mx-auto mb-3 border-4 border-indigo-100" id="avatar-preview">
            <p class="font-semibold text-gray-800"><?php echo e($user->name); ?></p>
            <p class="text-sm text-gray-400"><?php echo e($user->email); ?></p>
            <?php if($user->created_at): ?>
            <p class="text-xs text-gray-400 mt-1">Member since <?php echo e($user->created_at->format('M Y')); ?></p>
            <?php endif; ?>
        </div>
        <label class="cursor-pointer inline-flex items-center gap-2 bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition text-sm font-medium px-4 py-2 rounded-xl">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            Upload Photo
            <input type="file" name="avatar" form="profile-form" accept="image/*" class="sr-only"
                onchange="const reader = new FileReader(); reader.onload = e => document.getElementById('avatar-preview').src = e.target.result; reader.readAsDataURL(this.files[0])">
        </label>
        <p class="text-xs text-gray-400 mt-2">JPG, PNG up to 2MB</p>
    </div>

    
    <div class="lg:col-span-2 space-y-4">
        <form id="profile-form" action="<?php echo e(route('account.profile.update')); ?>" method="POST" enctype="multipart/form-data" class="bg-white rounded-2xl shadow-sm p-6 space-y-4">
            <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
            <h2 class="font-semibold text-gray-800">Personal Information</h2>

            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2 sm:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="<?php echo e(old('name', $user->name)); ?>" required
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="col-span-2 sm:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                    <input type="text" name="phone" value="<?php echo e(old('phone', $user->phone)); ?>"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth</label>
                    <input type="date" name="date_of_birth" value="<?php echo e(old('date_of_birth', $user->date_of_birth?->format('Y-m-d'))); ?>"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                    <select name="gender" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">Prefer not to say</option>
                        <?php $__currentLoopData = ['male' => 'Male', 'female' => 'Female', 'other' => 'Other']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $val => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($val); ?>" <?php echo e(old('gender', $user->gender) === $val ? 'selected' : ''); ?>><?php echo e($label); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Bio</label>
                    <textarea name="bio" rows="3" maxlength="500" placeholder="Tell us a little about yourself..."
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none"><?php echo e(old('bio', $user->bio)); ?></textarea>
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2.5 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition">Save Changes</button>
            </div>
        </form>

        
        <form action="<?php echo e(route('account.password.update')); ?>" method="POST" class="bg-white rounded-2xl shadow-sm p-6 space-y-4">
            <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
            <h2 class="font-semibold text-gray-800">Change Password</h2>
            <div class="grid grid-cols-1 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                    <input type="password" name="current_password" required
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <?php $__errorArgs = ['current_password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                        <input type="password" name="password" required minlength="8"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                        <input type="password" name="password_confirmation" required
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>
            </div>
            <button type="submit" class="bg-gray-800 text-white px-6 py-2.5 rounded-xl text-sm font-semibold hover:bg-gray-900 transition">Update Password</button>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.account', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\completeEcom\resources\views/account/profile.blade.php ENDPATH**/ ?>