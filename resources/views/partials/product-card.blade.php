@php
    $isFlash = $product->activeFlashSaleProduct && $product->activeFlashSaleProduct->isAvailable();
    $effectivePrice = $product->final_price;
    $hasDiscount = $effectivePrice < $product->price;
    $discountPct = $hasDiscount ? round((($product->price - $effectivePrice) / $product->price) * 100) : 0;
    $rating = $product->reviews->avg('rating') ?? 0;
    $reviewCount = $product->reviews->count();
    $isWishlisted = auth()->check()
        ? \App\Models\Wishlist::where('user_id', auth()->id())->where('product_id', $product->id)->exists()
        : \App\Models\Wishlist::where('session_id', session()->getId())->where('product_id', $product->id)->exists();
    $isInCart = auth()->check()
        ? \App\Models\Cart::where('user_id', auth()->id())->where('product_id', $product->id)->exists()
        : \App\Models\Cart::where('session_id', session()->getId())->where('product_id', $product->id)->exists();
    // Only shown when there's no discount/flash badge already in that corner — a genuine,
    // data-backed freshness signal (real created_at, not a fabricated "trending" label)
    // rather than clutter competing with the price badge for the same spot.
    $isNew = !$isFlash && !$hasDiscount && $product->created_at->gt(now()->subDays(14));

    // "Buy Now"/"Select Options" pill color (Settings → General → Storefront Buttons) — inline
    // background-image/color rather than the Tailwind gradient classes they replace, so an
    // admin-picked RGB color applies here; bg-[length:200%_auto]/hover:bg-right stay as plain
    // utility classes below since they're separate CSS properties (size/position) that don't
    // collide with this inline background-image. Defaults match the original hardcoded
    // from-pink-500 via-fuchsia-500 to-orange-400 exactly, so an untouched install looks identical.
    $orderButtonGradient = sprintf(
        'linear-gradient(to right, %s, %s, %s)',
        setting('order_button_color_from', '#ec4899'),
        setting('order_button_color_via', '#d946ef'),
        setting('order_button_color_to', '#fb923c'),
    );
    $orderButtonTextColor = setting('order_button_text_color', '#ffffff');
