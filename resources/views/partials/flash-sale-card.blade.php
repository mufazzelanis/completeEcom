@php
    $basePrice = $fsp->product->sale_price ?? $fsp->product->price;
    $flashPrice = $fsp->sale_price;
    $discountPct = round((($basePrice - $flashPrice) / $basePrice) * 100);
    $soldPct = $fsp->stock_limit > 0 ? min(100, round(($fsp->sold_count / $fsp->stock_limit) * 100)) : 0;
    $rating = $fsp->product->reviews->avg('rating') ?? 0;
@endphp
<a href="{{ route('products.show', $fsp->product->slug) }}" class="flex-shrink-0 w-32 sm:w-36 md:w-40 group">
    <div class="relative overflow-hidden bg-gray-50 rounded-lg aspect-square mb-2">
        @if($fsp->product->image)
            <img src="{{ Storage::url($fsp->product->image) }}" alt="{{ $fsp->product->name }}"
                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-400">
        @else
            <div class="w-full h-full flex items-center justify-center">
                <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
        @endif
        <span class="absolute top-0 left-0 bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-br-lg">
            -{{ $discountPct }}%
        </span>
    </div>
    <p class="text-sm font-bold text-red-500">৳{{ number_format($flashPrice) }}</p>
    <p class="text-[10px] text-gray-400 line-through">৳{{ number_format($basePrice) }}</p>
    <div class="mt-1.5 bg-orange-100 rounded-full h-3 overflow-hidden relative">
        <div class="bg-gradient-to-r from-orange-400 to-red-500 h-full rounded-full transition-all" style="width: {{ max(10, $soldPct) }}%"></div>
        <span class="absolute inset-0 flex items-center justify-center text-[10px] font-bold text-gray-700">
            @if($fsp->stock_limit > 0 && $soldPct >= 90)
                Almost Gone!
            @elseif($fsp->stock_limit > 0)
                {{ $soldPct }}% sold
            @else
                Hot
            @endif
        </span>
    </div>
</a>
