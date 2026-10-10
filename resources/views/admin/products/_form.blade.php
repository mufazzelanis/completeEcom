{{-- Shared by create.blade.php and edit.blade.php — the wrapping <form x-data="..."> (which
     differs only in its seed values: old() on create, $product-> on edit) lives in each of
     those two files; this partial is everything inside it. Mirrors the established pattern
     already used for seller/products/_form.blade.php and admin/landing-pages/_form.blade.php.
     $product is unset on create, an Eloquent model on edit — every reference below is guarded
     with isset($product). --}}
@once
@push('styles')
<style>
    /* Staggered entrance for the form's cards (animation-delay set per card via inline
       style="animation-delay:…"). backwards fill-mode means each card is invisible from
       0ms, not just from its own delay onward, so nothing flashes at full opacity first. */
    @keyframes formCardIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }
    .form-card { animation: formCardIn .35s ease-out backwards; }
</style>
@endpush
@push('scripts')
<script>
    // Save/Update without a full page reload (or the Turbo-visit body-swap, which
    // reruns every animation and resets scroll even though it's not a literal browser
    // reload) — intercepts the submit, posts via fetch, and either stays exactly where
    // it is (update: just a toast, URL silently kept in sync with any slug change) or
    // moves on smoothly (create: nothing meaningful to stay on, so Turbo.visit to the
    // index once the product actually exists). DOMContentLoaded because Turbo re-swaps
    // <body> on every visit to this page — app.js re-dispatches that event on every
    // turbo:load too, so this (same as the Settings page's own AJAX-save script) rebinds
    // correctly each time without a special turbo:load listener of its own.
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('product-form');
        const submitBtn = document.getElementById('product-submit-btn');
        if (!form || !submitBtn) return;
        const originalLabel = submitBtn.innerHTML;
        const isUpdate = !!form.querySelector('input[name="_method"][value="PUT"]');
        const knownErrorFields = ['name', 'sku', 'image', 'price', 'category_id'];

        function clearErrors() {
            knownErrorFields.forEach((f) => {
                const el = document.getElementById('error-' + f);
                if (el) { el.textContent = ''; el.classList.add('hidden'); }
            });
            document.getElementById('ajax-error-summary')?.classList.add('hidden');
        }

        function showErrors(errors) {
            const leftover = [];
            Object.entries(errors).forEach(([field, messages]) => {
                const msg = Array.isArray(messages) ? messages[0] : messages;
                const el = knownErrorFields.includes(field) ? document.getElementById('error-' + field) : null;
                if (el) { el.textContent = msg; el.classList.remove('hidden'); }
                else leftover.push(msg);
            });
            const summary = document.getElementById('ajax-error-summary');
            if (summary && leftover.length > 0) {
                summary.innerHTML = '<ul class="list-disc list-inside space-y-1">' + leftover.map((m) => `<li>${m}</li>`).join('') + '</ul>';
                summary.classList.remove('hidden');
                summary.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            // Same checks the form's own x-on:submit already does — read directly via
            // Alpine's public $data() API rather than relying on listener-order between
            // that directive and this handler, so this works regardless of which attaches
            // first.
            const data = window.Alpine ? Alpine.$data(form) : null;
            if (data) {
                if (data.productType === 'variable' && data.combinations.length === 0) {
                    alert('Add at least one color or size for this variable product.');
                    return;
                }
                if (data.productType === 'bundle' && data.bundleItems.length === 0) {
                    alert('Add at least one item to this bundle.');
                    return;
                }
            }

            clearErrors();
            submitBtn.disabled = true;
            submitBtn.innerHTML = `
                <svg class="animate-spin -ml-1 mr-1.5 h-4 w-4 inline" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>${isUpdate ? 'Updating…' : 'Creating…'}`;

            try {
                const res = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: { 'Accept': 'application/json' },
                });

                if (res.status === 422) {
                    const body = await res.json();
                    showErrors(body.errors || {});
                    productFormToast(false, 'Please fix the errors below.');
                    return;
                }
                if (!res.ok) {
                    productFormToast(false, 'Something went wrong — please try again.');
                    return;
                }

                const result = await res.json();
                if (isUpdate) {
                    productFormToast(true, result.message || 'Saved successfully.');
                    if (result.redirect) window.history.replaceState(null, '', result.redirect);
                } else if (result.redirect && window.Turbo) {
                    window.Turbo.visit(result.redirect);
                } else {
                    productFormToast(true, result.message || 'Saved successfully.');
                }
            } catch (err) {
                productFormToast(false, 'Network error — please try again.');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalLabel;
            }
        });
    });

    function productFormToast(success, message) {
        document.getElementById('product-form-toast')?.remove();
        const toast = document.createElement('div');
        toast.id = 'product-form-toast';
        toast.className = `fixed top-20 right-5 z-[100] px-4 py-3 rounded-xl text-sm font-medium shadow-lg flex items-center gap-2 text-white ${success ? 'bg-green-600' : 'bg-red-600'}`;
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3500);
    }
