@extends('layouts.admin')
@section('title', 'Edit — '.$product->name)

@section('content')
<div class="max-w-5xl">
    <a href="{{ route('admin.products.index') }}" class="text-indigo-600 hover:text-indigo-700 text-sm flex items-center space-x-2 mb-6">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        <span>Back to Products</span>
    </a>
    @if(session('success'))<div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">{{ session('success') }}</div>@endif
    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6">
        <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $e)<li class="text-sm text-red-600">{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <form action="{{ route('admin.products.update', $product) }}" method="POST" enctype="multipart/form-data"
        x-data="{
            productType: '{{ old('type', $product->type ?? 'simple') }}',
            colors: {{ Js::from($variantMatrix['colors']) }},
            sizes: {{ Js::from($variantMatrix['sizes']) }},
            combinations: {{ Js::from($variantMatrix['combinations']) }},
            bundleItems: {{ Js::from($product->bundleItems->map(fn($b) => ['product_id' => $b->item_product_id, 'quantity' => $b->quantity, 'discount_pct' => $b->discount_pct])->toArray()) }},
            faqs: {{ Js::from($product->faqs->map(fn($f) => ['question' => $f->question, 'answer' => $f->answer])->toArray()) }},
            specs: {{ Js::from($product->specs->map(fn($s) => ['key' => $s->spec_key, 'value' => $s->spec_value])->toArray()) }},
            selectedTags: {{ Js::from($product->tags->map(fn($t) => ['id' => $t->id, 'name' => $t->name])->toArray()) }},
            tagQuery: '',
            showTagDropdown: false,
            creatingTag: false,
            allTags: {{ Js::from($allTags) }},
            allProducts: {{ Js::from($simpleProducts) }},
            attributeNames: {{ Js::from($attributeNames) }},
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
                    const res = await fetch('{{ route('admin.tags.quick-create') }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
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
            addColor() { this.colors.push({ id:'', name:'', hex_code:'#6366f1', is_active:true }); this.rebuildCombinations(); },
            removeColor(i) {
                this.colors.splice(i, 1);
                this.combinations = this.combinations
                    .filter(c => c.color_index !== i)
                    .map(c => ({ ...c, color_index: (c.color_index !== null && c.color_index > i) ? c.color_index - 1 : c.color_index }));
                this.rebuildCombinations();
            },
            addSize() { this.sizes.push({ id:'', name:'', is_active:true }); this.rebuildCombinations(); },
            removeSize(i) {
                this.sizes.splice(i, 1);
                this.combinations = this.combinations
                    .filter(c => c.size_index !== i)
                    .map(c => ({ ...c, size_index: (c.size_index !== null && c.size_index > i) ? c.size_index - 1 : c.size_index }));
                this.rebuildCombinations();
            },
            rebuildCombinations() {
                const colorIdxs = this.colors.length ? this.colors.map((_, i) => i) : [null];
                const sizeIdxs = this.sizes.length ? this.sizes.map((_, i) => i) : [null];
                const wanted = [];
                for (const ci of colorIdxs) {
                    for (const si of sizeIdxs) {
                        if (ci === null && si === null) continue;
                        wanted.push(ci + '|' + si);
                    }
                }
                const existingByKey = {};
                this.combinations.forEach(c => { existingByKey[c.color_index + '|' + c.size_index] = c; });
                this.combinations = wanted.map(key => {
                    if (existingByKey[key]) return existingByKey[key];
                    const [ciRaw, siRaw] = key.split('|');
                    return {
                        id: '', color_index: ciRaw === 'null' ? null : parseInt(ciRaw),
                        size_index: siRaw === 'null' ? null : parseInt(siRaw),
                        sku: '', price: '', stock: 0, is_active: true,
                    };
                });
            },
            addBundleItem() { this.bundleItems.push({ product_id:'', quantity:1, discount_pct:0 }); },
            addFaq() { this.faqs.push({ question:'', answer:'' }); },
            addSpec() { this.specs.push({ key:'', value:'' }); },
            specKeyFilter(q) { return this.attributeNames.filter(n => n.toLowerCase().includes(q.toLowerCase())).slice(0,6); }
        }"
        @submit="if (productType === 'variable' && combinations.length === 0) { alert('Add at least one color or size for this variable product.'); $event.preventDefault(); }
                 else if (productType === 'bundle' && bundleItems.length === 0) { alert('Add at least one item to this bundle.'); $event.preventDefault(); }">
        @csrf @method('PUT')
        <input type="hidden" name="type" :value="productType">

        {{-- Product Type --}}
        <div class="bg-white rounded-2xl shadow-sm p-6 mb-6">
            <h3 class="font-semibold text-gray-800 mb-4">Product Type</h3>
            <div class="grid grid-cols-4 gap-3">
                @foreach(['simple'=>['Simple','Fixed price & stock','M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4'], 'variable'=>['Variable','Sizes & colors','M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01'], 'bundle'=>['Bundle','Group of products','M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'], 'digital'=>['Digital','Downloadable file','M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z']] as $val => [$label, $desc, $icon])
                <label class="flex flex-col items-center p-4 border-2 rounded-xl cursor-pointer transition"
                    :class="productType === '{{ $val }}' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-gray-300'"
                    @click="productType='{{ $val }}'">
                    <svg class="w-5 h-5 mb-1" :class="productType==='{{ $val }}' ? 'text-indigo-600' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}"/></svg>
                    <span class="text-sm font-semibold" :class="productType==='{{ $val }}' ? 'text-indigo-700' : 'text-gray-700'">{{ $label }}</span>
                    <span class="text-xs text-gray-400 text-center mt-0.5">{{ $desc }}</span>
                </label>
                @endforeach
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">

                {{-- Basic Info --}}
                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <h3 class="font-semibold text-gray-800 mb-4">Product Information</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Product Name *</label>
                            <input type="text" name="name" id="product-name-input" value="{{ old('name', $product->name) }}"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">SKU</label>
                            <div class="flex gap-2">
                                <input type="text" name="sku" id="product-sku-input" value="{{ old('sku', $product->sku) }}" placeholder="e.g. TSHIRT-BLU-001"
                                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('sku') border-red-400 @enderror">
                                <button type="button" onclick="generateProductSku()"
                                    class="flex-shrink-0 px-3 rounded-xl border border-gray-200 text-xs font-medium text-gray-600 hover:bg-gray-50 transition whitespace-nowrap">
                                    Generate
                                </button>
                            </div>
                            @error('sku')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div x-data="{ val: {{ Js::from(old('short_description', $product->short_description ?? '')) }} }">
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
                            @include('partials.rich-editor', ['name' => 'description', 'value' => old('description', $product->description ?? ''), 'id' => 'description', 'placeholder' => 'Describe the product in detail — features, materials, sizing, care instructions…'])
                            <p class="text-xs text-gray-400 mt-1">Use headings, lists and images to lay the description out exactly how you want customers to read it.</p>
                        </div>
                    </div>
                </div>

                {{-- Images --}}
                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <h3 class="font-semibold text-gray-800 mb-4">Images</h3>

                    {{-- Main image --}}
                    <div x-data="{
                        preview: {{ Js::from($product->image ? Storage::url($product->image) : null) }},
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
                        <p class="text-xs text-gray-400 mt-1">Drop a new image to replace the current one, or click the × to clear the preview.</p>
                        @error('image')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    {{-- Existing gallery: drag to reorder, click × to mark for deletion --}}
                    @if($product->images->isNotEmpty())
                    <div class="mt-5" x-data="{
                        images: {{ Js::from($product->images->map(fn($i) => ['id' => $i->id, 'url' => \Illuminate\Support\Facades\Storage::url($i->image)])->values()->toArray()) }},
                        dragIndex: null,
                        toDelete: [],
                        onDrop(i) {
                            if (this.dragIndex === null || this.dragIndex === i) return;
                            const moved = this.images.splice(this.dragIndex, 1)[0];
                            this.images.splice(i, 0, moved);
                            this.dragIndex = null;
                        },
                        toggleDelete(id) {
                            this.toDelete = this.toDelete.includes(id) ? this.toDelete.filter(x => x !== id) : [...this.toDelete, id];
                        }
                    }">
                        <p class="text-xs text-gray-500 mb-2">Gallery — drag thumbnails to reorder, click × to remove</p>
                        <div class="flex flex-wrap gap-3">
                            <template x-for="(img, i) in images" :key="img.id">
                                <div class="relative group cursor-move" draggable="true"
                                    @dragstart="dragIndex = i" @dragover.prevent @drop.prevent="onDrop(i)"
                                    :class="toDelete.includes(img.id) ? 'opacity-30' : ''">
                                    <img :src="img.url" class="w-16 h-16 object-cover rounded-lg border border-gray-100">
                                    <button type="button" @click="toggleDelete(img.id)"
                                        :class="toDelete.includes(img.id) ? 'opacity-100 bg-gray-500' : 'opacity-0 group-hover:opacity-100 bg-red-500'"
                                        class="absolute -top-1.5 -right-1.5 text-white rounded-full w-5 h-5 text-xs flex items-center justify-center transition shadow"
                                        x-text="toDelete.includes(img.id) ? '↺' : '×'"></button>
                                    <input type="hidden" name="delete_images[]" :value="img.id" x-bind:disabled="!toDelete.includes(img.id)">
                                    <input type="hidden" name="existing_image_order[]" :value="img.id">
                                </div>
                            </template>
                        </div>
                    </div>
                    @endif

                    {{-- New gallery images --}}
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
                        <label class="block text-sm font-medium text-gray-700 mb-1">Add Gallery Images</label>
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
                        @error('images.*')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                {{-- Variable: Colors / Sizes / Combinations --}}
                @include('admin.products._variant_matrix')

                {{-- Bundle Items --}}
                <div class="bg-white rounded-2xl shadow-sm p-6" x-show="productType === 'bundle'" x-cloak>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-800">Bundle Items</h3>
                        <button type="button" @click="addBundleItem()" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg> Add Item
                        </button>
                    </div>
                    <template x-if="bundleItems.length === 0"><p class="text-sm text-gray-400 py-2">No bundle items.</p></template>
                    <div class="space-y-3">
                        <template x-for="(item, i) in bundleItems" :key="i">
                            <div class="grid grid-cols-12 gap-3 items-end bg-gray-50 rounded-xl px-4 py-3">
                                <div class="col-span-5">
                                    <label class="text-xs text-gray-500 mb-1 block">Product *</label>
                                    <select :name="`bundle_items[${i}][product_id]`" x-model="item.product_id"
                                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                        <option value="">Select…</option>
                                        <template x-for="p in allProducts" :key="p.id">
                                            <option :value="p.id" :selected="item.product_id == p.id" x-text="p.name + ' — ৳' + p.price"></option>
                                        </template>
                                    </select>
                                </div>
                                <div class="col-span-2">
                                    <label class="text-xs text-gray-500 mb-1 block">Qty</label>
                                    <input type="number" :name="`bundle_items[${i}][quantity]`" x-model="item.quantity" min="1" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </div>
                                <div class="col-span-3">
                                    <label class="text-xs text-gray-500 mb-1 block">Discount %</label>
                                    <input type="number" :name="`bundle_items[${i}][discount_pct]`" x-model="item.discount_pct" min="0" max="100" step="0.5" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </div>
                                <div class="col-span-2 flex justify-end">
                                    <button type="button" @click="bundleItems.splice(i,1)" class="text-red-400 hover:text-red-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Digital --}}
                <div class="bg-white rounded-2xl shadow-sm p-6" x-show="productType === 'digital'" x-cloak>
                    <h3 class="font-semibold text-gray-800 mb-4">Digital File</h3>
                    <div class="space-y-4">
                        @if($product->download_file)
                        <div class="bg-green-50 border border-green-200 rounded-xl p-3 text-sm text-green-700">
                            Current file: <span class="font-mono">{{ basename($product->download_file) }}</span>
                        </div>
                        @endif
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $product->download_file ? 'Replace File' : 'Upload File *' }}</label>
                            <input type="file" name="download_file" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Download Expiry (days)</label>
                            <input type="number" name="download_expiry_days" value="{{ old('download_expiry_days', $product->download_expiry_days) }}" min="1" placeholder="Blank = no limit"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>
                </div>

                {{-- Specifications --}}
                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-800">Specifications</h3>
                        <button type="button" @click="addSpec()" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg> Add
                        </button>
                    </div>
                    <template x-if="specs.length === 0"><p class="text-sm text-gray-400 py-2">No specifications yet.</p></template>
                    <div class="space-y-2">
                        <template x-for="(spec, i) in specs" :key="i">
                            <div class="grid grid-cols-12 gap-2 items-center" x-data="{ keyQ: spec.key, showKeySug: false }">
                                <div class="col-span-5 relative">
                                    <input type="text" :name="`specs[${i}][key]`" x-model="spec.key" placeholder="e.g. Material"
                                        @input="keyQ=spec.key; showKeySug=true" @blur="setTimeout(()=>showKeySug=false,200)"
                                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <div x-show="showKeySug && specKeyFilter(keyQ).length > 0" x-cloak class="absolute z-10 bg-white border border-gray-200 rounded-xl shadow-lg mt-1 w-full">
                                        <template x-for="name in specKeyFilter(keyQ)" :key="name">
                                            <button type="button" @click="spec.key=name; keyQ=name; showKeySug=false" class="block w-full text-left px-3 py-1.5 text-sm hover:bg-indigo-50 text-gray-700" x-text="name"></button>
                                        </template>
                                    </div>
                                </div>
                                <div class="col-span-6"><input type="text" :name="`specs[${i}][value]`" x-model="spec.value" placeholder="Value" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"></div>
                                <div class="col-span-1 flex justify-end"><button type="button" @click="specs.splice(i,1)" class="text-red-400 hover:text-red-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button></div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- FAQs --}}
                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-800">FAQs</h3>
                        <button type="button" @click="addFaq()" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg> Add FAQ
                        </button>
                    </div>
                    <template x-if="faqs.length === 0"><p class="text-sm text-gray-400 py-2">No FAQs yet.</p></template>
                    <div class="space-y-4">
                        <template x-for="(faq, i) in faqs" :key="i">
                            <div class="border border-gray-100 rounded-xl p-4 relative">
                                <button type="button" @click="faqs.splice(i,1)" class="absolute top-3 right-3 text-red-400 hover:text-red-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                                <div class="mb-2">
                                    <label class="text-xs font-medium text-gray-600 mb-1 block">Question</label>
                                    <input type="text" :name="`faqs[${i}][question]`" x-model="faq.question" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-gray-600 mb-1 block">Answer</label>
                                    <textarea :name="`faqs[${i}][answer]`" x-model="faq.answer" rows="2" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none"></textarea>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Tags --}}
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
                                <button type="button" @click="addTag(tag)" class="block w-full text-left px-4 py-2 text-sm hover:bg-indigo-50 text-gray-700" x-text="tag.name"></button>
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

            {{-- Right column --}}
            <div class="space-y-6">

                {{-- Pricing --}}
                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <h3 class="font-semibold text-gray-800 mb-4">Pricing & Inventory</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Price (৳) *</label>
                            <input type="number" name="price" value="{{ old('price', $product->price) }}" step="0.01" min="0"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div x-show="productType !== 'bundle'">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sale Price (৳)</label>
                            <input type="number" name="sale_price" value="{{ old('sale_price', $product->sale_price) }}" step="0.01" min="0"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div x-show="productType !== 'bundle' && productType !== 'variable'">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Stock</label>
                            <input type="number" name="stock" value="{{ old('stock', $product->stock) }}" min="0"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div x-show="productType === 'variable'" x-cloak>
                            <p class="text-xs text-gray-400 bg-gray-50 rounded-lg p-3">Stock is managed per color/size combination above.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Weight (kg)</label>
                            <input type="number" name="weight" value="{{ old('weight', $product->weight) }}" step="0.01" min="0"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>
                </div>

                {{-- Organisation --}}
                <div class="bg-white rounded-2xl shadow-sm p-6"
                    x-data="{
                        allSubs: {{ Js::from($allSubcategories) }},
                        categoryId: '{{ old('category_id', $product->category_id) }}',
                        subcategoryId: '{{ old('subcategory_id', $product->subcategory_id) }}',
                        get subcategories() { return this.allSubs[this.categoryId] || [] },
                        onCategoryChange() { if (!this.subcategories.find(s => s.id == this.subcategoryId)) this.subcategoryId = ''; }
                    }">
                    <h3 class="font-semibold text-gray-800 mb-4">Organisation</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Brand</label>
                            <select name="brand_id" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">No Brand</option>
                                @foreach($brands as $brand)
                                <option value="{{ $brand->id }}" {{ old('brand_id', $product->brand_id) == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Category *</label>
                            <select name="category_id" x-model="categoryId" @change="onCategoryChange()"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">Select Category</option>
                                @foreach($categoryTree as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
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
                                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }} class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-indigo-600 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full"></div>
                            </label>
                            <span class="text-sm text-gray-700">Active</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $product->is_featured) ? 'checked' : '' }} class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-indigo-600 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full"></div>
                            </label>
                            <span class="text-sm text-gray-700">Featured</span>
                        </div>
                    </div>
                </div>

                {{-- Info summary --}}
                <div class="bg-gray-50 rounded-2xl p-4 text-xs text-gray-500 space-y-1.5">
                    <div class="flex justify-between"><span>Slug</span><span class="font-mono text-gray-600 truncate max-w-32">{{ $product->slug }}</span></div>
                    <div class="flex justify-between"><span>Colors</span><span class="font-semibold text-gray-700">{{ $product->colors->count() }}</span></div>
                    <div class="flex justify-between"><span>Sizes</span><span class="font-semibold text-gray-700">{{ $product->sizes->count() }}</span></div>
                    <div class="flex justify-between"><span>Combinations</span><span class="font-semibold text-gray-700">{{ $product->combinations->count() }}</span></div>
                    <div class="flex justify-between"><span>Gallery</span><span class="font-semibold text-gray-700">{{ $product->images->count() }}</span></div>
                    <div class="flex justify-between"><span>FAQs</span><span class="font-semibold text-gray-700">{{ $product->faqs->count() }}</span></div>
                    <div class="flex justify-between"><span>Specs</span><span class="font-semibold text-gray-700">{{ $product->specs->count() }}</span></div>
                    <div class="flex justify-between"><span>Tags</span><span class="font-semibold text-gray-700">{{ $product->tags->count() }}</span></div>
                    <div class="flex justify-between"><span>Views</span><span class="font-semibold text-gray-700">{{ number_format($product->views) }}</span></div>
                </div>

                <button type="submit" class="w-full bg-indigo-600 text-white py-3 rounded-xl font-semibold hover:bg-indigo-700 transition">
                    Update Product
                </button>
            </div>
        </div>

        @include('admin.products._seo_fields')
    </form>
</div>

@push('scripts')
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
@endpush
@endsection
