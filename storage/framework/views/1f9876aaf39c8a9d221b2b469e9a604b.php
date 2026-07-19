<?php $__env->startSection('title', 'Products'); ?>

<?php $__env->startSection('content'); ?>
<div class="mb-6 bg-white rounded-2xl shadow-sm p-4">
    <form action="<?php echo e(route('admin.products.index')); ?>" method="GET" id="admin-product-filter">
        <!-- Row 1: Search + primary filters -->
        <div class="flex flex-wrap items-center gap-2 mb-3">
            <!-- Search with auto-suggest -->
            <div class="relative" x-data="{
                query: '<?php echo e(addslashes(request('search', ''))); ?>',
                results: [],
                open: false,
                async fetchSuggestions() {
                    if (this.query.length < 2) { this.open = false; return; }
                    const res = await fetch('<?php echo e(route('admin.search.suggest')); ?>?q=' + encodeURIComponent(this.query));
                    const data = await res.json();
                    this.results = data.products;
                    this.open = this.results.length > 0;
                }
            }" @click.outside="open = false">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" name="search"
                        x-model="query"
                        @input.debounce.300ms="fetchSuggestions()"
                        @focus="query.length > 1 && fetchSuggestions()"
                        @keydown.escape="open = false"
                        placeholder="Search name, SKU, barcode..."
                        class="border border-gray-200 rounded-xl pl-9 pr-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 w-64"
                        autocomplete="off">
                </div>
                <div x-show="open" x-cloak
                     class="absolute top-full left-0 bg-white shadow-xl rounded-xl border border-gray-100 z-50 mt-1 w-80 overflow-hidden">
                    <template x-for="product in results" :key="product.url">
                        <a :href="product.url" @click="open = false"
                           class="flex items-center px-3 py-2.5 hover:bg-indigo-50 gap-3">
                            <div class="w-9 h-9 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0 flex items-center justify-center">
                                <img x-show="product.image" :src="product.image" class="w-full h-full object-cover">
                                <svg x-show="!product.image" class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800 truncate" x-text="product.name"></p>
                                <p class="text-xs text-gray-500" x-text="product.sku + ' · ' + product.price"></p>
                            </div>
                            <span :class="product.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                                  class="text-xs px-1.5 py-0.5 rounded-full flex-shrink-0"
                                  x-text="product.is_active ? 'Active' : 'Inactive'"></span>
                        </a>
                    </template>
                </div>
            </div>

            <select name="category" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">All Categories</option>
                <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($cat->id); ?>" <?php echo e(request('category') == $cat->id ? 'selected' : ''); ?>><?php echo e($cat->name); ?></option>
                    <?php $__currentLoopData = $cat->children ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $child): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($child->id); ?>" <?php echo e(request('category') == $child->id ? 'selected' : ''); ?>>&nbsp;&nbsp;↳ <?php echo e($child->name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>

            <select name="type" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">All Types</option>
                <option value="simple"   <?php echo e(request('type') === 'simple'   ? 'selected' : ''); ?>>Simple</option>
                <option value="variable" <?php echo e(request('type') === 'variable' ? 'selected' : ''); ?>>Variable</option>
                <option value="bundle"   <?php echo e(request('type') === 'bundle'   ? 'selected' : ''); ?>>Bundle</option>
                <option value="digital"  <?php echo e(request('type') === 'digital'  ? 'selected' : ''); ?>>Digital</option>
            </select>

            <select name="brand" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">All Brands</option>
                <?php $__currentLoopData = $brands; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $brand): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($brand->id); ?>" <?php echo e(request('brand') == $brand->id ? 'selected' : ''); ?>><?php echo e($brand->name); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>

            <select name="status" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">Any Status</option>
                <option value="active"   <?php echo e(request('status') === 'active'   ? 'selected' : ''); ?>>Active</option>
                <option value="inactive" <?php echo e(request('status') === 'inactive' ? 'selected' : ''); ?>>Inactive</option>
            </select>
        </div>

        <!-- Row 2: Secondary filters + sort + buttons -->
        <div class="flex flex-wrap items-center gap-2">
            <select name="stock_status" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">Any Stock</option>
                <option value="in"  <?php echo e(request('stock_status') === 'in'  ? 'selected' : ''); ?>>In Stock</option>
                <option value="low" <?php echo e(request('stock_status') === 'low' ? 'selected' : ''); ?>>Low Stock</option>
                <option value="out" <?php echo e(request('stock_status') === 'out' ? 'selected' : ''); ?>>Out of Stock</option>
            </select>

            <label class="flex items-center gap-1.5 border border-gray-200 rounded-xl px-3 py-2 cursor-pointer text-sm text-gray-700 hover:bg-gray-50 select-none">
                <input type="checkbox" name="on_sale" value="1" <?php echo e(request('on_sale') ? 'checked' : ''); ?> class="rounded text-indigo-600">
                On Sale
            </label>

            <label class="flex items-center gap-1.5 border border-gray-200 rounded-xl px-3 py-2 cursor-pointer text-sm text-gray-700 hover:bg-gray-50 select-none">
                <input type="checkbox" name="featured" value="1" <?php echo e(request('featured') ? 'checked' : ''); ?> class="rounded text-indigo-600">
                Featured
            </label>

            <select name="sort_by" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="latest"     <?php echo e(request('sort_by', 'latest') === 'latest'     ? 'selected' : ''); ?>>Newest First</option>
                <option value="oldest"     <?php echo e(request('sort_by') === 'oldest'     ? 'selected' : ''); ?>>Oldest First</option>
                <option value="name_asc"   <?php echo e(request('sort_by') === 'name_asc'   ? 'selected' : ''); ?>>Name A–Z</option>
                <option value="name_desc"  <?php echo e(request('sort_by') === 'name_desc'  ? 'selected' : ''); ?>>Name Z–A</option>
                <option value="price_asc"  <?php echo e(request('sort_by') === 'price_asc'  ? 'selected' : ''); ?>>Price Low–High</option>
                <option value="price_desc" <?php echo e(request('sort_by') === 'price_desc' ? 'selected' : ''); ?>>Price High–Low</option>
                <option value="stock_asc"  <?php echo e(request('sort_by') === 'stock_asc'  ? 'selected' : ''); ?>>Stock Low–High</option>
                <option value="stock_desc" <?php echo e(request('sort_by') === 'stock_desc' ? 'selected' : ''); ?>>Stock High–Low</option>
            </select>

            <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-gray-700 transition">
                Filter
            </button>
            <?php if(request()->hasAny(['search','category','type','brand','status','stock_status','on_sale','featured','sort_by'])): ?>
                <a href="<?php echo e(route('admin.products.index')); ?>" class="px-4 py-2 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50">Clear</a>
            <?php endif; ?>

            <div class="ml-auto">
                <a href="<?php echo e(route('admin.products.create')); ?>" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-indigo-700 transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add Product
                </a>
            </div>
        </div>

        <!-- Active filter chips -->
        <?php
            $adminActiveFilters = array_filter([
                request('search'), request('category'), request('type'), request('brand'),
                request('status'), request('stock_status'), request('on_sale'), request('featured'),
            ]);
        ?>
        <?php if(count($adminActiveFilters) > 0): ?>
        <div class="flex flex-wrap items-center gap-1.5 mt-3 pt-3 border-t border-gray-100">
            <span class="text-xs text-gray-400">Active filters:</span>
            <?php if(request('search')): ?><span class="inline-flex items-center gap-1 px-2 py-0.5 bg-indigo-50 text-indigo-700 rounded-full text-xs">"<?php echo e(request('search')); ?>"</span><?php endif; ?>
            <?php if(request('status')): ?><span class="inline-flex items-center gap-1 px-2 py-0.5 bg-indigo-50 text-indigo-700 rounded-full text-xs capitalize"><?php echo e(request('status')); ?></span><?php endif; ?>
            <?php if(request('type')): ?><span class="inline-flex items-center gap-1 px-2 py-0.5 bg-purple-50 text-purple-700 rounded-full text-xs capitalize"><?php echo e(request('type')); ?></span><?php endif; ?>
            <?php if(request('stock_status')): ?><span class="inline-flex items-center gap-1 px-2 py-0.5 bg-yellow-50 text-yellow-700 rounded-full text-xs capitalize"><?php echo e(request('stock_status')); ?> stock</span><?php endif; ?>
            <?php if(request('on_sale')): ?><span class="inline-flex items-center gap-1 px-2 py-0.5 bg-red-50 text-red-700 rounded-full text-xs">On Sale</span><?php endif; ?>
            <?php if(request('featured')): ?><span class="inline-flex items-center gap-1 px-2 py-0.5 bg-yellow-50 text-yellow-700 rounded-full text-xs">Featured</span><?php endif; ?>
            <span class="text-xs text-gray-400 ml-1">— <?php echo e($products->total()); ?> result(s)</span>
        </div>
        <?php endif; ?>
    </form>
