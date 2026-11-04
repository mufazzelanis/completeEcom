@php
    $sale = $sale ?? $fsp->flashSale;
    $product = $fsp->product;
    $upcoming = $sale->status === 'upcoming';
    $soldOut = ! $fsp->isAvailable();
    $state = $fsp->stock_state;
    $showBar = $sale->show_stock_bar && ! $upcoming;
@endphp
{{-- object-contain + padding, same reasoning as partials.product-card — shows the
     whole product regardless of the uploaded photo's own aspect ratio. --}}
<a href="{{ route('products.show', $product->slug) }}"
   class="fs-card group relative flex flex-col bg-white rounded-2xl ring-1 ring-gray-100 dark:ring-gray-800 overflow-hidden {{ ($wide ?? false) ? 'w-full' : 'w-[9.5rem] sm:w-44 flex-shrink-0' }}">
    <div class="relative bg-gray-50 aspect-square overflow-hidden">
        @if($product->image)
            <img src="{{ image_thumb($product->image, 400) }}" width="400" height="400" alt="{{ $product->name }}" loading="lazy" decoding="async"
                 class="absolute inset-0 w-full h-full p-3 object-contain text-[0px] transition-transform duration-500 group-hover:scale-105 {{ $soldOut ? 'grayscale opacity-60' : '' }}">
        @else
            <div class="w-full h-full flex items-center justify-center">
                <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
        @endif

        @if($fsp->discount_percent > 0)
            <span class="fs-grad fs-shine absolute top-0 left-0 text-white text-[11px] font-extrabold px-2 py-1 rounded-br-xl shadow-sm">-{{ $fsp->discount_percent }}%</span>
        @endif
        @if($fsp->is_featured && ! $soldOut)
            <span class="absolute top-1.5 right-1.5 inline-flex items-center gap-0.5 bg-white/95 dark:bg-gray-900/90 text-[10px] font-bold px-1.5 py-0.5 rounded-full shadow-sm fs-accent">
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                Top deal
            </span>
        @endif
        @if($soldOut)
            <div class="absolute inset-0 flex items-center justify-center">
                <span class="bg-gray-900/85 text-white text-xs font-bold uppercase tracking-wider px-3 py-1.5 rounded-full">Sold out</span>
            </div>
        @endif
    </div>

    <div class="flex flex-col flex-1 p-2.5">
        <p class="text-xs text-gray-700 leading-snug line-clamp-2 min-h-[2.1rem]">{{ $product->name }}</p>

        <div class="mt-1.5 flex items-baseline gap-1.5 flex-wrap">
            <span class="text-base font-extrabold tracking-tight text-gray-900 leading-none">৳{{ number_format($fsp->sale_price) }}</span>
            @if($fsp->savings > 0)
                <span class="text-[11px] text-gray-400 line-through leading-none">৳{{ number_format($fsp->base_price) }}</span>
            @endif
        </div>
        @if($fsp->savings > 0)
            <p class="mt-1 text-[11px] font-semibold text-emerald-600 dark:text-emerald-400">Save ৳{{ number_format($fsp->savings) }}</p>
        @endif

        <div class="mt-auto pt-2">
            @if($upcoming)
                <div class="flex items-center justify-center gap-1 rounded-lg bg-gray-100 text-gray-600 text-[11px] font-semibold py-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z"/></svg>
                    Deal starts soon
                </div>
            @elseif($showBar && $state !== 'unlimited')
                <div class="fs-bar {{ in_array($state, ['almost_gone', 'selling_fast']) ? 'is-hot' : '' }}">
                    <i style="width: {{ max(6, $fsp->sold_percent) }}%"></i>
                </div>
                <p class="mt-1 text-[10.5px] font-semibold {{ $state === 'almost_gone' ? 'fs-accent' : 'text-gray-500' }}">
                    @switch($state)
                        @case('sold_out') All {{ $fsp->stock_limit }} claimed @break
                        @case('almost_gone') 🔥 Only {{ $fsp->remaining }} left @break
                        @case('selling_fast') {{ $fsp->sold_percent }}% claimed @break
                        @default {{ $fsp->remaining }} left at this price
                    @endswitch
                </p>
            @elseif($showBar)
                <p class="text-[10.5px] font-semibold fs-accent">⚡ Limited-time price</p>
            @endif
        </div>
    </div>
</a>
