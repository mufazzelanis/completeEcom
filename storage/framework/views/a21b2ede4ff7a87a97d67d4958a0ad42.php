<?php $__env->startSection('title', $product->name); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-7xl mx-auto px-4 py-8">
    <!-- Breadcrumb -->
    <div class="flex items-center space-x-2 text-sm text-gray-500 mb-6">
        <a href="<?php echo e(route('home')); ?>" class="hover:text-indigo-600">Home</a>
        <span>/</span>
        <a href="<?php echo e(route('shop.index')); ?>" class="hover:text-indigo-600">Shop</a>
        <span>/</span>
        <a href="<?php echo e(route('shop.category', $product->category->slug)); ?>" class="hover:text-indigo-600"><?php echo e($product->category->name); ?></a>
        <span>/</span>
        <span class="text-gray-900 font-medium line-clamp-1"><?php echo e($product->name); ?></span>
    </div>

    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 p-8">
            <!-- Images -->
            <div x-data="{ active: '<?php echo e($product->image ? Storage::url($product->image) : ''); ?>' }">
                <div class="aspect-square rounded-xl overflow-hidden bg-gray-100 mb-4">
                    <template x-if="active">
                        <img :src="active" alt="<?php echo e($product->name); ?>" class="w-full h-full object-cover">
                    </template>
                    <template x-if="!active">
                        <div class="w-full h-full flex items-center justify-center">
                            <svg class="w-24 h-24 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    </template>
                </div>
                <?php if($product->images->isNotEmpty()): ?>
                    <div class="flex space-x-3 overflow-x-auto">
                        <?php if($product->image): ?>
                            <button @click="active = '<?php echo e(Storage::url($product->image)); ?>'"
                                class="w-20 h-20 flex-shrink-0 rounded-lg overflow-hidden border-2 border-indigo-500">
                                <img src="<?php echo e(Storage::url($product->image)); ?>" class="w-full h-full object-cover">
                            </button>
                        <?php endif; ?>
                        <?php $__currentLoopData = $product->images; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $img): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <button @click="active = '<?php echo e(Storage::url($img->image)); ?>'"
                                class="w-20 h-20 flex-shrink-0 rounded-lg overflow-hidden border-2 border-gray-200 hover:border-indigo-400">
                                <img src="<?php echo e(Storage::url($img->image)); ?>" class="w-full h-full object-cover">
                            </button>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Product Info -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-indigo-600 text-sm font-medium"><?php echo e($product->category->name); ?></span>
                    <?php if(auth()->guard()->check()): ?>
                        <form action="<?php echo e(route('wishlist.toggle', $product->id)); ?>" method="POST">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="p-2 rounded-full hover:bg-red-50 transition <?php echo e($wishlisted ? 'text-red-500' : 'text-gray-400'); ?>">
                                <svg class="w-6 h-6" fill="<?php echo e($wishlisted ? 'currentColor' : 'none'); ?>" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                </svg>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-4"><?php echo e($product->name); ?></h1>

                <!-- Rating -->
                <div class="flex items-center space-x-2 mb-4">
                    <div class="flex">
                        <?php for($i = 1; $i <= 5; $i++): ?>
                            <svg class="w-5 h-5 <?php echo e($i <= round($product->average_rating) ? 'text-yellow-400' : 'text-gray-300'); ?>" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        <?php endfor; ?>
                    </div>
                    <span class="text-sm text-gray-500">(<?php echo e($product->reviews->count()); ?> reviews)</span>
                </div>

                <!-- Price -->
                <div class="mb-6">
                    <?php if($product->sale_price): ?>
                        <div class="flex items-center space-x-3">
                            <span class="text-3xl font-bold text-red-600">৳<?php echo e(number_format($product->sale_price)); ?></span>
                            <span class="text-xl text-gray-400 line-through">৳<?php echo e(number_format($product->price)); ?></span>
                            <span class="bg-red-100 text-red-600 text-sm px-2 py-1 rounded-full font-medium">
                                <?php echo e(round((1 - $product->sale_price / $product->price) * 100)); ?>% off
                            </span>
                        </div>
                    <?php else: ?>
                        <span class="text-3xl font-bold text-gray-900">৳<?php echo e(number_format($product->price)); ?></span>
                    <?php endif; ?>
                </div>

                <?php if($product->short_description): ?>
                    <p class="text-gray-600 mb-6"><?php echo e($product->short_description); ?></p>
                <?php endif; ?>

                <!-- Stock -->
                <div class="mb-6">
                    <?php if($product->stock > 0): ?>
                        <span class="inline-flex items-center bg-green-100 text-green-700 px-3 py-1 rounded-full text-sm font-medium">
                            <span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                            In Stock (<?php echo e($product->stock); ?> available)
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center bg-red-100 text-red-700 px-3 py-1 rounded-full text-sm font-medium">
                            <span class="w-2 h-2 bg-red-500 rounded-full mr-2"></span>
                            Out of Stock
                        </span>
                    <?php endif; ?>
                    <?php if($product->sku): ?>
                        <span class="ml-3 text-xs text-gray-400">SKU: <?php echo e($product->sku); ?></span>
                    <?php endif; ?>
                </div>

                <!-- Add to Cart / Buy Now -->
                <?php if($product->stock > 0): ?>
                    <form action="<?php echo e(route('cart.add')); ?>" method="POST" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 mb-6">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="product_id" value="<?php echo e($product->id); ?>">
                        <div class="flex items-center border border-gray-200 rounded-xl overflow-hidden">
                            <button type="button" onclick="updateQty(-1)" class="px-4 py-3 text-gray-500 hover:bg-gray-100 text-lg font-bold">-</button>
                            <input type="number" name="quantity" id="qty" value="1" min="1" max="<?php echo e($product->stock); ?>"
                                class="w-16 text-center py-3 border-0 focus:outline-none text-sm font-semibold">
                            <button type="button" onclick="updateQty(1)" class="px-4 py-3 text-gray-500 hover:bg-gray-100 text-lg font-bold">+</button>
                        </div>
                        <div class="flex-1 flex gap-3">
                            <button type="submit" formaction="<?php echo e(route('cart.add')); ?>"
                                class="flex-1 bg-white border-2 border-indigo-600 text-indigo-600 py-3 rounded-xl font-semibold hover:bg-indigo-50 transition flex items-center justify-center space-x-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                <span>Add to Cart</span>
                            </button>
                            <button type="submit" formaction="<?php echo e(route('checkout.buy-now')); ?>"
                                class="flex-1 bg-indigo-600 text-white py-3 rounded-xl font-semibold hover:bg-indigo-700 transition flex items-center justify-center space-x-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                <span>Buy Now</span>
                            </button>
                        </div>
                    </form>
                <?php endif; ?>

                <!-- Meta -->
                <div class="border-t border-gray-100 pt-6 space-y-3 text-sm text-gray-500">
                    <div class="flex items-center space-x-2">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                        <span>Free shipping on orders over ৳2000</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span>7-day return policy</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Description + Reviews Tabs -->
        <div class="border-t border-gray-100 p-8" x-data="{ tab: 'description' }">
            <div class="flex space-x-6 border-b border-gray-200 mb-6">
                <button @click="tab = 'description'" :class="tab === 'description' ? 'border-b-2 border-indigo-600 text-indigo-600' : 'text-gray-500'" class="pb-3 font-medium text-sm">
                    Description
                </button>
                <button @click="tab = 'reviews'" :class="tab === 'reviews' ? 'border-b-2 border-indigo-600 text-indigo-600' : 'text-gray-500'" class="pb-3 font-medium text-sm">
                    Reviews (<?php echo e($product->reviews->count()); ?>)
                </button>
            </div>

            <div x-show="tab === 'description'">
                <div class="prose prose-sm max-w-none text-gray-600">
                    <?php echo nl2br(e($product->description ?? 'No description available.')); ?>

                </div>
            </div>

            <div x-show="tab === 'reviews'" x-cloak>
                <?php $__currentLoopData = $product->reviews; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $review): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="border-b border-gray-100 pb-6 mb-6 last:border-0">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center">
                                    <span class="text-indigo-600 font-semibold text-sm"><?php echo e(strtoupper(substr($review->user->name, 0, 1))); ?></span>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-800 text-sm"><?php echo e($review->user->name); ?></p>
                                    <div class="flex">
                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                            <svg class="w-4 h-4 <?php echo e($i <= $review->rating ? 'text-yellow-400' : 'text-gray-300'); ?>" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            </div>
                            <span class="text-xs text-gray-400"><?php echo e($review->created_at->diffForHumans()); ?></span>
                        </div>
                        <p class="text-gray-600 text-sm mt-2"><?php echo e($review->comment); ?></p>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                <?php if(auth()->guard()->check()): ?>
                    <div class="bg-gray-50 rounded-xl p-6 mt-6">
                        <h4 class="font-semibold text-gray-800 mb-4">Write a Review</h4>
                        <form action="<?php echo e(route('products.review', $product->slug)); ?>" method="POST">
                            <?php echo csrf_field(); ?>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Rating</label>
                                <div class="flex space-x-2" x-data="{ rating: 0 }">
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                        <label class="cursor-pointer">
                                            <input type="radio" name="rating" value="<?php echo e($i); ?>" class="sr-only" @change="rating = <?php echo e($i); ?>">
                                            <svg class="w-8 h-8" :class="rating >= <?php echo e($i); ?> ? 'text-yellow-400' : 'text-gray-300'" fill="currentColor" viewBox="0 0 20 20" @click="rating = <?php echo e($i); ?>">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                        </label>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Comment</label>
                                <textarea name="comment" rows="3" placeholder="Share your experience..."
                                    class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                            </div>
                            <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-xl text-sm font-medium hover:bg-indigo-700 transition">
                                Submit Review
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8 text-gray-500 text-sm">
                        <a href="<?php echo e(route('login')); ?>" class="text-indigo-600 hover:underline">Login</a> to write a review.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Related Products -->
    <?php if($related->isNotEmpty()): ?>
        <div class="mt-12">
            <h2 class="text-xl font-bold text-gray-900 mb-6">Related Products</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <?php $__currentLoopData = $related; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $relProduct): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php echo $__env->make('partials.product-card', ['product' => $relProduct], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function updateQty(delta) {
    const input = document.getElementById('qty');
    const max = parseInt(input.max);
    const newVal = parseInt(input.value) + delta;
    input.value = Math.max(1, Math.min(newVal, max));
}
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\azad-ecom\resources\views/products/show.blade.php ENDPATH**/ ?>