</div>

<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr class="text-xs text-gray-500 uppercase tracking-wider">
                <th class="px-6 py-3 text-left">Product</th>
                <th class="px-6 py-3 text-left">Category</th>
                <th class="px-6 py-3 text-right">Price</th>
                <th class="px-6 py-3 text-center">Stock</th>
                <th class="px-6 py-3 text-center">Status</th>
                <th class="px-6 py-3 text-center">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            <?php $__empty_1 = true; $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-12 h-12 bg-gray-100 rounded-xl overflow-hidden flex-shrink-0">
                                <?php if($product->image): ?>
                                    <img src="<?php echo e(Storage::url($product->image)); ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center text-gray-300">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="min-w-0">
                                <p class="font-medium text-gray-800 text-sm truncate max-w-48"><?php echo e($product->name); ?></p>
                                <p class="text-xs text-gray-400"><?php echo e($product->sku ?? 'No SKU'); ?><?php if($product->barcode): ?> · <?php echo e($product->barcode); ?><?php endif; ?></p>
                                <div class="flex items-center gap-1 mt-0.5">
                                    <span class="text-xs px-1.5 py-0.5 rounded-full font-medium <?php echo e($product->typeBadge()); ?>"><?php echo e(ucfirst($product->type ?? 'simple')); ?></span>
                                    <?php if($product->brand): ?><span class="text-xs text-gray-400"><?php echo e($product->brand->name); ?></span><?php endif; ?>
                                    <?php if($product->is_featured): ?><span class="text-xs bg-yellow-100 text-yellow-700 px-1.5 py-0.5 rounded-full">Featured</span><?php endif; ?>
                                    <?php if($product->sale_price): ?><span class="text-xs bg-red-100 text-red-600 px-1.5 py-0.5 rounded-full">Sale</span><?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        <?php echo e($product->category->name); ?>

                        <?php if($product->subcategory): ?>
                            <span class="text-gray-400 text-xs block">↳ <?php echo e($product->subcategory->name); ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <?php if($product->sale_price): ?>
                            <p class="font-semibold text-red-600 text-sm">৳<?php echo e(number_format($product->sale_price)); ?></p>
                            <p class="text-xs text-gray-400 line-through">৳<?php echo e(number_format($product->price)); ?></p>
                        <?php else: ?>
                            <p class="font-semibold text-gray-900 text-sm">৳<?php echo e(number_format($product->price)); ?></p>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="<?php echo e($product->stock === 0 ? 'text-red-600' : ($product->isLowStock() ? 'text-yellow-600' : 'text-gray-800')); ?> font-semibold text-sm"><?php echo e($product->stock); ?></span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo e($product->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'); ?>">
                            <?php echo e($product->is_active ? 'Active' : 'Inactive'); ?>

                        </span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <div class="flex items-center justify-center space-x-2">
                            <a href="<?php echo e(route('admin.products.edit', $product->id)); ?>" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Edit</a>
                            <form action="<?php echo e(route('admin.products.destroy', $product->id)); ?>" method="POST" onsubmit="return confirm('Delete this product?')">
                                <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                <button type="submit" class="text-red-500 hover:text-red-700 text-sm font-medium">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-400">No products found. <a href="<?php echo e(route('admin.products.create')); ?>" class="text-indigo-600">Add one</a>.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
        <span class="text-sm text-gray-500"><?php echo e($products->total()); ?> product(s) total</span>
        <?php echo e($products->links()); ?>

    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\completeEcom\resources\views/admin/products/index.blade.php ENDPATH**/ ?>