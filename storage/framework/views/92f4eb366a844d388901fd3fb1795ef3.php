<?php $__env->startSection('title', 'Checkout'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-[1200px] mx-auto px-4 py-8">
    <h1 class="text-2xl font-extrabold text-gray-900 mb-6">Checkout</h1>

    <?php if($errors->any()): ?>
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6">
            <div class="flex items-start space-x-3">
                <svg class="w-5 h-5 text-red-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <p class="text-sm font-semibold text-red-700 mb-1">Please fix the following errors:</p>
                    <ul class="list-disc list-inside space-y-1">
                        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li class="text-sm text-red-600"><?php echo e($error); ?></li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
            </div>
        </div>
    <?php endif; ?>

                <?php if(!auth()->check()): ?>
                <div class="bg-orange-50 border border-orange-200 rounded-xl p-4 mb-6 flex items-center gap-3">
                    <svg class="w-5 h-5 text-orange-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <div class="text-sm">
                        <p class="font-semibold text-orange-700">No separate registration needed!</p>
                        <p class="text-orange-600">An account will be created automatically using your phone number. Next time, just enter your phone number to track orders.</p>
                    </div>
                </div>
                <?php endif; ?>

    <form action="<?php echo e(route('checkout.store')); ?>" method="POST"
        x-data="{
            selected: '<?php echo e(old('payment_method', $paymentMethods->first()?->slug)); ?>',
            methods:  <?php echo e(Js::from($paymentMethods->map(fn($m) => [
                'slug'           => $m->slug,
                'name'           => $m->name,
                'type'           => $m->type,
                'description'    => $m->description,
                'instructions'   => $m->instructions,
                'account_name'   => $m->account_name,
                'account_number' => $m->account_number,
                'bank_name'      => $m->bank_name,
                'charge'         => $methodCharges[$m->slug] ?? 0,
            ]))); ?>,
            base: <?php echo e($base); ?>,
            get current() { return this.methods.find(m => m.slug === this.selected) || {} },
            get total() { return this.base + (parseFloat(this.current.charge) || 0) },
            needsTxn(type) { return ['mobile_banking','bank_transfer'].includes(type) }
        }">
        <?php echo csrf_field(); ?>
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">
            <div class="lg:col-span-3 space-y-6">
                
                <?php if(!auth()->check()): ?>
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h2 class="text-lg font-bold text-gray-800 mb-4">Contact Information</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                            <input type="text" name="shipping_name" id="shipping_name"
                                value="<?php echo e(old('shipping_name')); ?>"
                                class="w-full border rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 <?php echo e($errors->has('shipping_name') ? 'border-red-400 bg-red-50' : 'border-gray-200'); ?>">
                            <?php $__errorArgs = ['shipping_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number <span class="text-red-500">*</span></label>
                            <input type="text" name="shipping_phone" id="shipping_phone"
                                value="<?php echo e(old('shipping_phone')); ?>" placeholder="01XXXXXXXXX"
                                class="w-full border rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 <?php echo e($errors->has('shipping_phone') ? 'border-red-400 bg-red-50' : 'border-gray-200'); ?>">
                            <?php $__errorArgs = ['shipping_phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            <p class="text-xs text-gray-400 mt-1">Your account will be created with this number</p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h2 class="text-lg font-bold text-gray-800 mb-4">Shipping Address</h2>

                    <?php if($addresses->isNotEmpty()): ?>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Use saved address</label>
                            <div class="space-y-2">
                                <?php $__currentLoopData = $addresses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $address): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <label class="flex items-start space-x-3 cursor-pointer bg-gray-50 rounded-xl p-4 hover:bg-orange-50 transition"
                                        onclick="fillAddress(<?php echo e(json_encode($address)); ?>)">
                                        <input type="radio" name="saved_address" value="<?php echo e($address->id); ?>" class="mt-1 text-orange-500">
                                        <div class="text-sm">
                                            <p class="font-semibold text-gray-800"><?php echo e($address->name); ?></p>
                                            <p class="text-gray-500"><?php echo e($address->address_line1); ?>, <?php echo e($address->city); ?></p>
                                            <p class="text-gray-500"><?php echo e($address->phone); ?></p>
                                        </div>
                                    </label>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                            <p class="text-xs text-gray-400 mt-2">Or fill in a new address below</p>
                        </div>
                    <?php endif; ?>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <?php if(auth()->check()): ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                            <input type="text" name="shipping_name" id="shipping_name"
                                value="<?php echo e(old('shipping_name', auth()->user()->name)); ?>"
                                class="w-full border rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 <?php echo e($errors->has('shipping_name') ? 'border-red-400 bg-red-50' : 'border-gray-200'); ?>">
                            <?php $__errorArgs = ['shipping_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phone <span class="text-red-500">*</span></label>
                            <input type="text" name="shipping_phone" id="shipping_phone"
                                value="<?php echo e(old('shipping_phone', auth()->user()->phone)); ?>"
                                placeholder="01XXXXXXXXX"
                                class="w-full border rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 <?php echo e($errors->has('shipping_phone') ? 'border-red-400 bg-red-50' : 'border-gray-200'); ?>">
                            <?php $__errorArgs = ['shipping_phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <?php endif; ?>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Address <span class="text-red-500">*</span></label>
                            <input type="text" name="shipping_address" id="shipping_address"
                                value="<?php echo e(old('shipping_address')); ?>"
                                placeholder="Street address, house number, area..."
                                class="w-full border rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 <?php echo e($errors->has('shipping_address') ? 'border-red-400 bg-red-50' : 'border-gray-200'); ?>">
                            <?php $__errorArgs = ['shipping_address'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">City <span class="text-red-500">*</span></label>
                            <input type="text" name="shipping_city" id="shipping_city"
                                value="<?php echo e(old('shipping_city')); ?>" placeholder="Dhaka"
                                class="w-full border rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 <?php echo e($errors->has('shipping_city') ? 'border-red-400 bg-red-50' : 'border-gray-200'); ?>">
                            <?php $__errorArgs = ['shipping_city'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">District</label>
                            <input type="text" name="shipping_state" id="shipping_state"
                                value="<?php echo e(old('shipping_state')); ?>" placeholder="Dhaka"
                                class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ZIP Code</label>
                            <input type="text" name="shipping_zip" id="shipping_zip"
                                value="<?php echo e(old('shipping_zip')); ?>" placeholder="1207"
                                class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                            <input type="text" name="shipping_country" value="Bangladesh" readonly
                                class="w-full border border-gray-100 rounded-lg px-4 py-2.5 text-sm bg-gray-50 text-gray-500">
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Order Notes <span class="text-gray-400">(optional)</span></label>
                        <textarea name="notes" rows="2" placeholder="Any special instructions..."
                            class="w-full border border-gray-200 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500"><?php echo e(old('notes')); ?></textarea>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h2 class="text-lg font-bold text-gray-800 mb-4">Payment Method</h2>

                    <?php $__errorArgs = ['payment_method'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p class="text-red-500 text-xs mb-3 bg-red-50 border border-red-200 rounded-lg px-3 py-2"><?php echo e($message); ?></p>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-5">
                        <?php $__currentLoopData = $paymentMethods; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pm): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <label @click="selected = '<?php echo e($pm->slug); ?>'"
                            class="flex items-center space-x-3 border rounded-xl p-4 cursor-pointer transition"
                            :class="selected === '<?php echo e($pm->slug); ?>' ? 'border-orange-500 bg-orange-50 shadow-sm' : 'border-gray-200 hover:border-orange-300'">
                            <input type="radio" name="payment_method" value="<?php echo e($pm->slug); ?>"
                                <?php echo e(old('payment_method', $paymentMethods->first()?->slug) === $pm->slug ? 'checked' : ''); ?>

                                x-model="selected" class="text-orange-500 sr-only">
                            <div class="flex-shrink-0 w-10 h-10 rounded-lg overflow-hidden flex items-center justify-center
                                <?php if($pm->type === 'cod'): ?> bg-green-100
                                <?php elseif($pm->type === 'mobile_banking'): ?> bg-pink-100
                                <?php elseif($pm->type === 'card'): ?> bg-purple-100
                                <?php else: ?> bg-blue-100 <?php endif; ?>">
                                <?php if($pm->logo): ?>
                                    <img src="<?php echo e(Storage::url($pm->logo)); ?>" class="w-8 h-8 object-contain">
                                <?php elseif($pm->type === 'cod'): ?>
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                <?php elseif($pm->type === 'mobile_banking'): ?>
                                    <svg class="w-5 h-5 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                <?php else: ?>
                                    <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                <?php endif; ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-gray-800 text-sm"><?php echo e($pm->name); ?></p>
                                <p class="text-xs text-gray-400"><?php echo e($pm->description); ?></p>
                                <?php if($pm->charge_type !== 'none'): ?>
                                    <p class="text-xs text-orange-500 mt-0.5">
                                        + <?php echo e($pm->charge_type === 'percent' ? $pm->charge_value . '%' : '৳' . number_format($pm->charge_value, 2)); ?> fee
                                    </p>
                                <?php endif; ?>
                            </div>
                            <div :class="selected === '<?php echo e($pm->slug); ?>' ? 'text-orange-500' : 'text-transparent'">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            </div>
                        </label>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>

                    <div x-show="current.instructions" x-cloak
                        class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-4">
                        <p class="text-xs font-semibold text-blue-700 mb-1" x-text="current.name + ' Instructions'"></p>
                        <template x-if="current.account_number">
                            <div class="bg-white border border-blue-200 rounded-lg p-3 mb-2 flex items-center justify-between">
                                <div>
                                    <p class="text-xs text-gray-500" x-text="current.type === 'mobile_banking' ? 'Send to this number' : 'Account Number'"></p>
                                    <p class="font-bold text-gray-900 text-lg font-mono" x-text="current.account_number"></p>
                                    <p class="text-xs text-gray-400" x-text="current.account_name"></p>
                                </div>
                                <svg class="w-6 h-6 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            </div>
                        </template>
                        <p class="text-xs text-blue-700 whitespace-pre-line" x-text="current.instructions"></p>
                    </div>

                    <div x-show="needsTxn(current.type)" x-cloak class="space-y-3">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Transaction ID *</label>
                                <input type="text" name="transaction_id" value="<?php echo e(old('transaction_id')); ?>"
                                    placeholder="e.g. 8M3R6TXYZ"
                                    class="w-full border rounded-lg px-4 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-orange-500 <?php $__errorArgs = ['transaction_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-400 bg-red-50 <?php else: ?> border-gray-200 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                                <?php $__errorArgs = ['transaction_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Your Mobile Number *</label>
                                <input type="text" name="sender_number" value="<?php echo e(old('sender_number')); ?>"
                                    placeholder="01XXXXXXXXX"
                                    class="w-full border rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 <?php $__errorArgs = ['sender_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-400 bg-red-50 <?php else: ?> border-gray-200 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                                <?php $__errorArgs = ['sender_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>
                        <p class="text-xs text-gray-400">Your order will be confirmed after we verify your payment (usually within 1–2 hours).</p>
                    </div>

                    <div x-show="current.type === 'cod'" x-cloak
                        class="bg-green-50 border border-green-200 rounded-xl p-3 flex items-center space-x-3">
                        <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p class="text-sm text-green-700">Pay in cash when your order arrives. No advance payment needed.</p>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-sm p-6 lg:sticky lg:top-24">
                    <h2 class="text-lg font-bold text-gray-800 mb-4">Order Summary</h2>

                    <div class="space-y-3 mb-4 max-h-48 lg:max-h-64 overflow-y-auto">
                        <?php $__currentLoopData = $cartItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="flex items-center space-x-3">
                                <div class="w-12 h-12 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0">
                                    <?php if($item->product->image): ?>
                                        <img src="<?php echo e(Storage::url($item->product->image)); ?>" class="w-full h-full object-cover">
                                    <?php endif; ?>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-gray-700 truncate"><?php echo e($item->product->name); ?></p>
                                    <p class="text-xs text-gray-400">× <?php echo e($item->quantity); ?></p>
                                </div>
                                <span class="text-sm font-semibold text-gray-900 flex-shrink-0">৳<?php echo e(number_format($item->subtotal)); ?></span>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>

                    <div class="border-t border-gray-100 pt-4 space-y-2 text-sm">
                        <div class="flex justify-between text-gray-600">
                            <span>Subtotal</span>
                            <span>৳<?php echo e(number_format($subtotal)); ?></span>
                        </div>
                        <?php if($discount > 0): ?>
                            <div class="flex justify-between text-green-600">
                                <span>Discount (<?php echo e($coupon); ?>)</span>
                                <span>-৳<?php echo e(number_format($discount)); ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="flex justify-between text-gray-600">
                            <span>Shipping</span>
                            <span>৳<?php echo e(number_format($shipping)); ?></span>
                        </div>
                        <div class="flex justify-between text-orange-600" x-show="(current.charge||0) > 0" x-cloak>
                            <span x-text="(current.name || 'Payment') + ' Fee'"></span>
                            <span x-text="'৳' + parseFloat(current.charge||0).toFixed(2)"></span>
                        </div>
                        <div class="border-t border-gray-100 pt-2 flex justify-between font-bold text-gray-900 text-base">
                            <span>Total</span>
                            <span x-text="'৳' + total.toLocaleString('en-US', {minimumFractionDigits:0, maximumFractionDigits:0})">৳<?php echo e(number_format($base)); ?></span>
                        </div>
                    </div>

                    <button type="submit"
                        class="w-full mt-6 bg-orange-500 text-white py-3 rounded-xl font-bold hover:bg-orange-600 active:bg-orange-700 transition flex items-center justify-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Place Order</span>
                    </button>

                    <p class="text-xs text-gray-400 text-center mt-3">
                        By placing an order you agree to our Terms &amp; Conditions
                    </p>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function fillAddress(address) {
    document.getElementById('shipping_name').value    = address.name          || '';
    document.getElementById('shipping_phone').value   = address.phone         || '';
    document.getElementById('shipping_address').value = address.address_line1 || '';
    document.getElementById('shipping_city').value    = address.city          || '';
    document.getElementById('shipping_state').value   = address.state         || '';
    document.getElementById('shipping_zip').value     = address.zip           || '';
}
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\azad-ecom\resources\views/checkout/index.blade.php ENDPATH**/ ?>