</script>
@endpush
@endonce
@csrf
@if(isset($product))@method('PUT')@endif
<input type="hidden" name="type" :value="productType">

{{-- Product Type --}}
<div class="bg-white rounded-2xl shadow-sm p-6 mb-6 form-card" style="animation-delay:0ms">
    <h3 class="font-semibold text-gray-800 mb-4">Product Type</h3>
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
        @foreach(['simple'=>['Simple','Fixed price & stock','M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4'], 'variable'=>['Variable','Sizes & colors','M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01'], 'bundle'=>['Bundle','Group of products','M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'], 'digital'=>['Digital','Downloadable file','M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z']] as $val => [$label, $desc, $icon])
        <label class="flex flex-col items-center p-4 border-2 rounded-xl cursor-pointer transition-all duration-200"
            :class="productType === '{{ $val }}' ? 'border-indigo-500 bg-indigo-50 scale-[1.02] shadow-sm' : 'border-gray-200 hover:border-gray-300 hover:scale-[1.01]'"
            @click="productType='{{ $val }}'">
            <svg class="w-5 h-5 mb-1 transition-colors" :class="productType==='{{ $val }}' ? 'text-indigo-600' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}"/></svg>
            <span class="text-sm font-semibold" :class="productType==='{{ $val }}' ? 'text-indigo-700' : 'text-gray-700'">{{ $label }}</span>
            <span class="text-xs text-gray-400 text-center mt-0.5">{{ $desc }}</span>
        </label>
        @endforeach
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">

        @if(isset($product) && $product->approval_status === 'rejected' && $product->rejection_reason)
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm form-card" style="animation-delay:0ms">
            <strong>Rejected:</strong> {{ $product->rejection_reason }}
        </div>
        @endif

        {{-- Basic Info --}}
        <div class="bg-white rounded-2xl shadow-sm p-6 form-card" style="animation-delay:40ms">
            <div class="flex items-center gap-2 mb-4">
                <div class="w-7 h-7 bg-indigo-50 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <h3 class="font-semibold text-gray-800">Product Information</h3>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Product Name *</label>
                    <input type="text" name="name" id="product-name-input" x-model="name"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-shadow @error('name') border-red-400 @enderror">
                    <p id="error-name" class="text-red-500 text-xs mt-1 {{ $errors->has('name') ? '' : 'hidden' }}">{{ $errors->first('name') }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">SKU</label>
                    <div class="flex gap-2">
                        <input type="text" name="sku" id="product-sku-input" value="{{ old('sku', $product->sku ?? '') }}" placeholder="e.g. TSHIRT-BLU-001"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('sku') border-red-400 @enderror">
                        <button type="button" onclick="generateProductSku()"
                            class="flex-shrink-0 px-3 rounded-xl border border-gray-200 text-xs font-medium text-gray-600 hover:bg-gray-50 transition whitespace-nowrap">
                            Generate
                        </button>
                    </div>
                    <p id="error-sku" class="text-red-500 text-xs mt-1 {{ $errors->has('sku') ? '' : 'hidden' }}">{{ $errors->first('sku') }}</p>
                </div>
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-sm font-medium text-gray-700">Short Description</label>
                        <span class="text-xs" :class="shortDescription.length > 200 ? 'text-red-500' : 'text-gray-400'" x-text="shortDescription.length + ' / 200'"></span>
                    </div>
                    <textarea name="short_description" rows="2" x-model="shortDescription" maxlength="200" placeholder="A quick one- or two-line summary shown on listing cards and search results…"
                        class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                    <p class="text-xs text-gray-400 mt-1">Keep it short and punchy — this is what customers see before opening the full description.</p>
                </div>
                <div @editor-change.window="if ($event.detail.id === 'description') descriptionLength = $event.detail.text.length">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Description</label>
                    @include('partials.rich-editor', ['name' => 'description', 'value' => old('description', $product->description ?? ''), 'id' => 'description', 'placeholder' => 'Describe the product in detail — features, materials, sizing, care instructions…'])
                    <p class="text-xs text-gray-400 mt-1">Use headings, lists and images to lay the description out exactly how you want customers to read it.</p>
                </div>
            </div>
        </div>

        {{-- Images --}}
        <div class="bg-white rounded-2xl shadow-sm p-6 form-card" style="animation-delay:80ms">
            <div class="flex items-center gap-2 mb-4">
                <div class="w-7 h-7 bg-indigo-50 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M4 8h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <h3 class="font-semibold text-gray-800">Images</h3>
            </div>

            {{-- Main image --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Main Image</label>
                <div class="border-2 border-dashed border-gray-200 rounded-xl p-4 text-center hover:border-indigo-400 transition cursor-pointer"
                    @dragover.prevent @dragleave.prevent @drop.prevent="onFiles($event.dataTransfer.files)"
                    @click="$refs.mainImageInput.click()">
                    <template x-if="!preview">
                        <div class="py-4 text-gray-400">
                            <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M4 8h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <p class="text-sm">Click or drag an image here</p>
                            <p class="text-xs text-gray-300 mt-0.5">PNG, JPG up to 4MB — square works best (e.g. 1000×1000px); any shape is shown in full either way, never cropped</p>
                        </div>
                    </template>
                    <template x-if="preview">
                        <div class="relative inline-block">
                            <img :src="preview" class="w-28 h-28 object-cover rounded-xl border border-gray-100 mx-auto">
                            <button type="button" @click.stop="clearImage()"
                                class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm shadow hover:bg-red-600">×</button>
                        </div>
                    </template>
                </div>
                <input type="file" name="image" x-ref="mainImageInput" accept="image/*" class="hidden" @change="onFiles($event.target.files)">
                @if(isset($product))<p class="text-xs text-gray-400 mt-1">Drop a new image to replace the current one, or click the × to clear the preview.</p>@endif
                <p id="error-image" class="text-red-500 text-xs mt-1 {{ $errors->has('image') ? '' : 'hidden' }}">{{ $errors->first('image') }}</p>
            </div>

            {{-- Existing gallery: drag to reorder, click × to mark for deletion --}}
            @if(isset($product) && $product->images->isNotEmpty())
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
                        <p class="text-xs text-gray-300 mt-0.5">You can add as many as you like — square, e.g. 1000×1000px, works best</p>
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
                @if(isset($product) && $product->download_file)
                <div class="bg-green-50 border border-green-200 rounded-xl p-3 text-sm text-green-700">
                    Current file: <span class="font-mono">{{ basename($product->download_file) }}</span>
                </div>
                @endif
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ isset($product) && $product->download_file ? 'Replace File' : 'Upload File *' }}</label>
                    <input type="file" name="download_file" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm">
                    <p class="text-xs text-gray-400 mt-1">Max 100MB. PDF, ZIP, MP3, MP4, etc.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Download Expiry (days)</label>
                    <input type="number" name="download_expiry_days" value="{{ old('download_expiry_days', $product->download_expiry_days ?? '') }}" min="1" placeholder="Blank = no limit"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>
        </div>

        {{-- Specifications --}}
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-800">Specifications <span class="text-xs text-gray-400 font-normal ml-1">(optional)</span></h3>
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
                <h3 class="font-semibold text-gray-800">FAQs <span class="text-xs text-gray-400 font-normal ml-1">(optional)</span></h3>
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

        {{-- Product Readiness checklist — reacts live to the fields above as you type, no
             save required, so gaps (Google Merchant's "No global identifier"-style issues,
             an empty description, a missing image) are visible before you ever hit submit. --}}
        <div class="bg-white rounded-2xl shadow-sm p-6 form-card" style="animation-delay:40ms">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-800">Product Readiness</h3>
                <span class="text-xs font-bold px-2 py-0.5 rounded-lg"
                    :class="completenessPercent === 100 ? 'bg-emerald-100 text-emerald-700' : (completenessPercent >= 50 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-600')"
                    x-text="completenessPercent + '%'"></span>
            </div>
            <div class="w-full h-1.5 bg-gray-100 rounded-full overflow-hidden mb-4">
                <div class="h-full rounded-full transition-all duration-500"
                    :class="completenessPercent === 100 ? 'bg-emerald-500' : (completenessPercent >= 50 ? 'bg-amber-500' : 'bg-red-400')"
                    :style="`width: ${completenessPercent}%`"></div>
            </div>
            <div class="space-y-1.5">
                <template x-for="check in completenessChecks" :key="check.label">
                    <div class="flex items-center gap-2 text-sm">
                        <svg class="w-4 h-4 flex-shrink-0" :class="check.ok ? 'text-emerald-500' : 'text-gray-300'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path x-show="check.ok" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            <circle x-show="!check.ok" cx="12" cy="12" r="9" stroke-width="2"/>
                        </svg>
                        <span :class="check.ok ? 'text-gray-600' : 'text-gray-400'" x-text="check.label"></span>
                    </div>
                </template>
            </div>
            <button type="button" @click="livePreviewOpen = true"
                class="w-full mt-4 flex items-center justify-center gap-2 text-sm font-medium text-indigo-600 bg-indigo-50 hover:bg-indigo-100 rounded-xl py-2.5 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                Preview as customers see it
            </button>
        </div>

        {{-- Pricing --}}
        <div class="bg-white rounded-2xl shadow-sm p-6 form-card" style="animation-delay:80ms">
            <div class="flex items-center gap-2 mb-4">
                <div class="w-7 h-7 bg-indigo-50 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h3 class="font-semibold text-gray-800">Pricing & Inventory</h3>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <span x-text="productType === 'bundle' ? 'Bundle Price (৳) *' : 'Price (৳) *'"></span>
                    </label>
                    <input type="number" name="price" x-model="price" step="0.01" min="0"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('price') border-red-400 @enderror">
                    <p id="error-price" class="text-red-500 text-xs mt-1 {{ $errors->has('price') ? '' : 'hidden' }}">{{ $errors->first('price') }}</p>
                </div>
                <div x-show="productType !== 'bundle'">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sale Price (৳)</label>
                    <input type="number" name="sale_price" x-model="salePrice" step="0.01" min="0"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div x-show="productType !== 'bundle' && productType !== 'variable'">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Stock{{ isset($product) ? '' : ' *' }}</label>
                    <input type="number" name="stock" value="{{ old('stock', $product->stock ?? 0) }}" min="0"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div x-show="productType === 'variable'" x-cloak>
                    <p class="text-xs text-gray-400 bg-gray-50 rounded-lg p-3">Stock is managed per color/size combination above.</p>
                </div>
                <div x-show="productType === 'bundle'" x-cloak>
                    <p class="text-xs text-gray-400 bg-gray-50 rounded-lg p-3">Bundle stock is automatic — available while all items have stock.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Weight (kg)</label>
                    <input type="number" name="weight" value="{{ old('weight', $product->weight ?? '') }}" step="0.01" min="0" placeholder="Optional"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>
        </div>

        {{-- Organisation --}}
        <div class="bg-white rounded-2xl shadow-sm p-6 form-card" style="animation-delay:120ms">
            <div class="flex items-center gap-2 mb-4">
                <div class="w-7 h-7 bg-indigo-50 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                </div>
                <h3 class="font-semibold text-gray-800">Organisation</h3>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Brand</label>
                    <select name="brand_id" x-model="brandId" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">No Brand</option>
                        @foreach($brands as $brand)
                        <option value="{{ $brand->id }}">{{ $brand->name }}</option>
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
                    <p id="error-category_id" class="text-red-500 text-xs mt-1 {{ $errors->has('category_id') ? '' : 'hidden' }}">{{ $errors->first('category_id') }}</p>
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
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sort Order</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', $product->sort_order ?? 0) }}" min="0"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <p class="text-xs text-gray-400 mt-1">Lower shows first within this category's product listing page. Easier to manage from Products list → sort "Manual Order" → drag to reorder.</p>
                </div>
                <div class="flex items-center space-x-3">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $product->is_active ?? true) ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-indigo-600 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full"></div>
                    </label>
                    <span class="text-sm text-gray-700">Active</span>
                </div>
                <div class="flex items-center space-x-3">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $product->is_featured ?? false) ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-indigo-600 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full"></div>
                    </label>
                    <span class="text-sm text-gray-700">Featured</span>
                </div>
            </div>
        </div>

        @if(isset($product))
        {{-- Info summary --}}
        <div class="bg-gray-50 rounded-2xl p-4 text-xs text-gray-500 space-y-1.5 form-card" style="animation-delay:160ms">
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
        @endif

        {{-- Sticky so it stays reachable while scrolling a long form — the single most
             common action on this page, now never more than a glance away. --}}
        <div class="sticky bottom-4 z-10 space-y-2">
            <button type="submit" id="product-submit-btn" class="w-full bg-indigo-600 text-white py-3 rounded-xl font-semibold hover:bg-indigo-700 active:scale-[0.99] transition shadow-lg shadow-indigo-600/20">
                {{ isset($product) ? 'Update Product' : 'Create Product' }}
            </button>
            <p class="text-center text-xs text-gray-400">Tip: press <kbd class="px-1 py-0.5 bg-gray-100 border border-gray-200 rounded text-[10px] font-mono">Ctrl</kbd> + <kbd class="px-1 py-0.5 bg-gray-100 border border-gray-200 rounded text-[10px] font-mono">S</kbd> to save</p>
        </div>
    </div>
