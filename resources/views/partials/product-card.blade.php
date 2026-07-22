@php
    $isFlash = $product->activeFlashSaleProduct && $product->activeFlashSaleProduct->isAvailable();
    $effectivePrice = $product->final_price;
    $hasDiscount = $effectivePrice < $product->price;
    $discountPct = $hasDiscount ? round((($product->price - $effectivePrice) / $product->price) * 100) : 0;
    $rating = $product->reviews->avg('rating') ?? 0;
    $reviewCount = $product->reviews->count();
    $isWishlisted = auth()->check() ? \App\Models\Wishlist::where('user_id', auth()->id())->where('product_id', $product->id)->exists() : false;
@endphp
<div class="bg-white rounded-lg shadow-sm hover:shadow-lg transition-all duration-300 group overflow-hidden border border-transparent hover:border-orange-200 relative">
    <a href="{{ route('products.show', $product->slug) }}" class="block relative">
        <div class="relative overflow-hidden bg-gray-50 aspect-square">
            @if($product->image)
                <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}"
                    class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
            @else
                <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200">
                    <svg class="w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
            @endif

            @if($isFlash)
                <span class="absolute top-0 left-0 bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-br-lg">
                    ⚡ -{{ $discountPct }}%
                </span>
            @elseif($hasDiscount)
                <span class="absolute top-0 left-0 bg-orange-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-br-lg">
                    -{{ $discountPct }}%
                </span>
            @endif

            <div class="absolute top-2 right-2 flex flex-col gap-1.5 md:opacity-0 md:group-hover:opacity-100 transition-opacity duration-200">
                <button onclick="event.preventDefault(); toggleWishlist({{ $product->id }}, this)"
                    class="w-8 h-8 bg-white rounded-full shadow-md flex items-center justify-center hover:bg-red-50 transition {{ $isWishlisted ? 'text-red-500' : 'text-gray-400 hover:text-red-500' }}"
                    title="Add to Wishlist">
                    <svg class="w-4 h-4" fill="{{ $isWishlisted ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                </button>
            </div>

            @if($product->available_stock <= 0)
                <div class="absolute inset-0 bg-black/40 flex items-center justify-center">
                    <span class="bg-white text-gray-800 text-xs font-bold px-3 py-1.5 rounded-full">SOLD OUT</span>
                </div>
            @endif
        </div>
    </a>

    <div class="p-3">
        @if($product->brand)
            <p class="text-[10px] text-gray-400 font-medium uppercase tracking-wide mb-0.5">{{ $product->brand->name }}</p>
        @endif

        <a href="{{ route('products.show', $product->slug) }}" class="block">
            <h3 class="text-xs text-gray-700 leading-snug line-clamp-2 h-8 group-hover:text-orange-500 transition-colors">
                {{ $product->name }}
            </h3>
        </a>

        @if($rating > 0)
            <div class="flex items-center gap-1 mt-1.5">
                <div class="flex items-center">
                    @for($i = 1; $i <= 5; $i++)
                        @if($i <= floor($rating))
                            <svg class="w-3 h-3 text-orange-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        @elseif($i - 0.5 <= $rating)
                            <svg class="w-3 h-3 text-orange-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        @else
                            <svg class="w-3 h-3 text-gray-200" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        @endif
                    @endfor
                </div>
                <span class="text-[10px] text-gray-400">({{ $reviewCount }})</span>
            </div>
        @endif

        <div class="mt-2">
            @if($hasDiscount)
                <span class="text-base font-bold {{ $isFlash ? 'text-red-500' : 'text-orange-500' }}">৳{{ number_format($effectivePrice) }}</span>
                <span class="text-[10px] text-gray-400 line-through ml-1">৳{{ number_format($product->price) }}</span>
            @else
                <span class="text-base font-bold text-gray-900">৳{{ number_format($product->price) }}</span>
            @endif
        </div>

        @if($product->available_stock <= 5 && $product->available_stock > 0)
            <p class="text-[10px] text-orange-500 mt-1 font-medium">Only {{ $product->available_stock }} left - order soon</p>
        @endif
    </div>
</div>
