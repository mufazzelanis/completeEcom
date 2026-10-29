{{-- "Recently viewed" / "Continue shopping" — this visitor's own browsing
     (Personalization::recentlyViewed) as one swipeable row. No raw scrollbar: phones swipe,
     desktop gets round arrow buttons + soft edge fades that only show when there's more to
     see that way. Nothing renders for a first-time visitor. Page-local rv-* CSS, light + dark. --}}
@if(($items ?? collect())->count() >= ($min ?? 1))
<style>
    .rv-wrap { position: relative; }
    .rv-row { display: flex; gap: .85rem; overflow-x: auto; scroll-snap-type: x mandatory; scroll-behavior: smooth; padding: .35rem .15rem .5rem; scrollbar-width: none; -ms-overflow-style: none; }
    .rv-row::-webkit-scrollbar { display: none; }
    .rv-card { position: relative; flex: 0 0 9.75rem; scroll-snap-align: start; display: flex; flex-direction: column; border-radius: 1.1rem; background: #fff; border: 1px solid #eceef3; padding: .55rem; transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease; }
    .rv-card:hover { transform: translateY(-3px); box-shadow: 0 14px 30px -16px rgba(15, 23, 42, .35); border-color: #c7d2fe; }
    .dark .rv-card { background: #111827; border-color: #1f2937; }
    .dark .rv-card:hover { border-color: #4f46e5; box-shadow: 0 14px 30px -16px rgba(0, 0, 0, .7); }
    .rv-img { position: relative; aspect-ratio: 1; border-radius: .8rem; background: #fff; overflow: hidden; }
    .dark .rv-img { background: #fff; } /* product photos are shot on white — keep them on white */
    .rv-img img { width: 100%; height: 100%; object-fit: contain; padding: .45rem; transition: transform .35s ease; }
    .rv-card:hover .rv-img img { transform: scale(1.06); }
    .rv-off { position: absolute; top: .4rem; left: .4rem; padding: .1rem .45rem; border-radius: 999px; font-size: .65rem; font-weight: 800; color: #fff; background: linear-gradient(135deg, #ef4444, #f97316); }
    .rv-name { margin-top: .55rem; font-size: .8rem; font-weight: 500; line-height: 1.3; color: #374151; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; min-height: 2.1rem; }
    .dark .rv-name { color: #e5e7eb; }
    .rv-foot { margin-top: auto; padding-top: .35rem; display: flex; align-items: baseline; gap: .3rem; flex-wrap: wrap; }
    .rv-price { font-size: .95rem; font-weight: 800; color: #111827; }
    .dark .rv-price { color: #fff; }
    .rv-was { font-size: .72rem; color: #9ca3af; text-decoration: line-through; }
    .rv-out { font-size: .72rem; font-weight: 700; color: #dc2626; }
    .rv-nav { position: absolute; top: 40%; z-index: 2; width: 2.5rem; height: 2.5rem; border-radius: 999px; display: none; align-items: center; justify-content: center; background: #fff; color: #111827; border: 1px solid #e5e7eb; box-shadow: 0 8px 20px -8px rgba(15, 23, 42, .35); transition: opacity .2s, transform .2s; }
    .rv-nav:hover { transform: scale(1.08); }
    .dark .rv-nav { background: #1f2937; color: #f9fafb; border-color: #374151; }
    .rv-nav.prev { left: -.6rem; } .rv-nav.next { right: -.6rem; }
    .rv-nav[disabled] { opacity: 0; pointer-events: none; }
    .rv-fade { position: absolute; top: 0; bottom: .5rem; width: 3rem; pointer-events: none; transition: opacity .2s; z-index: 1; }
    .rv-fade.l { left: 0; background: linear-gradient(90deg, var(--rv-bg, #f3f4f6), transparent); }
    .rv-fade.r { right: 0; background: linear-gradient(-90deg, var(--rv-bg, #f3f4f6), transparent); }
    .dark .rv-fade { --rv-bg: #030712; }
    @media (min-width: 768px) { .rv-card { flex-basis: 10.75rem; } .rv-nav { display: flex; } }
</style>
<section class="{{ $class ?? '' }}" aria-labelledby="rv-title"
         x-data="{ atStart: true, atEnd: false,
            update() { const r = $refs.row; this.atStart = r.scrollLeft < 8; this.atEnd = r.scrollLeft + r.clientWidth >= r.scrollWidth - 8; },
            go(dir) { $refs.row.scrollBy({ left: dir * $refs.row.clientWidth * 0.85 }); } }"
         x-init="$nextTick(() => update())">
    <div class="flex items-end justify-between mb-3 gap-3">
        <div>
            <h2 id="rv-title" class="text-xl font-extrabold tracking-tight text-gray-900 dark:text-white flex items-center gap-2">
                <span class="w-1.5 h-5 rounded-full bg-gradient-to-b from-orange-500 to-red-500"></span>{{ $title ?? 'Recently viewed' }}
            </h2>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 ml-3.5">Pick up where you left off · {{ $items->count() }} {{ \Illuminate\Support\Str::plural('item', $items->count()) }}</p>
        </div>
    </div>
    <div class="rv-wrap">
        <div class="rv-fade l" x-show="!atStart" x-transition.opacity></div>
        <div class="rv-fade r" x-show="!atEnd" x-transition.opacity></div>
        <button type="button" class="rv-nav prev" @click="go(-1)" :disabled="atStart" aria-label="Scroll left">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <button type="button" class="rv-nav next" @click="go(1)" :disabled="atEnd" aria-label="Scroll right">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
        </button>
        <div class="rv-row" x-ref="row" @scroll.debounce.50ms="update()" data-track-list="Recently viewed">
            @foreach($items as $p)
                @php $now = (float) $p->final_price; $was = (float) $p->price; $off = $was > $now ? (int) round((1 - $now / $was) * 100) : 0; @endphp
                <a href="{{ route('products.show', $p) }}" class="rv-card">
                    <div class="rv-img">
                        @if($p->image)<img src="{{ image_thumb($p->image, 300) }}" alt="{{ $p->name }}" loading="lazy" decoding="async" width="160" height="160">@endif
                        @if($off >= 1 && $p->available_stock > 0)<span class="rv-off">-{{ $off }}%</span>@endif
                    </div>
                    <p class="rv-name">{{ $p->name }}</p>
                    <div class="rv-foot">
                        @if($p->available_stock > 0)
                            <span class="rv-price">৳{{ number_format($now) }}</span>
                            @if($off >= 1)<span class="rv-was">৳{{ number_format($was) }}</span>@endif
                        @else
                            <span class="rv-out">Out of stock</span>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif
