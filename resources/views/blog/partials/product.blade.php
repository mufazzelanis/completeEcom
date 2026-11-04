{{-- One product inside a blog post. $variant: spotlight | tile | row | mini; $place: tracker placement
     (inline / shelf / sidebar); $rank: number shown on ranked lists. Products come from
     App\Support\BlogShop::query() — reviews_avg_rating / reviews_count, flash price, brand. --}}
@php
    $variant = $variant ?? 'tile';
    $final = (float) $product->final_price;
    $base = (float) $product->price;
    $save = max(0, $base - $final);
    $pct = $base > 0 && $save > 0 ? (int) round($save / $base * 100) : 0;
    $rating = round((float) ($product->reviews_avg_rating ?? 0), 1);
    $reviews = (int) ($product->reviews_count ?? 0);
    $stock = $product->available_stock;
    $url = route('products.show', $product->slug);
    $note = $product->blog_note ?? null;
    $isFlash = $product->activeFlashSaleProduct && $product->activeFlashSaleProduct->isAvailable();
    $track = json_encode(tracking_item($product), JSON_UNESCAPED_UNICODE);
    $stars = function ($size) use ($rating) {
        $out = '';
        for ($i = 1; $i <= 5; $i++) {
            $fill = $rating >= $i ? 'text-amber-400' : ($rating >= $i - 0.5 ? 'text-amber-300' : 'text-gray-200 dark:text-gray-700');
            $out .= '<svg class="' . $size . ' ' . $fill . '" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>';
        }
        return $out;
    };
    $img = fn ($w, $class) => $product->image
        ? '<img src="' . e(image_thumb($product->image, $w)) . '" alt="' . e($product->name) . '" width="' . $w . '" height="' . $w . '" loading="lazy" decoding="async" class="' . $class . '">'
        : '<span class="absolute inset-0 flex items-center justify-center text-3xl opacity-30">🛍️</span>';
@endphp

@if($variant === 'spotlight')
<div data-bp="{{ $product->id }}" data-bp-place="{{ $place }}" data-track-item="{{ $track }}"
     class="bshop-card group relative grid grid-cols-[6.75rem_minmax(0,1fr)] sm:grid-cols-[13rem_minmax(0,1fr)] gap-3 sm:gap-5 p-3 sm:p-4 rounded-2xl bg-white dark:bg-gray-900 ring-1 ring-gray-200/80 dark:ring-gray-800">
    <a href="{{ $url }}" class="relative block aspect-square rounded-xl bg-gray-50 dark:bg-gray-800 overflow-hidden" tabindex="-1" aria-hidden="true">
        {!! $img(400, 'absolute inset-0 w-full h-full p-2 sm:p-3 object-contain text-[0px] transition-transform duration-500 group-hover:scale-105') !!}
        @if($pct > 0)
            <span class="absolute top-1.5 left-1.5 text-[10px] sm:text-[11px] font-extrabold text-white px-1.5 sm:px-2 py-0.5 rounded-full {{ $isFlash ? 'bg-gradient-to-r from-red-500 to-rose-500' : 'bg-gradient-to-r from-orange-500 to-amber-500' }}">{{ $isFlash ? '⚡ ' : '' }}-{{ $pct }}%</span>
        @endif
    </a>
    <div class="min-w-0 flex flex-col">
        <div class="flex items-center gap-1.5 flex-wrap text-[10px] sm:text-[11px] font-bold uppercase tracking-wider">
            <span class="inline-flex items-center gap-1 text-orange-600 dark:text-orange-400">
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M10 1l2.6 5.6 6.1.7-4.5 4.2 1.2 6L10 14.6 4.6 17.5l1.2-6L1.3 7.3l6.1-.7L10 1z"/></svg>
                {{ isset($rank) ? '#' . $rank . ' pick' : 'Featured pick' }}
            </span>
            @if($product->brand)<span class="text-gray-400 dark:text-gray-500">· {{ $product->brand->name }}</span>@endif
        </div>
        <a href="{{ $url }}" class="mt-1 text-sm sm:text-lg font-bold leading-snug text-gray-900 dark:text-gray-100 hover:text-orange-600 dark:hover:text-orange-400 line-clamp-2 sm:line-clamp-3 transition-colors">{{ $product->name }}</a>
        @if($reviews > 0)
            <div class="mt-1 flex items-center gap-1 text-[11px] text-gray-500 dark:text-gray-400">
                <span class="flex">{!! $stars('w-3.5 h-3.5') !!}</span>
                <span class="font-semibold text-gray-700 dark:text-gray-300">{{ number_format($rating, 1) }}</span>
                <span>({{ $reviews }})</span>
            </div>
        @endif
        <div class="mt-1.5 sm:mt-2 flex items-baseline gap-x-2 gap-y-1 flex-wrap">
            <span class="text-lg sm:text-2xl font-extrabold tracking-tight text-gray-900 dark:text-white leading-none">৳{{ number_format($final) }}</span>
            @if($save > 0)
                <span class="text-xs sm:text-sm text-gray-400 line-through leading-none">৳{{ number_format($base) }}</span>
                <span class="text-[10px] sm:text-xs font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-500/10 px-1.5 py-0.5 rounded-md leading-none">Save ৳{{ number_format($save) }}</span>
            @endif
        </div>
        @if($note)
            <p class="hidden sm:block mt-2.5 text-sm text-gray-600 dark:text-gray-300 leading-relaxed border-l-2 border-orange-300 dark:border-orange-500/60 pl-3">{{ $note }}</p>
        @endif
        @if($stock > 0 && $stock <= 5)
            <p class="mt-1.5 text-[11px] font-semibold text-orange-700 dark:text-orange-400">Only {{ $stock }} left in stock</p>
        @endif
        @include('blog.partials.buy', ['size' => 'lg', 'class' => 'mt-auto pt-3'])
    </div>
    @if($note)
        <p class="sm:hidden col-span-2 -mt-1 text-[13px] text-gray-600 dark:text-gray-300 leading-relaxed border-l-2 border-orange-300 dark:border-orange-500/60 pl-2.5">{{ $note }}</p>
    @endif