</div>

{{-- Quick entry: one click derives Meta Title/Description, OG/Twitter title+description+image,
     image alt/title text, AI summary/overview, a focus keyword, and matching tags — all from
     the Name/Short Description/Full Description/Image/Brand already entered above, and only
     into fields that are still empty. Nothing here is guessed (no GTIN/MPN/category/age-group
     invented from nothing); it only ever reuses text the admin already wrote. Plain JS (not
     Alpine) since the SEO fields partial has its own, separate x-data scope, and Tags' Alpine
     state is reached via Alpine.$data() rather than duplicating it here — see
     autofillProductSeo() in this page's own @push('scripts'). --}}
<div class="flex items-center gap-2 -mb-2 mt-6">
    <button type="button" onclick="autofillProductSeo()"
        class="text-xs font-medium text-emerald-600 hover:text-emerald-800 bg-emerald-50 hover:bg-emerald-100 px-3 py-1.5 rounded-lg transition inline-flex items-center gap-1.5">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
        Auto-fill SEO, social, image text, AI summary &amp; tags
    </button>
    <span class="text-xs text-gray-400">Only fills fields that are still empty — Tags above included</span>
</div>

@include('admin.products._seo_fields', isset($product) ? [] : ['product' => null])

{{-- Live preview — a slide-over showing the product roughly as it will appear on the shop
     grid, built from the same reactive fields as the checklist above rather than a second
     copy of the form's data. x-teleport so it isn't clipped by any ancestor's overflow. --}}
<template x-teleport="body">
    <div x-show="livePreviewOpen" x-cloak class="fixed inset-0 z-[9998]" @keydown.escape.window="livePreviewOpen = false">
        <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" @click="livePreviewOpen = false"></div>
        <div class="absolute inset-y-0 right-0 w-full max-w-sm bg-gray-50 shadow-2xl flex flex-col"
            x-show="livePreviewOpen" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 bg-white flex-shrink-0">
                <div>
                    <h3 class="font-semibold text-gray-800">Customer Preview</h3>
                    <p class="text-xs text-gray-400">Roughly how this looks on the shop grid</p>
                </div>
                <button type="button" @click="livePreviewOpen = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto p-6">
                <div class="max-w-[220px] mx-auto bg-white rounded-xl shadow-sm ring-1 ring-gray-100 overflow-hidden">
                    <div class="relative bg-gray-50 aspect-square p-3">
                        <template x-if="preview">
                            <img :src="preview" class="w-full h-full object-contain">
                        </template>
                        <template x-if="!preview">
                            <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200">
                                <svg class="w-14 h-14 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                        </template>
                        <template x-if="salePrice && parseFloat(salePrice) > 0 && parseFloat(salePrice) < parseFloat(price || 0)">
                            <span class="absolute top-2 left-2 bg-gradient-to-r from-orange-500 to-amber-500 text-white text-[10px] font-bold px-2 py-1 rounded-full shadow-sm"
                                x-text="'-' + Math.round((1 - parseFloat(salePrice) / parseFloat(price)) * 100) + '%'"></span>
                        </template>
                    </div>
                    <div class="p-3 pt-4">
                        <p class="text-[10px] text-gray-500 font-medium uppercase tracking-wide mb-0.5" x-show="brandName" x-text="brandName"></p>
                        <h3 class="text-xs font-bold text-gray-900 leading-snug line-clamp-2" x-text="name || 'Product name…'"></h3>
                        <p class="text-[11px] text-gray-500 mt-1 line-clamp-2" x-text="shortDescription"></p>
                        <div class="mt-2">
                            <template x-if="salePrice && parseFloat(salePrice) > 0 && parseFloat(salePrice) < parseFloat(price || 0)">
                                <span>
                                    <span class="text-base font-bold text-orange-700" x-text="'৳' + Number(salePrice).toLocaleString()"></span>
                                    <span class="text-[10px] text-gray-500 line-through ml-1" x-text="'৳' + Number(price || 0).toLocaleString()"></span>
                                </span>
                            </template>
                            <template x-if="!(salePrice && parseFloat(salePrice) > 0 && parseFloat(salePrice) < parseFloat(price || 0))">
                                <span class="text-base font-bold text-gray-900" x-text="'৳' + Number(price || 0).toLocaleString()"></span>
                            </template>
                        </div>
                    </div>
                </div>
                <p class="text-xs text-gray-400 text-center mt-6">A simplified preview — the real shop card also shows stock badges, ratings and the order button.</p>
            </div>
        </div>
    </div>
</template>
