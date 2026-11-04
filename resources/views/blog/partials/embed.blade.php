{{-- A product block placed inside the article text (App\Support\BlogShop::render). not-prose: the
     typography plugin's article styles (link underlines, img margins) must not reach the cards. --}}
<div class="not-prose bshop my-7 sm:my-9" data-bshop-block>
    @if($layout === 'spotlight')
        <div class="space-y-3 sm:space-y-4">
            @foreach($products as $product)
                @include('blog.partials.product', ['variant' => 'spotlight', 'place' => 'inline'])
            @endforeach
        </div>
    @else
        <div class="flex items-center justify-between gap-3 mb-3">
            <p class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                🛍️ {{ $layout === 'list' ? 'Our top picks' : 'Shop the picks' }} <span class="text-gray-300 dark:text-gray-600">· {{ $products->count() }}</span>
            </p>
            @if($layout === 'carousel' && $products->count() > 2)
                <span class="text-[11px] text-gray-400 dark:text-gray-500 sm:hidden">Swipe →</span>
            @endif
        </div>
        @if($layout === 'list')
            <ol class="space-y-2.5 sm:space-y-3">
                @foreach($products as $product)
                    <li>@include('blog.partials.product', ['variant' => 'row', 'place' => 'inline', 'rank' => $loop->iteration])</li>
                @endforeach
            </ol>
        @elseif($layout === 'carousel')
            <div class="bshop-track -mx-3 px-3 sm:mx-0 sm:px-0 flex gap-3 overflow-x-auto pb-1">
                @foreach($products as $product)
                    <div class="w-[10.5rem] sm:w-48 flex-shrink-0">@include('blog.partials.product', ['variant' => 'tile', 'place' => 'inline'])</div>
                @endforeach
            </div>
        @else
            @php $cols = $products->count() === 2 ? 'sm:grid-cols-2' : ($products->count() === 4 ? 'sm:grid-cols-4' : 'sm:grid-cols-3'); @endphp
            <div class="grid grid-cols-2 {{ $cols }} gap-2.5 sm:gap-3">
                @foreach($products as $product)
                    @include('blog.partials.product', ['variant' => 'tile', 'place' => 'inline'])
                @endforeach
            </div>
        @endif
    @endif
</div>