</div>

@elseif($variant === 'row')
<div data-bp="{{ $product->id }}" data-bp-place="{{ $place }}" data-track-item="{{ $track }}"
     class="bshop-card group flex gap-3 sm:gap-4 p-2.5 sm:p-3 rounded-2xl bg-white dark:bg-gray-900 ring-1 ring-gray-200/80 dark:ring-gray-800">
    <a href="{{ $url }}" class="relative flex-shrink-0 w-20 h-20 sm:w-28 sm:h-28 rounded-xl bg-gray-50 dark:bg-gray-800 overflow-hidden" tabindex="-1" aria-hidden="true">
        {!! $img(200, 'absolute inset-0 w-full h-full p-1.5 object-contain text-[0px]') !!}
        @if(isset($rank))
            <span class="absolute top-0 left-0 min-w-[1.6rem] h-6 px-1.5 flex items-center justify-center rounded-br-xl text-xs font-extrabold text-white {{ $rank === 1 ? 'bg-gradient-to-br from-amber-400 to-orange-500' : 'bg-gray-900/80 dark:bg-gray-700' }}">#{{ $rank }}</span>
        @endif
    </a>
    <div class="min-w-0 flex-1 flex flex-col">
        <a href="{{ $url }}" class="text-[13px] sm:text-base font-bold leading-snug text-gray-900 dark:text-gray-100 hover:text-orange-600 dark:hover:text-orange-400 line-clamp-2 transition-colors">{{ $product->name }}</a>
        @if($reviews > 0)
            <div class="mt-0.5 flex items-center gap-1 text-[11px] text-gray-500 dark:text-gray-400">
                <span class="flex">{!! $stars('w-3 h-3') !!}</span><span>({{ $reviews }})</span>
            </div>
        @endif
        @if($note)<p class="hidden sm:block mt-1 text-xs text-gray-500 dark:text-gray-400 line-clamp-2">{{ $note }}</p>@endif
        <div class="mt-auto pt-1.5 flex items-end justify-between gap-2 flex-wrap">
            <div class="flex items-baseline gap-1.5 flex-wrap">
                <span class="text-base sm:text-lg font-extrabold text-gray-900 dark:text-white leading-none">৳{{ number_format($final) }}</span>
                @if($save > 0)<span class="text-[11px] text-gray-400 line-through leading-none">৳{{ number_format($base) }}</span>@endif
                @if($pct > 0)<span class="text-[10px] font-bold text-emerald-700 dark:text-emerald-300">-{{ $pct }}%</span>@endif
            </div>
            @include('blog.partials.buy', ['size' => 'sm', 'class' => 'w-full sm:w-auto sm:min-w-[9.5rem]'])
        </div>
    </div>
