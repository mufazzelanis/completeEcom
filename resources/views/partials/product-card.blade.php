<div class="bg-white rounded-2xl shadow-sm hover:shadow-md transition-all group overflow-hidden">
    <a href="{{ route('products.show', $product->slug) }}" class="block">
        <div class="relative overflow-hidden bg-gray-100 aspect-square">
            @if($product->image)
                <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}"
                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
            @else
                <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200">
                    <svg class="w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
            @endif

            @if($product->sale_price)
                <span class="absolute top-2 left-2 bg-red-500 text-white text-xs px-2 py-1 rounded-full font-medium">Sale</span>
            @endif
            @if($product->is_featured)
                <span class="absolute top-2 right-2 bg-yellow-400 text-yellow-900 text-xs px-2 py-1 rounded-full font-medium">Featured</span>
            @endif
        </div>
    </a>

    <div class="p-4">
        <p class="text-xs text-indigo-500 font-medium mb-1">{{ $product->category->name }}</p>
        <a href="{{ route('products.show', $product->slug) }}" class="font-semibold text-gray-800 text-sm hover:text-indigo-600 line-clamp-2 leading-snug mb-2">
            {{ $product->name }}
        </a>

        <div class="flex items-center justify-between mt-3">
            <div>
                @if($product->sale_price)
                    <span class="text-lg font-bold text-red-600">৳{{ number_format($product->sale_price) }}</span>
                    <span class="text-sm text-gray-400 line-through ml-1">৳{{ number_format($product->price) }}</span>
                @else
                    <span class="text-lg font-bold text-gray-900">৳{{ number_format($product->price) }}</span>
                @endif
            </div>
            <form action="{{ route('cart.add') }}" method="POST">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <input type="hidden" name="quantity" value="1">
                <button type="submit" class="w-9 h-9 bg-indigo-600 text-white rounded-full flex items-center justify-center hover:bg-indigo-700 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                </button>
            </form>
        </div>

        @if($product->stock <= 5 && $product->stock > 0)
            <p class="text-xs text-orange-500 mt-2 font-medium">Only {{ $product->stock }} left!</p>
        @elseif($product->stock === 0)
            <p class="text-xs text-red-500 mt-2 font-medium">Out of stock</p>
        @endif
    </div>
</div>
