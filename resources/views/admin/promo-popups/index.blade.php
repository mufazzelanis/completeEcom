@extends('layouts.admin')
@section('title', 'Promo Popups')

@section('content')
@php
    $tz = setting('timezone', 'Asia/Dhaka');
    $tot = ['views' => $popups->sum('views'), 'clicks' => $popups->sum('clicks'), 'orders' => $popups->sum('orders'), 'revenue' => $popups->sum('revenue')];
    $live = $popups->filter(fn ($p) => $p->status()[0] === 'Live')->count();
@endphp
<style>
    .pl-head { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: .75rem; margin-bottom: 1rem; }
    .pl-new { display: inline-flex; align-items: center; gap: .4rem; padding: .6rem 1rem; border-radius: .8rem; font-size: .875rem; font-weight: 700; color: #fff; background: linear-gradient(135deg, #f97316, #c2410c); box-shadow: 0 8px 18px -8px #f97316; }
    .pl-sum { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: .6rem; margin-bottom: 1rem; }
    .pl-sum div { background: #fff; border: 1px solid #f1f5f9; border-radius: 1rem; padding: .75rem .9rem; }
    .pl-sum span { display: block; font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: #6b7280; }
    .pl-sum b { font-size: 1.2rem; font-weight: 800; color: #111827; }
    .pl-list { display: flex; flex-direction: column; gap: .75rem; }
    .pl-card { display: grid; grid-template-columns: 5.5rem minmax(0, 1fr) auto; gap: 1rem; align-items: center; background: #fff; border: 1px solid #eef2f7; border-radius: 1.1rem; padding: .9rem 1rem; }
    .pl-thumb { width: 5.5rem; height: 5.5rem; border-radius: .9rem; overflow: hidden; background: #f3f4f6; display: flex; align-items: center; justify-content: center; }
    .pl-thumb img { width: 100%; height: 100%; object-fit: cover; }
    .pl-thumb.is-text { color: #fff; font-weight: 900; font-size: .8rem; text-align: center; padding: .4rem; line-height: 1.2; }
    .pl-name { font-weight: 800; color: #111827; font-size: .98rem; }
    .pl-badge { font-size: .68rem; font-weight: 800; padding: .1rem .5rem; border-radius: 999px; }
    .pl-tags { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .4rem; }
    .pl-tags span { font-size: .7rem; padding: .12rem .5rem; border-radius: .5rem; background: #f3f4f6; color: #4b5563; }
    .pl-stats { display: grid; grid-template-columns: repeat(5, minmax(0, auto)); gap: .2rem 1.1rem; margin-top: .55rem; }
    .pl-stats div { font-size: .7rem; color: #9ca3af; }
    .pl-stats b { display: block; font-size: .95rem; color: #111827; font-weight: 800; }
    .pl-side { display: flex; flex-direction: column; align-items: flex-end; gap: .6rem; }
    .pl-acts { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: .3rem; }
    .pl-acts a, .pl-acts button { font-size: .75rem; font-weight: 700; padding: .35rem .6rem; border-radius: .6rem; border: 1px solid #e5e7eb; background: #fff; color: #374151; }
    .pl-acts a:hover, .pl-acts button:hover { border-color: #9ca3af; }
    .pl-acts .is-danger { color: #dc2626; }
    .pl-switch { position: relative; width: 2.75rem; height: 1.55rem; border-radius: 999px; background: #d1d5db; transition: background-color .2s; flex-shrink: 0; }
    .pl-switch::after { content: ''; position: absolute; top: .2rem; left: .2rem; width: 1.15rem; height: 1.15rem; border-radius: 999px; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,.25); transition: transform .2s; }
    .pl-switch.is-on { background: #10b981; }
    .pl-switch.is-on::after { transform: translateX(1.2rem); }
    .pl-empty { text-align: center; padding: 3rem 1rem; background: #fff; border: 1px dashed #e5e7eb; border-radius: 1.25rem; }
    .dark .pl-sum div, .dark .pl-card, .dark .pl-empty, .dark .pl-acts a, .dark .pl-acts button { background: #111827; border-color: #1f2937; }
    .dark .pl-sum b, .dark .pl-name, .dark .pl-stats b { color: #f9fafb; }
    .dark .pl-tags span { background: #1f2937; color: #d1d5db; }
    .dark .pl-acts a, .dark .pl-acts button { color: #e5e7eb; }
    @media (max-width: 1023px) { .pl-sum { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    @media (max-width: 767px) {
        .pl-sum { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .pl-card { grid-template-columns: 4.25rem minmax(0, 1fr); }
        .pl-thumb { width: 4.25rem; height: 4.25rem; }
        .pl-side { grid-column: 1 / -1; flex-direction: row-reverse; align-items: center; justify-content: space-between; }
        .pl-acts { justify-content: flex-start; }
        .pl-stats { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }
</style>

<div class="pl-head">
    <div>
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">Promo Popups</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Promotion popups on the store — picture, text or both, shown to the right visitors at the right moment.</p>
    </div>
    <a href="{{ route('admin.promo-popups.create') }}" class="pl-new">＋ New popup</a>
</div>

@if($popups->isNotEmpty())
<div class="pl-sum">
    <div><span>Live now</span><b style="color:#059669">{{ $live }}</b></div>
    <div><span>Shown</span><b>{{ number_format($tot['views']) }}</b></div>
    <div><span>Clicks</span><b>{{ number_format($tot['clicks']) }}</b> <small class="text-xs text-gray-400">{{ $tot['views'] ? round($tot['clicks'] / $tot['views'] * 100, 1) : 0 }}%</small></div>
    <div><span>Orders after click</span><b>{{ number_format($tot['orders']) }}</b></div>
    <div><span>Sales after click</span><b>৳{{ number_format($tot['revenue']) }}</b></div>
</div>
@endif

<div class="pl-list">
@forelse($popups as $p)
    @php
        [$stLabel, $stColor] = $p->status();
        $pages = collect($p->pages ?: ['all'])->map(fn ($k) => \App\Models\PromoPopup::PAGES[$k] ?? $k)->implode(', ');
        $narrow = count($p->category_ids ?? []) + count($p->product_ids ?? []);
        $trigger = match ($p->trigger) {
            'delay' => 'After ' . $p->trigger_value . 's',
            'scroll' => 'At ' . $p->trigger_value . '% scroll',
            'views' => 'After ' . max(1, $p->trigger_value) . ' product views',
            default => 'Exit intent',
        };
        $freq = $p->frequency === 'days' ? 'Every ' . $p->frequency_days . ' days' : \App\Models\PromoPopup::FREQUENCIES[$p->frequency];
    @endphp
    <div class="pl-card" x-data="{ on: {{ $p->is_active ? 'true' : 'false' }}, st: @js([$stLabel, $stColor]), busy: false }">
        <div class="pl-thumb {{ $p->image ? '' : 'is-text' }}" @unless($p->image) style="background: linear-gradient(145deg, {{ $p->accent_color }}, #111827)" @endunless>
            @if($p->image)<img src="{{ image_thumb($p->image, 200) }}" alt="" loading="lazy">@else{{ \Illuminate\Support\Str::limit($p->title ?: $p->body, 28) }}@endif
        </div>
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <span class="pl-name truncate">{{ $p->name }}</span>
                <span class="pl-badge" :style="'background:' + st[1] + '1a;color:' + st[1]" x-text="st[0]">{{ $stLabel }}</span>
                @if($p->priority)<span class="pl-badge" style="background:#f3f4f6;color:#6b7280">Priority {{ $p->priority }}</span>@endif
            </div>
            <div class="pl-tags">
                <span>📄 {{ $pages }}{{ $narrow ? ' · ' . $narrow . ' selected' : '' }}</span>
                @if($p->device !== 'all')<span>{{ $p->device === 'mobile' ? '📱 Mobile' : '💻 Desktop' }}</span>@endif
                @if($p->audience !== 'all')<span>👤 {{ \App\Models\PromoPopup::AUDIENCES[$p->audience] }}</span>@endif
                @if($p->source !== 'all')<span>🔗 {{ \App\Models\PromoPopup::SOURCES[$p->source] }}</span>@endif
                <span>⏱ {{ $trigger }}</span>
                <span>🔁 {{ $freq }}</span>
                @if($p->starts_at || $p->ends_at)<span>📅 {{ $p->starts_at?->timezone($tz)->format('d M') ?? 'now' }} → {{ $p->ends_at?->timezone($tz)->format('d M, g:i A') ?? 'no end' }}</span>@endif
                @if($p->coupon_code)<span>🏷 {{ $p->coupon_code }}</span>@endif
            </div>
            <div class="pl-stats">
                <div><b>{{ number_format($p->views) }}</b>shown</div>
                <div><b>{{ number_format($p->clicks) }}</b>clicks</div>
                <div><b>{{ $p->ctr() }}%</b>click rate</div>
                <div><b>{{ number_format($p->orders) }}</b>orders</div>
                <div><b>৳{{ number_format($p->revenue) }}</b>sales</div>
            </div>
            @if($p->stats_since)<p class="text-[11px] text-gray-400 mt-1">Results since {{ $p->stats_since->timezone($tz)->format('d M Y') }}</p>@endif
        </div>
        <div class="pl-side">
            <button type="button" class="pl-switch" :class="on && 'is-on'" :disabled="busy" role="switch" :aria-checked="on" aria-label="Switch {{ $p->name }} on or off"
                    @click="busy = true; fetch(@js(route('admin.promo-popups.toggle', $p)), { method: 'PATCH', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content } })
                        .then(r => r.ok ? r.json() : Promise.reject()).then(d => { on = d.active; st = d.status; window.showToast && showToast(d.active ? 'Popup is on' : 'Popup switched off'); })
                        .catch(() => window.showToast && showToast('Could not change it — try again', 'error')).finally(() => busy = false)"></button>
            <div class="pl-acts">
                <a href="{{ route('admin.promo-popups.edit', $p) }}">✏️ Edit</a>
                <a href="{{ url('/') }}?promo_preview={{ $p->id }}" target="_blank" rel="noopener" data-turbo="false">👁 Preview</a>
                <form method="POST" action="{{ route('admin.promo-popups.duplicate', $p) }}">@csrf<button type="submit">⧉ Copy</button></form>
                <form method="POST" action="{{ route('admin.promo-popups.reset', $p) }}" onsubmit="return uiConfirm(event, 'Reset the results of “{{ addslashes($p->name) }}” to zero?')">@csrf<button type="submit">↺ Reset</button></form>
                <form method="POST" action="{{ route('admin.promo-popups.destroy', $p) }}" onsubmit="return uiConfirm(event, 'Delete “{{ addslashes($p->name) }}”?')">@csrf @method('DELETE')<button type="submit" class="is-danger">🗑</button></form>
            </div>
        </div>
    </div>
@empty
    <div class="pl-empty">
        <p style="font-size:2.25rem">🎯</p>
        <p class="font-bold text-gray-800 dark:text-gray-100 mt-1">No promo popups yet</p>
        <p class="text-sm text-gray-500 mt-1 mb-4">Show an offer to visitors on product pages — a picture, a message, a coupon or a countdown.</p>
        <a href="{{ route('admin.promo-popups.create') }}" class="pl-new">＋ Create your first popup</a>
    </div>
@endforelse
</div>
@endsection
