{{--
    Shared by admin/products/create.blade.php, admin/products/edit.blade.php and
    seller/products/_form.blade.php. Pure markup — it reads/writes `productType`,
    `colors`, `sizes`, `combinations` and calls addColor()/removeColor(i)/
    addSize()/removeSize(i) on whichever parent x-data scope includes it, so
    every host page must define those with matching names/shapes.
--}}

{{-- Colors --}}
<div class="bg-white rounded-2xl shadow-sm p-6" x-show="productType === 'variable'" x-cloak>
    <div class="flex items-center justify-between mb-4">
        <h3 class="font-semibold text-gray-800">Colors <span class="text-xs text-gray-400 font-normal ml-1">(optional)</span></h3>
        <button type="button" @click="addColor()" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg> Add
        </button>
    </div>
    <div class="space-y-2">
        <template x-if="colors.length === 0"><p class="text-sm text-gray-400 py-2">No colors added. Add one, or skip straight to Sizes below.</p></template>
        <div class="grid grid-cols-12 gap-2 text-xs text-gray-500 font-medium px-1 mb-1" x-show="colors.length > 0">
            <div class="col-span-4">Name *</div><div class="col-span-4">Hex Code</div>
            <div class="col-span-2">Swatch Image</div><div class="col-span-1">On</div><div class="col-span-1"></div>
        </div>
        <template x-for="(c, i) in colors" :key="i">
            <div class="grid grid-cols-12 gap-2 items-center bg-gray-50 rounded-xl px-3 py-2">
                <input type="hidden" :name="`colors[${i}][id]`" :value="c.id || ''">
                <div class="col-span-4"><input type="text" :name="`colors[${i}][name]`" x-model="c.name" placeholder="Navy Blue" class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"></div>
                <div class="col-span-4 flex items-center gap-1">
                    <input type="color" x-model="c.hex_code" class="w-8 h-8 rounded cursor-pointer border-0 p-0 flex-shrink-0">
                    <input type="text" :name="`colors[${i}][hex_code]`" x-model="c.hex_code" class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-xs focus:outline-none font-mono">
                </div>
                <div class="col-span-2"><input type="file" :name="`color_images[${i}]`" accept="image/*" class="w-full text-xs"></div>
                <div class="col-span-1 flex justify-center"><input type="checkbox" :name="`colors[${i}][is_active]`" value="1" x-model="c.is_active" class="w-4 h-4 text-indigo-600 rounded"></div>
                <div class="col-span-1 flex justify-end">
                    <button type="button" @click="removeColor(i)" class="text-red-400 hover:text-red-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
        </template>
    </div>
</div>

{{-- Sizes --}}
<div class="bg-white rounded-2xl shadow-sm p-6" x-show="productType === 'variable'" x-cloak>
    <div class="flex items-center justify-between mb-4">
        <h3 class="font-semibold text-gray-800">Sizes <span class="text-xs text-gray-400 font-normal ml-1">(optional — S, M, L, XL, etc.)</span></h3>
        <button type="button" @click="addSize()" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg> Add
        </button>
    </div>
    <div class="space-y-2">
        <template x-if="sizes.length === 0"><p class="text-sm text-gray-400 py-2">No sizes added.</p></template>
        <div class="grid grid-cols-12 gap-2 text-xs text-gray-500 font-medium px-1 mb-1" x-show="sizes.length > 0">
            <div class="col-span-9">Name *</div><div class="col-span-2">On</div><div class="col-span-1"></div>
        </div>
        <template x-for="(s, i) in sizes" :key="i">
            <div class="grid grid-cols-12 gap-2 items-center bg-gray-50 rounded-xl px-3 py-2">
                <input type="hidden" :name="`sizes[${i}][id]`" :value="s.id || ''">
                <div class="col-span-9"><input type="text" :name="`sizes[${i}][name]`" x-model="s.name" placeholder="Small" class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"></div>
                <div class="col-span-2 flex justify-center"><input type="checkbox" :name="`sizes[${i}][is_active]`" value="1" x-model="s.is_active" class="w-4 h-4 text-indigo-600 rounded"></div>
                <div class="col-span-1 flex justify-end">
                    <button type="button" @click="removeSize(i)" class="text-red-400 hover:text-red-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
        </template>
    </div>
    <p class="text-xs text-gray-400 mt-2">Add at least a color or a size — the combinations below are generated automatically.</p>