</div>

@elseif($variant === 'mini')
<div data-bp="{{ $product->id }}" data-bp-place="{{ $place }}" data-track-item="{{ $track }}" class="group flex items-center gap-3">
    <a href="{{ $url }}" class="relative flex-shrink-0 w-14 h-14 rounded-xl bg-gray-50 dark:bg-gray-800 overflow-hidden" tabindex="-1" aria-hidden="true">
        {!! $img(200, 'absolute inset-0 w-full h-full p-1 object-contain text-[0px]') !!}
    </a>
    <div class="min-w-0 flex-1">
        <a href="{{ $url }}" class="block text-[13px] font-semibold leading-snug text-gray-800 dark:text-gray-100 group-hover:text-orange-600 dark:group-hover:text-orange-400 line-clamp-2 transition-colors">{{ $product->name }}</a>
        <p class="mt-0.5 text-sm font-extrabold text-gray-900 dark:text-white">৳{{ number_format($final) }}@if($save > 0) <s class="ml-1 text-[11px] font-medium text-gray-400">৳{{ number_format($base) }}</s>@endif</p>
    </div>
</div>

@else
<div data-bp="{{ $product->id }}" data-bp-place="{{ $place }}" data-track-item="{{ $track }}"
     class="bshop-card group h-full flex flex-col rounded-2xl bg-white dark:bg-gray-900 ring-1 ring-gray-200/80 dark:ring-gray-800 overflow-hidden">
    <a href="{{ $url }}" class="relative block aspect-square bg-gray-50 dark:bg-gray-800 overflow-hidden" tabindex="-1" aria-hidden="true">
        {!! $img(300, 'absolute inset-0 w-full h-full p-3 object-contain text-[0px] transition-transform duration-500 group-hover:scale-105') !!}
        @if($pct > 0)
            <span class="absolute top-2 left-2 text-[10px] font-extrabold text-white px-1.5 py-0.5 rounded-full {{ $isFlash ? 'bg-gradient-to-r from-red-500 to-rose-500' : 'bg-gradient-to-r from-orange-500 to-amber-500' }}">{{ $isFlash ? '⚡ ' : '' }}-{{ $pct }}%</span>
        @endif
        @if($rating > 0)
            <span class="absolute bottom-2 left-2 inline-flex items-center gap-0.5 bg-white/95 dark:bg-gray-900/90 text-gray-800 dark:text-gray-100 text-[10px] font-bold px-1.5 py-0.5 rounded-full shadow-sm">
                <svg class="w-3 h-3 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                {{ number_format($rating, 1) }}
            </span>
        @endif
    </a>
    <div class="flex flex-col flex-1 p-2.5 sm:p-3">
        @if($product->brand)<p class="text-[10px] font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500 truncate">{{ $product->brand->name }}</p>@endif
        <a href="{{ $url }}" class="text-xs sm:text-[13px] font-bold leading-snug text-gray-900 dark:text-gray-100 hover:text-orange-600 dark:hover:text-orange-400 line-clamp-2 min-h-[2.1rem] transition-colors">{{ $product->name }}</a>
        <div class="mt-1.5 flex items-baseline gap-1.5 flex-wrap">
            <span class="text-base font-extrabold text-gray-900 dark:text-white leading-none">৳{{ number_format($final) }}</span>
            @if($save > 0)<span class="text-[11px] text-gray-400 line-through leading-none">৳{{ number_format($base) }}</span>@endif
        </div>
        @if($save > 0)<p class="mt-1 text-[11px] font-semibold text-emerald-600 dark:text-emerald-400">Save ৳{{ number_format($save) }}</p>@endif
        @include('blog.partials.buy', ['size' => 'sm', 'class' => 'mt-auto pt-2.5'])
    </div>
</div>
@endif
