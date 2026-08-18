@php
    $isFlash = $product->activeFlashSaleProduct && $product->activeFlashSaleProduct->isAvailable();
    $effectivePrice = $product->final_price;
    $hasDiscount = $effectivePrice < $product->price;
    $discountPct = $hasDiscount ? round((($product->price - $effectivePrice) / $product->price) * 100) : 0;
    $rating = $product->reviews->avg('rating') ?? 0;
    $reviewCount = $product->reviews->count();
    $isWishlisted = auth()->check() ? \App\Models\Wishlist::where('user_id', auth()->id())->where('product_id', $product->id)->exists() : false;
    $isInCart = auth()->check()
        ? \App\Models\Cart::where('user_id', auth()->id())->where('product_id', $product->id)->exists()
        : \App\Models\Cart::where('session_id', session()->getId())->where('product_id', $product->id)->exists();
@endphp
<div class="h-full flex flex-col bg-white rounded-lg shadow-sm hover:shadow-lg transition-all duration-300 group overflow-hidden border border-transparent hover:border-orange-200 relative">
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

            <div class="absolute top-2 right-2 flex flex-col items-end gap-1.5 md:opacity-0 md:group-hover:opacity-100 transition-opacity duration-200">
                <button onclick="event.preventDefault(); toggleWishlist({{ $product->id }}, this)"
                    class="w-8 h-8 bg-white rounded-full shadow-md flex items-center justify-center hover:bg-red-50 transition {{ $isWishlisted ? 'text-red-500' : 'text-gray-400 hover:text-red-500' }}"
                    title="Add to Wishlist" aria-label="{{ $isWishlisted ? 'Remove from wishlist' : 'Add to wishlist' }} — {{ $product->name }}">
                    <svg class="w-4 h-4" fill="{{ $isWishlisted ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                </button>

                {{-- Quick add-to-cart — toggles this product in/out of the cart via AJAX, no page
                     reload, so a customer browsing the grid can select several products without
                     losing the "added" state on the ones they already picked. The button reflects
                     actual cart membership (computed above as $isInCart) rather than a timed
                     animation, so it stays selected until the customer explicitly un-selects it.
                     Only shown for simple products with stock: variants aren't wired into the
                     cart-add flow at all, so there's no UI here to pick one. --}}
                @if($product->isSimple() && $product->available_stock > 0)
                    @php
                        $quickAddStyle = setting('add_to_cart_button_style', 'icon');
                        $addToCartText = setting('add_to_cart_button_text', 'Add to Cart');
                        $cartIconSvg = '<svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>';
                        $checkIconSvg = '<svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>';
                    @endphp
                    <button onclick="event.preventDefault(); toggleCartItem({{ $product->id }}, this)"
                        data-in-cart="{{ $isInCart ? 'true' : 'false' }}"
                        data-product-name="{{ $product->name }}"
                        data-product-price="{{ $effectivePrice }}"
                        data-icon-default='{!! $cartIconSvg !!}'
                        data-icon-added='{!! $checkIconSvg !!}'
                        data-label-default="{{ $addToCartText }}"
                        data-label-added="Added"
                        class="quick-add-btn {{ $quickAddStyle === 'text' ? 'pl-2 pr-3 h-8' : 'w-8 h-8' }} rounded-full shadow-md flex items-center justify-center gap-1 transition {{ $isInCart ? 'bg-orange-500 text-white hover:bg-orange-600' : 'bg-white text-gray-500 hover:bg-orange-50 hover:text-orange-500' }}"
                        title="{{ $isInCart ? 'Remove from Cart' : $addToCartText }}"
                        aria-label="{{ $isInCart ? 'Remove from cart' : $addToCartText }} — {{ $product->name }}">
                        <span class="quick-add-icon">{!! $isInCart ? $checkIconSvg : $cartIconSvg !!}</span>
                        @if($quickAddStyle === 'text')
                            <span class="text-[10px] font-bold whitespace-nowrap quick-add-label">{{ $isInCart ? 'Added' : $addToCartText }}</span>
                        @endif
                    </button>
                @endif
            </div>

            @if($product->available_stock <= 0)
                <div class="absolute inset-0 bg-black/40 flex items-center justify-center">
                    <span class="bg-white text-gray-800 text-xs font-bold px-3 py-1.5 rounded-full">SOLD OUT</span>
                </div>
            @endif
        </div>
    </a>

    <div class="p-3 flex flex-col flex-1">
        @if($product->brand)
            <p class="text-[10px] text-gray-500 font-medium uppercase tracking-wide mb-0.5">{{ $product->brand->name }}</p>
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
                <span class="text-[10px] text-gray-500">({{ $reviewCount }})</span>
            </div>
        @endif

        {{-- Everything below is pinned to the bottom of the card via mt-auto, so price/
             stock-warning/Buy-Now line up at the same height across a row regardless of
             how many lines the name/rating above take up (that mismatch was what made
             the grid look "staggered up and down"). --}}
        <div class="mt-auto pt-2">
            <div>
                @if($hasDiscount)
                    <span class="text-base font-bold {{ $isFlash ? 'text-red-500' : 'text-orange-500' }}">৳{{ number_format($effectivePrice) }}</span>
                    <span class="text-[10px] text-gray-500 line-through ml-1">৳{{ number_format($product->price) }}</span>
                @else
                    <span class="text-base font-bold text-gray-900">৳{{ number_format($product->price) }}</span>
                @endif
            </div>

            @if($product->available_stock <= 5 && $product->available_stock > 0)
                <p class="text-[10px] text-orange-500 mt-1 font-medium">Only {{ $product->available_stock }} left - order soon</p>
            @endif

            @if($product->isVariable() && $product->available_stock > 0)
                {{-- Variable products need a color/size picked first — no matrix here
                     on the card, so send the customer to the product page to choose. --}}
                <a href="{{ route('products.show', $product->slug) }}"
                    class="inline-flex items-center gap-1 bg-[length:200%_auto] bg-gradient-to-r from-pink-500 via-fuchsia-500 to-orange-400 hover:bg-right text-white text-[11px] font-bold pl-2 pr-3 py-1 rounded-full shadow-sm hover:shadow-md transition-all duration-500 mt-2">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z"/></svg>
                    Select Options
                </a>
            @elseif($product->available_stock > 0)
                {{-- Same checkout.buy-now endpoint the product page uses — skips the cart
                     and takes the customer straight to checkout for just this one item. --}}
                <form action="{{ route('checkout.buy-now') }}" method="POST" class="mt-2">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <input type="hidden" name="quantity" value="1">
                    <button type="submit"
                        class="inline-flex items-center gap-1 bg-[length:200%_auto] bg-gradient-to-r from-pink-500 via-fuchsia-500 to-orange-400 hover:bg-right text-white text-[11px] font-bold pl-2 pr-3 py-1 rounded-full shadow-sm hover:shadow-md transition-all duration-500">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z"/></svg>
                        {{ setting('buy_now_button_text', 'Buy Now') }}
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>
