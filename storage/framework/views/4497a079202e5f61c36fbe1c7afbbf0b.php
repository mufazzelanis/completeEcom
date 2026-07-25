<?php $__env->startSection('title', 'Add Product'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-5xl">
    <a href="<?php echo e(route('admin.products.index')); ?>" class="text-indigo-600 hover:text-indigo-700 text-sm flex items-center space-x-2 mb-6">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        <span>Back to Products</span>
    </a>

    <?php if($errors->any()): ?>
    <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6">
        <ul class="list-disc list-inside space-y-1">
            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li class="text-sm text-red-600"><?php echo e($e); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
    </div>
    <?php endif; ?>

    <?php $oldSelectedTags = $allTags->whereIn('id', array_map('intval', old('tag_ids', [])))->values(); ?>
    <form action="<?php echo e(route('admin.products.store')); ?>" method="POST" enctype="multipart/form-data"
        x-data="{
            productType: '<?php echo e(old('type','simple')); ?>',
            variants: [],
            colors: [],
            bundleItems: [],
            faqs: [],
            specs: [],
            selectedTags: <?php echo e(Js::from($oldSelectedTags)); ?>,
            tagQuery: '',
            showTagDropdown: false,
            creatingTag: false,
            allTags: <?php echo e(Js::from($allTags)); ?>,
            allProducts: <?php echo e(Js::from($simpleProducts)); ?>,
            attributeNames: <?php echo e(Js::from($attributeNames)); ?>,
            get tagSuggestions() {
                if (!this.tagQuery.trim()) return this.allTags.filter(t => !this.selectedTags.find(s=>s.id===t.id));
                const q = this.tagQuery.toLowerCase();
                return this.allTags.filter(t => t.name.toLowerCase().includes(q) && !this.selectedTags.find(s=>s.id===t.id));
            },
            get exactTagMatch() {
                const q = this.tagQuery.trim().toLowerCase();
                return q ? this.allTags.find(t => t.name.toLowerCase() === q) : null;
            },
            addTag(tag) { this.selectedTags.push(tag); this.tagQuery=''; this.showTagDropdown=false; },
            removeTag(id) { this.selectedTags = this.selectedTags.filter(t=>t.id!==id); },
            async createTag() {
                const name = this.tagQuery.trim();
                if (!name || this.creatingTag) return;
                const existing = this.exactTagMatch;
                if (existing) { this.addTag(existing); return; }
                this.creatingTag = true;
                try {
                    const res = await fetch('<?php echo e(route('admin.tags.quick-create')); ?>', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>', 'Accept': 'application/json' },
                        body: JSON.stringify({ name }),
                    });
                    if (res.ok) {
                        const tag = await res.json();
                        this.allTags.push(tag);
                        this.addTag(tag);
                    }
                } finally {
                    this.creatingTag = false;
                }
            },
            addVariant() { this.variants.push({ name:'', sku:'', price:'', stock:0, is_active:true }); },
            addColor() { this.colors.push({ name:'', hex_code:'#6366f1', stock:'', is_active:true }); },
            addBundleItem() { this.bundleItems.push({ product_id:'', quantity:1, discount_pct:0 }); },
            addFaq() { this.faqs.push({ question:'', answer:'' }); },
            addSpec() { this.specs.push({ key:'', value:'' }); },
            specKeyFilter(q) { return this.attributeNames.filter(n => n.toLowerCase().includes(q.toLowerCase())).slice(0,6); }
        }">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="type" :value="productType">

        
        <div class="bg-white rounded-2xl shadow-sm p-6 mb-6">
            <h3 class="font-semibold text-gray-800 mb-4">Product Type</h3>
            <div class="grid grid-cols-4 gap-3">
                <?php $__currentLoopData = ['simple'=>['Simple','Regular product with fixed price and stock','M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4'], 'variable'=>['Variable','Multiple sizes and color options','M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01'], 'bundle'=>['Bundle','Group of products sold together','M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'], 'digital'=>['Digital','Downloadable file (PDF, software, etc.)','M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z']]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $val => [$label, $desc, $icon]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <label class="flex flex-col items-center p-4 border-2 rounded-xl cursor-pointer transition"
                    :class="productType === '<?php echo e($val); ?>' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-gray-300'"
                    @click="productType='<?php echo e($val); ?>'">
                    <svg class="w-6 h-6 mb-2" :class="productType==='<?php echo e($val); ?>' ? 'text-indigo-600' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo e($icon); ?>"/>
                    </svg>
                    <span class="text-sm font-semibold" :class="productType==='<?php echo e($val); ?>' ? 'text-indigo-700' : 'text-gray-700'"><?php echo e($label); ?></span>
                    <span class="text-xs text-gray-400 text-center mt-1"><?php echo e($desc); ?></span>
                </label>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <div class="lg:col-span-2 space-y-6">

                
                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <h3 class="font-semibold text-gray-800 mb-4">Product Information</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Product Name *</label>
                            <input type="text" name="name" id="product-name-input" value="<?php echo e(old('name')); ?>"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-400 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                            <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">SKU</label>
                            <div class="flex gap-2">
                                <input type="text" name="sku" id="product-sku-input" value="<?php echo e(old('sku')); ?>" placeholder="e.g. TSHIRT-BLU-001"
                                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 <?php $__errorArgs = ['sku'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-400 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                                <button type="button" onclick="generateProductSku()"
                                    class="flex-shrink-0 px-3 rounded-xl border border-gray-200 text-xs font-medium text-gray-600 hover:bg-gray-50 transition whitespace-nowrap">
                                    Generate
                                </button>
                            </div>
                            <?php $__errorArgs = ['sku'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div x-data="{ val: <?php echo e(Js::from(old('short_description', ''))); ?> }">
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-sm font-medium text-gray-700">Short Description</label>
                                <span class="text-xs" :class="val.length > 200 ? 'text-red-500' : 'text-gray-400'" x-text="val.length + ' / 200'"></span>
                            </div>
                            <textarea name="short_description" rows="2" x-model="val" maxlength="200" placeholder="A quick one- or two-line summary shown on listing cards and search results…"
                                class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                            <p class="text-xs text-gray-400 mt-1">Keep it short and punchy — this is what customers see before opening the full description.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Full Description</label>
                            <?php echo $__env->make('admin.products._description_editor', ['name' => 'description', 'value' => old('description', ''), 'id' => 'description'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                            <p class="text-xs text-gray-400 mt-1">Use headings, lists and images to lay the description out exactly how you want customers to read it.</p>
                        </div>
                    </div>
                </div>

                
                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <h3 class="font-semibold text-gray-800 mb-4">Images</h3>

                    
                    <div x-data="{
                        preview: null,
                        onFiles(fileList) {
                            const file = fileList && fileList[0];
                            if (!file || !file.type.startsWith('image/')) return;
                            const dt = new DataTransfer(); dt.items.add(file);
                            $refs.mainImageInput.files = dt.files;
                            const reader = new FileReader();
                            reader.onload = e => this.preview = e.target.result;
                            reader.readAsDataURL(file);
                        },
                        clear() { this.preview = null; $refs.mainImageInput.value = ''; }
                    }">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Main Image</label>
                        <div class="border-2 border-dashed border-gray-200 rounded-xl p-4 text-center hover:border-indigo-400 transition cursor-pointer"
                            @dragover.prevent @dragleave.prevent @drop.prevent="onFiles($event.dataTransfer.files)"
                            @click="$refs.mainImageInput.click()">
                            <template x-if="!preview">
                                <div class="py-4 text-gray-400">
                                    <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M4 8h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <p class="text-sm">Click or drag an image here</p>
                                    <p class="text-xs text-gray-300 mt-0.5">PNG, JPG up to 4MB</p>
                                </div>
                            </template>
                            <template x-if="preview">
                                <div class="relative inline-block">
                                    <img :src="preview" class="w-28 h-28 object-cover rounded-xl border border-gray-100 mx-auto">
                                    <button type="button" @click.stop="clear()"
                                        class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm shadow hover:bg-red-600">×</button>
                                </div>
                            </template>
                        </div>
                        <input type="file" name="image" x-ref="mainImageInput" accept="image/*" class="hidden" @change="onFiles($event.target.files)">
                        <?php $__errorArgs = ['image'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    
                    <div class="mt-5" x-data="{
                        files: [],
                        addFiles(fileList) {
                            for (const f of fileList) { if (f.type.startsWith('image/')) this.files.push({ file: f, url: URL.createObjectURL(f) }); }
                            this.syncInput();
                        },
                        removeFile(i) { URL.revokeObjectURL(this.files[i].url); this.files.splice(i, 1); this.syncInput(); },
                        syncInput() {
                            const dt = new DataTransfer();
                            this.files.forEach(f => dt.items.add(f.file));
                            $refs.galleryInput.files = dt.files;
                        }
                    }">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Gallery Images</label>
                        <div class="border-2 border-dashed border-gray-200 rounded-xl p-4 text-center hover:border-indigo-400 transition cursor-pointer"
                            @dragover.prevent @dragleave.prevent @drop.prevent="addFiles($event.dataTransfer.files)"
                            @click="$refs.galleryInput.click()">
                            <div class="py-2 text-gray-400">
                                <svg class="w-7 h-7 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                <p class="text-sm">Click or drag multiple images here</p>
                                <p class="text-xs text-gray-300 mt-0.5">You can add as many as you like</p>
                            </div>
                        </div>
                        <input type="file" name="images[]" x-ref="galleryInput" accept="image/*" multiple class="hidden" @change="addFiles($event.target.files)">
                        <div class="flex flex-wrap gap-3 mt-3" x-show="files.length > 0" x-cloak>
                            <template x-for="(f, i) in files" :key="i">
                                <div class="relative group">
                                    <img :src="f.url" class="w-16 h-16 object-cover rounded-lg border border-gray-100">
                                    <button type="button" @click.stop="removeFile(i)"
                                        class="absolute -top-1.5 -right-1.5 bg-red-500 text-white rounded-full w-5 h-5 text-xs flex items-center justify-center opacity-0 group-hover:opacity-100 transition shadow">×</button>
                                </div>
                            </template>
                        </div>
                        <?php $__errorArgs = ['images.*'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>

                
                <div class="bg-white rounded-2xl shadow-sm p-6" x-show="productType === 'variable'" x-cloak>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-800">Size Variants <span class="text-xs text-gray-400 font-normal ml-1">(S, M, L, XL, etc.)</span></h3>
                        <button type="button" @click="addVariant()" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg> Add
                        </button>
                    </div>
                    <div class="space-y-2">
                        <template x-if="variants.length === 0"><p class="text-sm text-gray-400 py-2">No variants. Click Add to get started.</p></template>
                        <div class="grid grid-cols-12 gap-2 text-xs text-gray-500 font-medium px-1 mb-1" x-show="variants.length > 0">
                            <div class="col-span-3">Name *</div><div class="col-span-2">SKU</div>
                            <div class="col-span-2">Price (৳)</div><div class="col-span-2">Stock</div>
                            <div class="col-span-2">Active</div><div class="col-span-1"></div>
                        </div>
                        <template x-for="(v, i) in variants" :key="i">
                            <div class="grid grid-cols-12 gap-2 items-center bg-gray-50 rounded-xl px-3 py-2">
                                <div class="col-span-3"><input type="text" :name="`variants[${i}][name]`" x-model="v.name" placeholder="Small" class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"></div>
                                <div class="col-span-2"><input type="text" :name="`variants[${i}][sku]`" x-model="v.sku" class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"></div>
                                <div class="col-span-2"><input type="number" :name="`variants[${i}][price]`" x-model="v.price" placeholder="Base" class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"></div>
                                <div class="col-span-2"><input type="number" :name="`variants[${i}][stock]`" x-model="v.stock" min="0" class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"></div>
                                <div class="col-span-2 flex justify-center"><input type="checkbox" :name="`variants[${i}][is_active]`" value="1" checked class="w-4 h-4 text-indigo-600 rounded"></div>
                                <div class="col-span-1 flex justify-end"><button type="button" @click="variants.splice(i,1)" class="text-red-400 hover:text-red-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button></div>
                            </div>
                        </template>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">Leave Price blank to use the product's base price.</p>
                </div>

                
                <div class="bg-white rounded-2xl shadow-sm p-6" x-show="productType === 'variable'" x-cloak>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-800">Color Options</h3>
                        <button type="button" @click="addColor()" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg> Add
                        </button>
                    </div>
                    <div class="space-y-2">
                        <template x-if="colors.length === 0"><p class="text-sm text-gray-400 py-2">No colors. Click Add to get started.</p></template>
                        <div class="grid grid-cols-12 gap-2 text-xs text-gray-500 font-medium px-1 mb-1" x-show="colors.length > 0">
                            <div class="col-span-3">Name *</div><div class="col-span-3">Hex Code</div>
                            <div class="col-span-2">Swatch</div><div class="col-span-2">Stock</div><div class="col-span-1">On</div><div class="col-span-1"></div>
                        </div>
                        <template x-for="(c, i) in colors" :key="i">
                            <div class="grid grid-cols-12 gap-2 items-center bg-gray-50 rounded-xl px-3 py-2">
                                <div class="col-span-3"><input type="text" :name="`colors[${i}][name]`" x-model="c.name" placeholder="Navy Blue" class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"></div>
                                <div class="col-span-3 flex items-center gap-1">
                                    <input type="color" x-model="c.hex_code" class="w-8 h-8 rounded cursor-pointer border-0 p-0 flex-shrink-0">
                                    <input type="text" :name="`colors[${i}][hex_code]`" x-model="c.hex_code" class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-xs focus:outline-none font-mono">
                                </div>
                                <div class="col-span-2"><input type="file" :name="`color_images[${i}]`" accept="image/*" class="w-full text-xs"></div>
                                <div class="col-span-2"><input type="number" :name="`colors[${i}][stock]`" x-model="c.stock" placeholder="—" class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"></div>
                                <div class="col-span-1 flex justify-center"><input type="checkbox" :name="`colors[${i}][is_active]`" value="1" checked class="w-4 h-4 text-indigo-600 rounded"></div>
                                <div class="col-span-1 flex justify-end"><button type="button" @click="colors.splice(i,1)" class="text-red-400 hover:text-red-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button></div>
                            </div>
                        </template>
                    </div>
                </div>

                
                <div class="bg-white rounded-2xl shadow-sm p-6" x-show="productType === 'bundle'" x-cloak>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-800">Bundle Items</h3>
                        <button type="button" @click="addBundleItem()" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg> Add Item
                        </button>
                    </div>
                    <template x-if="bundleItems.length === 0"><p class="text-sm text-gray-400 py-2">No items yet. Add products to this bundle.</p></template>
                    <div class="space-y-3">
                        <template x-for="(item, i) in bundleItems" :key="i">
                            <div class="grid grid-cols-12 gap-3 items-center bg-gray-50 rounded-xl px-4 py-3">
                                <div class="col-span-5">
                                    <label class="text-xs text-gray-500 mb-1 block">Product *</label>
                                    <select :name="`bundle_items[${i}][product_id]`" x-model="item.product_id"
                                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                        <option value="">Select product…</option>
                                        <template x-for="p in allProducts" :key="p.id">
                                            <option :value="p.id" x-text="p.name + ' — ৳' + p.price"></option>
                                        </template>
                                    </select>
                                </div>
                                <div class="col-span-2">
                                    <label class="text-xs text-gray-500 mb-1 block">Qty</label>
                                    <input type="number" :name="`bundle_items[${i}][quantity]`" x-model="item.quantity" min="1"
                                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </div>
                                <div class="col-span-3">
                                    <label class="text-xs text-gray-500 mb-1 block">Discount %</label>
                                    <input type="number" :name="`bundle_items[${i}][discount_pct]`" x-model="item.discount_pct" min="0" max="100" step="0.5"
                                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </div>
                                <div class="col-span-2 flex items-end justify-end pb-0.5">
                                    <button type="button" @click="bundleItems.splice(i,1)" class="text-red-400 hover:text-red-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                    <p class="text-xs text-gray-400 mt-3">Bundle price set in Pricing applies to the whole bundle. Item discount reduces individual item display price.</p>
                </div>

                
                <div class="bg-white rounded-2xl shadow-sm p-6" x-show="productType === 'digital'" x-cloak>
                    <h3 class="font-semibold text-gray-800 mb-4">Digital File</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Upload File *</label>
                            <input type="file" name="download_file"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm">
                            <p class="text-xs text-gray-400 mt-1">Max 100MB. PDF, ZIP, MP3, MP4, etc.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Download Expiry (days)</label>
                            <input type="number" name="download_expiry_days" value="<?php echo e(old('download_expiry_days')); ?>" min="1" placeholder="e.g. 30 — leave blank for no limit"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>
                </div>

                
                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-800">Specifications <span class="text-xs text-gray-400 font-normal ml-1">(optional)</span></h3>
                        <button type="button" @click="addSpec()" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg> Add Row
                        </button>
                    </div>
                    <template x-if="specs.length === 0">
                        <p class="text-sm text-gray-400 py-2">No specs. Add rows for Material, Weight, Dimensions, etc.</p>
                    </template>
                    <div class="space-y-2">
                        <template x-for="(spec, i) in specs" :key="i">
                            <div class="grid grid-cols-12 gap-2 items-center" x-data="{ keyQ: spec.key, showKeySug: false }">
                                <div class="col-span-5 relative">
                                    <input type="text" :name="`specs[${i}][key]`" x-model="spec.key" placeholder="e.g. Material"
                                        @input="keyQ=spec.key; showKeySug=true"
                                        @blur="setTimeout(()=>showKeySug=false,200)"
                                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <div x-show="showKeySug && specKeyFilter(keyQ).length > 0" x-cloak
                                        class="absolute z-10 bg-white border border-gray-200 rounded-xl shadow-lg mt-1 w-full">
                                        <template x-for="name in specKeyFilter(keyQ)" :key="name">
                                            <button type="button" @click="spec.key=name; keyQ=name; showKeySug=false"
                                                class="block w-full text-left px-3 py-1.5 text-sm hover:bg-indigo-50 text-gray-700" x-text="name"></button>
                                        </template>
                                    </div>
                                </div>
                                <div class="col-span-6">
                                    <input type="text" :name="`specs[${i}][value]`" x-model="spec.value" placeholder="e.g. 100% Cotton"
                                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </div>
                                <div class="col-span-1 flex justify-end">
                                    <button type="button" @click="specs.splice(i,1)" class="text-red-400 hover:text-red-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                
                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-800">FAQs <span class="text-xs text-gray-400 font-normal ml-1">(optional)</span></h3>
                        <button type="button" @click="addFaq()" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg> Add FAQ
                        </button>
                    </div>
                    <template x-if="faqs.length === 0">
                        <p class="text-sm text-gray-400 py-2">No FAQs yet. Add common questions about this product.</p>
                    </template>
                    <div class="space-y-4">
                        <template x-for="(faq, i) in faqs" :key="i">
                            <div class="border border-gray-100 rounded-xl p-4 relative">
                                <button type="button" @click="faqs.splice(i,1)" class="absolute top-3 right-3 text-red-400 hover:text-red-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                                <div class="mb-2">
                                    <label class="text-xs font-medium text-gray-600 mb-1 block">Question *</label>
                                    <input type="text" :name="`faqs[${i}][question]`" x-model="faq.question" placeholder="e.g. Is this machine washable?"
                                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-gray-600 mb-1 block">Answer *</label>
                                    <textarea :name="`faqs[${i}][answer]`" x-model="faq.answer" rows="2" placeholder="Your answer here…"
                                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none"></textarea>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                
                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <h3 class="font-semibold text-gray-800 mb-4">Tags</h3>
                    <template x-for="tag in selectedTags" :key="tag.id">
                        <input type="hidden" name="tag_ids[]" :value="tag.id">
                    </template>
                    <div class="flex flex-wrap gap-2 mb-3" x-show="selectedTags.length > 0">
                        <template x-for="tag in selectedTags" :key="tag.id">
                            <span class="inline-flex items-center gap-1 bg-indigo-100 text-indigo-700 px-3 py-1 rounded-full text-xs font-medium">
                                <span x-text="tag.name"></span>
                                <button type="button" @click="removeTag(tag.id)" class="hover:text-indigo-900 font-bold">×</button>
                            </span>
                        </template>
                    </div>
                    <div class="relative">
                        <input type="text" x-model="tagQuery" placeholder="Search tags or type a new one and press Enter…"
                            @focus="showTagDropdown=true" @blur="setTimeout(()=>showTagDropdown=false,200)"
                            @keydown.enter.prevent="createTag()"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <div x-show="showTagDropdown && (tagSuggestions.length > 0 || (tagQuery.trim() && !exactTagMatch))" x-cloak
                            class="absolute z-10 bg-white border border-gray-200 rounded-xl shadow-lg mt-1 w-full max-h-48 overflow-y-auto">
                            <template x-for="tag in tagSuggestions" :key="tag.id">
                                <button type="button" @click="addTag(tag)"
                                    class="block w-full text-left px-4 py-2 text-sm hover:bg-indigo-50 text-gray-700" x-text="tag.name"></button>
                            </template>
                            <button type="button" x-show="tagQuery.trim() && !exactTagMatch" @click="createTag()" :disabled="creatingTag"
                                class="block w-full text-left px-4 py-2 text-sm text-indigo-600 hover:bg-indigo-50 font-medium border-t border-gray-100">
                                + Create tag "<span x-text="tagQuery.trim()"></span>"
                            </button>
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">Select an existing tag, or type a new name and press Enter to create it.</p>
                </div>

            </div>

            
            <div class="space-y-6">

                
                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <h3 class="font-semibold text-gray-800 mb-4">Pricing & Inventory</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                <span x-text="productType === 'bundle' ? 'Bundle Price (৳) *' : 'Price (৳) *'"></span>
                            </label>
                            <input type="number" name="price" value="<?php echo e(old('price')); ?>" step="0.01" min="0"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 <?php $__errorArgs = ['price'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-400 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                            <?php $__errorArgs = ['price'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div x-show="productType !== 'bundle'">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sale Price (৳)</label>
                            <input type="number" name="sale_price" value="<?php echo e(old('sale_price')); ?>" step="0.01" min="0"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div x-show="productType !== 'bundle' && productType !== 'variable'">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Stock *</label>
                            <input type="number" name="stock" value="<?php echo e(old('stock', 0)); ?>" min="0"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div x-show="productType === 'variable'" x-cloak>
                            <p class="text-xs text-gray-400 bg-gray-50 rounded-lg p-3">Stock is managed per size variant above.</p>
                        </div>
                        <div x-show="productType === 'bundle'" x-cloak>
                            <p class="text-xs text-gray-400 bg-gray-50 rounded-lg p-3">Bundle stock is automatic — available while all items have stock.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Weight (kg)</label>
                            <input type="number" name="weight" value="<?php echo e(old('weight')); ?>" step="0.01" min="0" placeholder="Optional"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>
                </div>

                
                <div class="bg-white rounded-2xl shadow-sm p-6"
                    x-data="{
                        allSubs: <?php echo e(Js::from($allSubcategories)); ?>,
                        categoryId: '<?php echo e(old('category_id')); ?>',
                        subcategoryId: '<?php echo e(old('subcategory_id')); ?>',
                        get subcategories() { return this.allSubs[this.categoryId] || [] },
                        onCategoryChange() { if (!this.subcategories.find(s => s.id == this.subcategoryId)) this.subcategoryId = ''; }
                    }">
                    <h3 class="font-semibold text-gray-800 mb-4">Organisation</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Brand</label>
                            <select name="brand_id" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">No Brand</option>
                                <?php $__currentLoopData = $brands; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $brand): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($brand->id); ?>" <?php echo e(old('brand_id') == $brand->id ? 'selected' : ''); ?>><?php echo e($brand->name); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Category *</label>
                            <select name="category_id" x-model="categoryId" @change="onCategoryChange()"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 <?php $__errorArgs = ['category_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-400 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                                <option value="">Select Category</option>
                                <?php $__currentLoopData = $categoryTree; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($cat->id); ?>"><?php echo e($cat->name); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <?php $__errorArgs = ['category_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div x-show="subcategories.length > 0" x-cloak>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Subcategory</label>
                            <select name="subcategory_id" x-model="subcategoryId"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">None</option>
                                <template x-for="s in subcategories" :key="s.id">
                                    <option :value="s.id" x-text="s.name"></option>
                                </template>
                            </select>
                        </div>
                        <div class="flex items-center space-x-3">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="is_active" value="1" checked class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-indigo-600 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full"></div>
                            </label>
                            <span class="text-sm text-gray-700">Active</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="is_featured" value="1" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-indigo-600 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full"></div>
                            </label>
                            <span class="text-sm text-gray-700">Featured</span>
                        </div>
                    </div>
                </div>

                <button type="submit" class="w-full bg-indigo-600 text-white py-3 rounded-xl font-semibold hover:bg-indigo-700 transition">
                    Create Product
                </button>
            </div>
        </div>

        <?php echo $__env->make('admin.products._seo_fields', ['product' => null], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </form>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
    function generateProductSku() {
        const nameInput = document.getElementById('product-name-input');
        const skuInput = document.getElementById('product-sku-input');
        const base = (nameInput.value || 'PROD')
            .toUpperCase()
            .replace(/[^A-Z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '')
            .split('-')
            .filter(Boolean)
            .slice(0, 3)
            .join('-');
        const rand = Math.floor(1000 + Math.random() * 9000);
        skuInput.value = `${base || 'PROD'}-${rand}`;
    }
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\completeEcom\resources\views/admin/products/create.blade.php ENDPATH**/ ?>