@endphp
<div class="h-full flex flex-col bg-white rounded-xl shadow-sm hover:shadow-xl hover:shadow-gray-200/60 transition-all duration-300 group overflow-hidden ring-1 ring-gray-100 hover:ring-orange-200 hover:-translate-y-1 relative">
    <a href="{{ route('products.show', $product->slug) }}" class="block relative">
        {{-- object-contain + padding (not cover) — a product photo can be any shape or have
             any amount of its own white-background padding baked in (most do), and cropping
             to fill a hard square either chops the product off or, if the source photo
             already has margin around it, just leaves it looking small and off inside the
             crop. Contain always shows the whole product, centered, same card size either way. --}}
        <div class="relative overflow-hidden bg-gray-50 aspect-square p-3">
            @if($product->image)
                <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}" loading="lazy" decoding="async"
                    class="w-full h-full object-contain group-hover:scale-110 transition-transform duration-500">
            @else
                <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200">
                    <svg class="w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
            @endif

            @if($isFlash)
                <span class="absolute top-2 left-2 flex items-center gap-0.5 bg-gradient-to-r from-red-500 to-rose-500 text-white text-[10px] font-bold px-2 py-1 rounded-full shadow-sm">
                    ⚡ -{{ $discountPct }}%
                </span>
            @elseif($hasDiscount)
                <span class="absolute top-2 left-2 bg-gradient-to-r from-orange-500 to-amber-500 text-white text-[10px] font-bold px-2 py-1 rounded-full shadow-sm">
                    -{{ $discountPct }}%
                </span>
            @elseif($isNew)
                <span class="absolute top-2 left-2 bg-gradient-to-r from-emerald-500 to-teal-500 text-white text-[10px] font-bold px-2 py-1 rounded-full shadow-sm">
                    NEW
                </span>
            @endif

            {{-- Heart alone up here now — the quick-add-to-cart button moved to a floating
                 circle anchored on the card's bottom-right corner (see below), which reads
                 as a much more deliberate, tappable "add" action than being buried in a
                 stack of icons on the photo, and keeps this corner uncluttered. --}}
            <button onclick="event.preventDefault(); toggleWishlist({{ $product->id }}, this)"
                class="absolute top-2 right-2 w-9 h-9 bg-white rounded-full shadow-md flex items-center justify-center hover:bg-red-50 transition {{ $isWishlisted ? 'text-red-500' : 'text-gray-400 hover:text-red-500' }}"
                title="Add to Wishlist" aria-label="{{ $isWishlisted ? 'Remove from wishlist' : 'Add to wishlist' }} — {{ $product->name }}">
                <svg class="w-4 h-4" fill="{{ $isWishlisted ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                </svg>
            </button>

            {{-- A compact rating chip (one star + the average, not five) reading as a real
                 app badge rather than a strip of icons — sits bottom-left of the photo so it
                 never competes with the discount/flash/new badge in the opposite corner. --}}
            @if($rating > 0)
                <span class="absolute bottom-2 left-2 inline-flex items-center gap-0.5 bg-white/95 backdrop-blur-sm text-gray-800 text-[10px] font-bold px-1.5 py-0.5 rounded-full shadow-sm">
                    <svg class="w-3 h-3 text-orange-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    {{ number_format($rating, 1) }}
                </span>
            @endif

            @if($product->available_stock <= 0)
                <div class="absolute inset-0 bg-black/40 flex items-center justify-center">
                    <span class="bg-white text-gray-800 text-xs font-bold px-3 py-1.5 rounded-full">SOLD OUT</span>
                </div>
            @endif
        </div>
    </a>

    {{-- Quick add-to-cart — toggles this product in/out of the cart via AJAX, no page
         reload, so a customer browsing the grid can select several products without
         losing the "added" state on the ones they already picked. The button reflects
         actual cart membership (computed above as $isInCart) rather than a timed
         animation, so it stays selected until the customer explicitly un-selects it.
         Only shown for simple products with stock: variants aren't wired into the
         cart-add flow at all, so there's no UI here to pick one. A negative top margin
         (not absolute positioning) pulls it up to straddle the photo/content boundary —
         anchored to the image's own bottom edge regardless of how tall the card ends up
         being, and it sits outside the <a> above so its tap target doesn't nest inside
         the "go to product" link. --}}
    @if($product->isSimple() && $product->available_stock > 0)
        @php
            $quickAddStyle = setting('add_to_cart_button_style', 'icon');
            $addToCartText = setting('add_to_cart_button_text', 'Add to Cart');
            $cartIconSvg = '<svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>';
            $checkIconSvg = '<svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>';
        @endphp
        <div class="relative z-10 flex justify-end px-3 -mt-5 pointer-events-none">
            <button onclick="toggleCartItem({{ $product->id }}, this)"
                data-in-cart="{{ $isInCart ? 'true' : 'false' }}"
                data-product-name="{{ $product->name }}"
                data-product-price="{{ $effectivePrice }}"
                data-icon-default='{!! $cartIconSvg !!}'
                data-icon-added='{!! $checkIconSvg !!}'
                data-label-default="{{ $addToCartText }}"
                data-label-added="Added"
                class="quick-add-btn pointer-events-auto {{ $quickAddStyle === 'text' ? 'pl-2.5 pr-3.5 h-10' : 'w-10 h-10' }} rounded-full shadow-lg ring-4 ring-white bg-orange-500 text-white hover:bg-orange-600 flex items-center justify-center gap-1 transition active:scale-90"
                title="{{ $isInCart ? 'Remove from Cart' : $addToCartText }}"
                aria-label="{{ $isInCart ? 'Remove from cart' : $addToCartText }} — {{ $product->name }}">
                <span class="quick-add-icon">{!! $isInCart ? $checkIconSvg : $cartIconSvg !!}</span>
                @if($quickAddStyle === 'text')
                    <span class="text-[10px] font-bold whitespace-nowrap quick-add-label">{{ $isInCart ? 'Added' : $addToCartText }}</span>
                @endif
            </button>
        </div>
    @endif

    <div class="p-3 pt-4 flex flex-col flex-1">
        @if($product->brand)
            <p class="text-[10px] text-gray-500 font-medium uppercase tracking-wide mb-0.5">{{ $product->brand->name }}</p>
        @endif

        <a href="{{ route('products.show', $product->slug) }}" class="block">
            {{-- Bold + darker than before (was plain-weight text-gray-700, easy to skim past)
                 — a customer reads this title before deciding to order, so low-contrast,
                 light-weight type here directly costs orders. Hover now also underlines, not
                 just recolors, so the "this is clickable" signal doesn't rely on color alone. --}}
            <h3 class="text-xs font-semibold text-gray-800 leading-snug line-clamp-2 h-8 group-hover:text-orange-600 group-hover:underline decoration-orange-300 decoration-2 underline-offset-2 transition-colors duration-200">
                {{ $product->name }}
            </h3>
        </a>

        {{-- Everything below is pinned to the bottom of the card via mt-auto, so price/
             stock-warning/Buy-Now line up at the same height across a row regardless of
             how many lines the name/rating above take up (that mismatch was what made
             the grid look "staggered up and down"). --}}
        <div class="mt-auto pt-2">
            <div>
                @if($hasDiscount)
                    <span class="text-base font-bold {{ $isFlash ? 'text-red-500' : 'text-orange-700' }}">৳{{ number_format($effectivePrice) }}</span>
                    <span class="text-[10px] text-gray-500 line-through ml-1">৳{{ number_format($product->price) }}</span>
                @else
                    <span class="text-base font-bold text-gray-900">৳{{ number_format($product->price) }}</span>
                @endif
            </div>

            @if($product->available_stock <= 5 && $product->available_stock > 0)
                <p class="text-[10px] text-orange-700 mt-1 font-medium">Only {{ $product->available_stock }} left - order soon</p>
            @endif

            @if($product->isVariable() && $product->available_stock > 0)
                {{-- Variable products need a color/size picked first — no matrix here
                     on the card, so send the customer to the product page to choose. --}}
                <a href="{{ route('products.show', $product->slug) }}"
                    style="background-image: {{ $orderButtonGradient }}; color: {{ $orderButtonTextColor }};"
                    class="inline-flex items-center gap-1 bg-[length:200%_auto] hover:bg-right text-[11px] font-bold pl-2 pr-3 py-1 rounded-full shadow-sm hover:shadow-md transition-all duration-500 mt-2">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z"/></svg>
                    Select Options
                </a>
            @elseif($product->available_stock > 0)
                {{-- Same checkout.buy-now endpoint the product page uses — skips the cart
                     and takes the customer straight to checkout for just this one item. --}}
                <form action="{{ route('checkout.buy-now') }}" method="POST" class="mt-2" data-show-loader>
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <input type="hidden" name="quantity" value="1">
                    <button type="submit" data-loader-message="Preparing your order&hellip;"
                        style="background-image: {{ $orderButtonGradient }}; color: {{ $orderButtonTextColor }};"
                        class="inline-flex items-center gap-1 bg-[length:200%_auto] hover:bg-right text-[11px] font-bold pl-2 pr-3 py-1 rounded-full shadow-sm hover:shadow-md transition-all duration-500">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z"/></svg>
                        {{ setting('buy_now_button_text', 'Buy Now') }}
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>
