@csrf
@if(isset($product))@method('PUT')@endif

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
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Stock <span class="text-red-500">*</span></label>
            <input type="number" name="stock" value="{{ old('stock', $product->stock ?? 0) }}" required
                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            @error('stock')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
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
        <textarea name="description" rows="6"
            class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('description', $product->description ?? '') }}</textarea>
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

    <div class="flex gap-3 pt-2">
        <button type="submit" class="bg-indigo-600 text-white px-6 py-2.5 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition">
            {{ isset($product) ? 'Save Changes' : 'Submit for Approval' }}
        </button>
        <a href="{{ route('seller.products.index') }}" class="px-6 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50">Cancel</a>
    </div>
</div>
