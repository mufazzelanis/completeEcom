@extends('layouts.admin')
@section('title', 'Add Product')

@section('content')
<div class="max-w-5xl">
    <a href="{{ route('admin.products.index') }}" class="text-indigo-600 hover:text-indigo-700 text-sm flex items-center space-x-2 mb-6">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        <span>Back to Products</span>
    </a>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6">
        <ul class="list-disc list-inside space-y-1">
            @foreach($errors->all() as $e)<li class="text-sm text-red-600">{{ $e }}</li>@endforeach
        </ul>
    </div>
    @endif

    @php $oldSelectedTags = $allTags->whereIn('id', array_map('intval', old('tag_ids', [])))->values(); @endphp
    <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data"
        x-data="{
            productType: '{{ old('type','simple') }}',
            variants: [],
            colors: [],
            bundleItems: [],
            faqs: [],
            specs: [],
            selectedTags: {{ Js::from($oldSelectedTags) }},
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
            addVariant() { this.variants.push({ name:'', sku:'', price:'', stock:0, is_active:true }); },
            addColor() { this.colors.push({ name:'', hex_code:'#6366f1', stock:'', is_active:true }); },
            addBundleItem() { this.bundleItems.push({ product_id:'', quantity:1, discount_pct:0 }); },
            addFaq() { this.faqs.push({ question:'', answer:'' }); },
            addSpec() { this.specs.push({ key:'', value:'' }); },
            specKeyFilter(q) { return this.attributeNames.filter(n => n.toLowerCase().includes(q.toLowerCase())).slice(0,6); }
        }">
        @csrf
        <input type="hidden" name="type" :value="productType">

        {{-- Product Type Selector --}}
        <div class="bg-white rounded-2xl shadow-sm p-6 mb-6">
            <h3 class="font-semibold text-gray-800 mb-4">Product Type</h3>
            <div class="grid grid-cols-4 gap-3">
                @foreach(['simple'=>['Simple','Regular product with fixed price and stock','M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4'], 'variable'=>['Variable','Multiple sizes and color options','M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01'], 'bundle'=>['Bundle','Group of products sold together','M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'], 'digital'=>['Digital','Downloadable file (PDF, software, etc.)','M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z']] as $val => [$label, $desc, $icon])
                <label class="flex flex-col items-center p-4 border-2 rounded-xl cursor-pointer transition"
                    :class="productType === '{{ $val }}' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-gray-300'"
                    @click="productType='{{ $val }}'">
                    <svg class="w-6 h-6 mb-2" :class="productType==='{{ $val }}' ? 'text-indigo-600' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}"/>
                    </svg>
                    <span class="text-sm font-semibold" :class="productType==='{{ $val }}' ? 'text-indigo-700' : 'text-gray-700'">{{ $label }}</span>
                    <span class="text-xs text-gray-400 text-center mt-1">{{ $desc }}</span>
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
                            <input type="text" name="name" value="{{ old('name') }}"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('name') border-red-400 @enderror">
                            @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">SKU</label>
                            <input type="text" name="sku" value="{{ old('sku') }}"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Short Description</label>
                            <textarea name="short_description" rows="2"
                                class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('short_description') }}</textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Full Description</label>
                            <textarea name="description" rows="6"
                                class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('description') }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Images --}}
                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <h3 class="font-semibold text-gray-800 mb-4">Images</h3>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Main Image</label>
                        <input type="file" name="image" accept="image/*" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm">
                    </div>
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Gallery Images</label>
                        <input type="file" name="images[]" accept="image/*" multiple class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm">
                    </div>
                </div>

                {{-- Variable: Variants --}}
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

                {{-- Variable: Colors --}}
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

                {{-- Bundle Items --}}
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

                {{-- Digital File --}}
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
                            <input type="number" name="download_expiry_days" value="{{ old('download_expiry_days') }}" min="1" placeholder="e.g. 30 — leave blank for no limit"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>
                </div>

                {{-- Specifications / Attributes --}}
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

                {{-- FAQs --}}
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

            {{-- Right column --}}
            <div class="space-y-6">

                {{-- Pricing & Inventory --}}
                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <h3 class="font-semibold text-gray-800 mb-4">Pricing & Inventory</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                <span x-text="productType === 'bundle' ? 'Bundle Price (৳) *' : 'Price (৳) *'"></span>
                            </label>
                            <input type="number" name="price" value="{{ old('price') }}" step="0.01" min="0"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('price') border-red-400 @enderror">
                            @error('price')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div x-show="productType !== 'bundle'">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sale Price (৳)</label>
                            <input type="number" name="sale_price" value="{{ old('sale_price') }}" step="0.01" min="0"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div x-show="productType !== 'bundle' && productType !== 'variable'">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Stock *</label>
                            <input type="number" name="stock" value="{{ old('stock', 0) }}" min="0"
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
                            <input type="number" name="weight" value="{{ old('weight') }}" step="0.01" min="0" placeholder="Optional"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>
                </div>

                {{-- Organisation --}}
                <div class="bg-white rounded-2xl shadow-sm p-6"
                    x-data="{
                        allSubs: {{ Js::from($allSubcategories) }},
                        categoryId: '{{ old('category_id') }}',
                        subcategoryId: '{{ old('subcategory_id') }}',
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
                                <option value="{{ $brand->id }}" {{ old('brand_id') == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Category *</label>
                            <select name="category_id" x-model="categoryId" @change="onCategoryChange()"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('category_id') border-red-400 @enderror">
                                <option value="">Select Category</option>
                                @foreach($categoryTree as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                            @error('category_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
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

        @include('admin.products._seo_fields', ['product' => null])
    </form>
</div>
@endsection
