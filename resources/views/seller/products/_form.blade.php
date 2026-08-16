@csrf
@if(isset($product))@method('PUT')@endif
<input type="hidden" name="type" :value="productType">

<div class="bg-white rounded-2xl shadow-sm p-6 space-y-4 max-w-2xl">
    @if(isset($product) && $product->approval_status === 'rejected' && $product->rejection_reason)
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm">
        <strong>Rejected:</strong> {{ $product->rejection_reason }}
    </div>
    @endif

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Product Name <span class="text-red-500">*</span></label>
        <input type="text" name="name" value="{{ old('name', $product->name ?? '') }}" required
            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Product Type</label>
        <div class="grid grid-cols-3 gap-3">
            <label class="flex flex-col p-3 border-2 rounded-xl cursor-pointer transition"
                :class="productType === 'simple' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-gray-300'"
                @click="productType = 'simple'">
                <span class="text-sm font-semibold" :class="productType === 'simple' ? 'text-indigo-700' : 'text-gray-700'">Simple</span>
                <span class="text-xs text-gray-400 mt-0.5">Fixed price and stock</span>
            </label>
            <label class="flex flex-col p-3 border-2 rounded-xl cursor-pointer transition"
                :class="productType === 'variable' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-gray-300'"
                @click="productType = 'variable'">
                <span class="text-sm font-semibold" :class="productType === 'variable' ? 'text-indigo-700' : 'text-gray-700'">Variable</span>
                <span class="text-xs text-gray-400 mt-0.5">Sizes and/or colors, each with its own stock</span>
            </label>
            <label class="flex flex-col p-3 border-2 rounded-xl cursor-pointer transition"
                :class="productType === 'digital' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-gray-300'"
                @click="productType = 'digital'">
                <span class="text-sm font-semibold" :class="productType === 'digital' ? 'text-indigo-700' : 'text-gray-700'">Digital</span>
                <span class="text-xs text-gray-400 mt-0.5">Downloadable file (PDF, ZIP, etc.)</span>
            </label>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Category <span class="text-red-500">*</span></label>
            <select name="category_id" required class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">Select category</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ old('category_id', $product->category_id ?? '') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                @endforeach
            </select>
            @error('category_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            <a href="{{ route('seller.categories.create') }}" class="text-xs text-indigo-600 hover:underline block mt-1">Don't see your category? Propose a new one →</a>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Brand</label>
            <select name="brand_id" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">No brand</option>
                @foreach($brands as $brand)
                    <option value="{{ $brand->id }}" {{ old('brand_id', $product->brand_id ?? '') == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="grid grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Price <span class="text-red-500">*</span></label>
            <input type="number" step="0.01" name="price" value="{{ old('price', $product->price ?? '') }}" required
                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            @error('price')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Sale Price</label>
            <input type="number" step="0.01" name="sale_price" value="{{ old('sale_price', $product->sale_price ?? '') }}"
                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            @error('sale_price')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div x-show="productType !== 'variable'">
            <label class="block text-sm font-medium text-gray-700 mb-1">Stock <span class="text-red-500">*</span></label>
            <input type="number" name="stock" value="{{ old('stock', $product->stock ?? 0) }}"
                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            @error('stock')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div x-show="productType === 'variable'" x-cloak>
            <label class="block text-sm font-medium text-gray-700 mb-1">Stock</label>
            <p class="text-xs text-gray-400 bg-gray-50 rounded-lg p-3">Managed per combination below.</p>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">SKU</label>
            <input type="text" name="sku" value="{{ old('sku', $product->sku ?? '') }}"
                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            @error('sku')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Weight (kg)</label>
            <input type="number" step="0.01" name="weight" value="{{ old('weight', $product->weight ?? '') }}"
                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Short Description</label>
        <textarea name="short_description" rows="2" maxlength="500"
            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none">{{ old('short_description', $product->short_description ?? '') }}</textarea>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Full Description</label>
        @include('partials.rich-editor', ['name' => 'description', 'value' => old('description', $product->description ?? ''), 'id' => 'description', 'placeholder' => 'Describe the product in detail — features, materials, sizing, care instructions…'])
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Product Image</label>
        @if(isset($product) && $product->image)
        <img src="{{ Storage::url($product->image) }}" class="w-20 h-20 rounded-xl object-cover mb-2 border border-gray-100">
        @endif
        <input type="file" name="image" accept="image/*" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm">
        @error('image')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    <div class="bg-indigo-50 text-indigo-700 text-xs rounded-xl px-4 py-3">
        {{ isset($product) ? 'Saving changes will re-submit this product for admin approval before it goes live again.' : 'New products are reviewed by admin before they appear on the store.' }}
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm p-6 space-y-4 max-w-2xl mt-4" x-show="productType === 'digital'" x-cloak>
    <h3 class="font-semibold text-gray-800">Digital File</h3>
    @if(isset($product) && $product->download_file)
    <div class="bg-green-50 border border-green-200 rounded-xl p-3 text-sm text-green-700">
        Current file: <span class="font-mono">{{ basename($product->download_file) }}</span>
    </div>
    @endif
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
            {{ isset($product) && $product->download_file ? 'Replace File' : 'Upload File' }}
            <span class="text-red-500" x-show="{{ isset($product) && $product->download_file ? 'false' : 'true' }}">*</span>
        </label>
        <input type="file" name="download_file" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm">
        <p class="text-xs text-gray-400 mt-1">Max 100MB. Customers can download this after their order is placed and paid.</p>
        @error('download_file')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Download Expiry (days)</label>
        <input type="number" name="download_expiry_days" value="{{ old('download_expiry_days', $product->download_expiry_days ?? '') }}" min="1" placeholder="Blank = no limit"
            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
    </div>
</div>

<div class="max-w-2xl mt-4">
    @include('admin.products._variant_matrix')
</div>

<div class="max-w-2xl flex gap-3 pt-4">
    <button type="submit" class="bg-indigo-600 text-white px-6 py-2.5 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition">
        {{ isset($product) ? 'Save Changes' : 'Submit for Approval' }}
    </button>
    <a href="{{ route('seller.products.index') }}" class="px-6 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50">Cancel</a>
</div>
