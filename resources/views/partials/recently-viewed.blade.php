{{-- "Recently viewed" — this visitor's own browsing (Personalization::recentlyViewed), as one
     swipeable row of compact cards. Nothing renders for a first-time visitor. Light on
     purpose: lazy thumbnails, no per-card scripts. --}}
@if(($items ?? collect())->count() >= ($min ?? 1))
<style>
    .rv-row { display: flex; gap: .75rem; overflow-x: auto; scroll-snap-type: x mandatory; padding: .25rem .1rem .75rem; scrollbar-width: thin; }
    .rv-card { flex: 0 0 9.5rem; scroll-snap-align: start; border-radius: 1rem; background: #fff; border: 1px solid #eef0f4; padding: .6rem; transition: transform .15s, box-shadow .15s; }
    .rv-card:hover { transform: translateY(-2px); box-shadow: 0 10px 24px -14px rgba(15, 23, 42, .35); }
    .dark .rv-card { background: #111827; border-color: #263044; }
    .rv-img { aspect-ratio: 1; border-radius: .75rem; background: #f6f7fb; overflow: hidden; }
    .dark .rv-img { background: #1f2937; }
    .rv-img img { width: 100%; height: 100%; object-fit: contain; }
    .rv-name { margin-top: .5rem; font-size: .8rem; line-height: 1.3; color: #374151; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; min-height: 2.1rem; }
    .dark .rv-name { color: #e5e7eb; }
    .rv-price { margin-top: .25rem; font-size: .9rem; font-weight: 800; color: #111827; }
    .dark .rv-price { color: #fff; }
    .rv-price s { font-size: .72rem; font-weight: 500; color: #9ca3af; margin-left: .25rem; }
    .rv-out { font-size: .7rem; font-weight: 700; color: #dc2626; }
    @media (min-width: 768px) { .rv-card { flex-basis: 10.5rem; } }
</style>
<section class="{{ $class ?? '' }}" aria-labelledby="rv-title">
    <div class="flex items-end justify-between mb-3">
        <h2 id="rv-title" class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $title ?? 'Recently viewed' }}</h2>
        <span class="text-xs text-gray-400 dark:text-gray-500">Pick up where you left off</span>
    </div>
    <div class="rv-row" data-track-list="Recently viewed">
        @foreach($items as $p)
            @php $now = (float) $p->final_price; $was = (float) $p->price; @endphp
            <a href="{{ route('products.show', $p) }}" class="rv-card">
                <div class="rv-img">
                    @if($p->image)<img src="{{ image_thumb($p->image, 200) }}" alt="{{ $p->name }}" loading="lazy" decoding="async" width="150" height="150">@endif
                </div>
                <p class="rv-name">{{ $p->name }}</p>
                @if($p->available_stock > 0)
                    <p class="rv-price">৳{{ number_format($now) }}@if($was > $now)<s>৳{{ number_format($was) }}</s>@endif</p>
                @else
                    <p class="rv-out">Out of stock</p>
                @endif
            </a>
        @endforeach
    </div>
</section>
@endif
