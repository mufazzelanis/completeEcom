{{-- Buy actions for a product shown inside a blog post. Same endpoints as the shop cards (checkout.buy-now,
     toggleCartItem in layouts.app), so loaders, cart badge, toasts and GA/Meta tracking all just work.
     data-bp-act tells the reading tracker (blog/partials/tracker) which action the click was.
     $size: 'lg' (spotlight / sticky bar) or 'sm' (grid tiles, ranked rows) --}}
@php
    $size = $size ?? 'sm';
    $lg = $size === 'lg';
    $inStock = $product->available_stock > 0;
    $orderGradient = sprintf('linear-gradient(to right, %s, %s, %s)', brand_button_color('order_button_color_from'), brand_button_color('order_button_color_via'), brand_button_color('order_button_color_to'));
    $orderText = setting('order_button_text_color', '#ffffff');
    $buyLabel = setting('buy_now_button_text', 'Buy Now');
    $cartLabel = setting('add_to_cart_button_text', 'Add to Cart');
    $inCart = isset(visitor_product_ids('cart')[$product->id]);
    $cartSvg = '<svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>';
    $checkSvg = '<svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>';
    $btn = $lg ? 'h-11 px-4 text-sm rounded-xl' : 'h-9 px-3 text-xs rounded-lg';
@endphp
<div class="flex items-stretch gap-2 {{ $class ?? '' }}">
    @if($product->isVariable())
        <a href="{{ route('products.show', $product->slug) }}" data-bp-act="open"
           style="background-image: {{ $orderGradient }}; color: {{ $orderText }};"
           class="flex-1 inline-flex items-center justify-center gap-1.5 font-bold bg-[length:200%_auto] hover:bg-right shadow-sm hover:shadow-md transition-all duration-500 {{ $btn }}">
            Choose options
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
        </a>
    @elseif($inStock)
        <form action="{{ route('checkout.buy-now') }}" method="POST" class="flex-1 min-w-0 flex" data-show-loader>
            @csrf
            <input type="hidden" name="product_id" value="{{ $product->id }}">
            <input type="hidden" name="quantity" value="1">
            <button type="submit" data-bp-act="buy" data-loader-message="Preparing your order&hellip;"
                    style="background-image: {{ $orderGradient }}; color: {{ $orderText }};"
                    class="w-full inline-flex items-center justify-center gap-1.5 font-bold whitespace-nowrap bg-[length:200%_auto] hover:bg-right shadow-sm hover:shadow-md active:scale-[.97] transition-all duration-500 {{ $btn }}">
                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z"/></svg>
                {{ $buyLabel }}
            </button>
        </form>
        @if($product->isSimple())
            <button type="button" onclick="toggleCartItem({{ $product->id }}, this)" data-bp-act="cart"
                    data-in-cart="{{ $inCart ? 'true' : 'false' }}"
                    data-product-name="{{ $product->name }}"
                    data-product-price="{{ $product->final_price }}"
                    data-icon-default='{!! $cartSvg !!}' data-icon-added='{!! $checkSvg !!}'
                    data-label-default="{{ $cartLabel }}" data-label-added="Added"
                    title="{{ $inCart ? 'Remove from Cart' : $cartLabel }}" aria-label="{{ $cartLabel }} — {{ $product->name }}"
                    class="flex-shrink-0 inline-flex items-center justify-center gap-1.5 font-semibold border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-900 hover:border-orange-300 hover:text-orange-600 active:scale-95 transition {{ $lg ? 'h-11 px-3.5 rounded-xl text-sm' : 'h-9 w-9 rounded-lg' }}">
                <span class="quick-add-icon">{!! $inCart ? $checkSvg : $cartSvg !!}</span>
                @if($lg)<span class="quick-add-label hidden sm:inline">{{ $inCart ? 'Added' : $cartLabel }}</span>@endif
            </button>
        @endif
    @else
        <a href="{{ route('products.show', $product->slug) }}" data-bp-act="open"
           class="flex-1 inline-flex items-center justify-center gap-1.5 font-semibold border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-200 hover:border-orange-300 hover:text-orange-600 transition {{ $btn }}">
            Out of stock — view
        </a>
    @endif
</div>