</div>

{{-- Combinations (auto-generated: one row per color x size pair) --}}
<div class="bg-white rounded-2xl shadow-sm p-6" x-show="productType === 'variable'" x-cloak>
    <div class="flex items-center justify-between mb-4">
        <h3 class="font-semibold text-gray-800">Combinations</h3>
        <span class="text-xs text-gray-400" x-text="combinations.length + ' combination' + (combinations.length === 1 ? '' : 's')"></span>
    </div>
    <template x-if="combinations.length === 0">
        <p class="text-sm text-gray-400 py-2">Add a color and/or a size above — every combination appears here automatically with its own stock and optional price/SKU.</p>
    </template>
    <div class="space-y-2">
        <div class="grid grid-cols-12 gap-2 text-xs text-gray-500 font-medium px-1 mb-1" x-show="combinations.length > 0">
            <div class="col-span-3">Color</div><div class="col-span-3">Size</div>
            <div class="col-span-2">SKU</div><div class="col-span-2">Price (৳)</div>
            <div class="col-span-1">Stock</div><div class="col-span-1">On</div>
        </div>
        <template x-for="(combo, i) in combinations" :key="i">
            <div class="grid grid-cols-12 gap-2 items-center bg-gray-50 rounded-xl px-3 py-2">
                <input type="hidden" :name="`combinations[${i}][id]`" :value="combo.id || ''">
                <input type="hidden" :name="`combinations[${i}][color_index]`" :value="combo.color_index === null ? '' : combo.color_index">
                <input type="hidden" :name="`combinations[${i}][size_index]`" :value="combo.size_index === null ? '' : combo.size_index">
                <div class="col-span-3 flex items-center gap-1.5 text-sm text-gray-700">
                    <template x-if="combo.color_index !== null && colors[combo.color_index]">
                        <span class="flex items-center gap-1.5">
                            <span class="w-3.5 h-3.5 rounded-full border border-gray-200 flex-shrink-0" :style="`background:${colors[combo.color_index].hex_code || '#ccc'}`"></span>
                            <span x-text="colors[combo.color_index].name"></span>
                        </span>
                    </template>
                    <span x-show="combo.color_index === null" class="text-gray-300">—</span>
                </div>
                <div class="col-span-3 text-sm text-gray-700">
                    <span x-show="combo.size_index !== null && sizes[combo.size_index]" x-text="sizes[combo.size_index] ? sizes[combo.size_index].name : ''"></span>
                    <span x-show="combo.size_index === null" class="text-gray-300">—</span>
                </div>
                <div class="col-span-2"><input type="text" :name="`combinations[${i}][sku]`" x-model="combo.sku" placeholder="SKU" class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"></div>
                <div class="col-span-2"><input type="number" :name="`combinations[${i}][price]`" x-model="combo.price" placeholder="Base" step="0.01" min="0" class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"></div>
                <div class="col-span-1"><input type="number" :name="`combinations[${i}][stock]`" x-model="combo.stock" min="0" class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"></div>
                <div class="col-span-1 flex justify-center"><input type="checkbox" :name="`combinations[${i}][is_active]`" value="1" x-model="combo.is_active" class="w-4 h-4 text-indigo-600 rounded"></div>
            </div>
        </template>
    </div>
    <p class="text-xs text-gray-400 mt-2">Leave Price blank to use the product's base price. Total stock in Pricing &amp; Inventory is the sum of all combinations.</p>
</div>
