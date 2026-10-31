@extends('layouts.admin')
@section('title', 'SEO Insights')

@section('content')
@php
    $fmt = fn ($n) => number_format((float) $n);
    $pct = fn ($a, $b) => $b > 0 ? (($a - $b) / $b * 100) : null;
    $link = fn (array $set = []) => route('admin.seo-insights.index', array_filter(array_merge(request()->only(['period', 'tab', 'f', 'q']), $set), fn ($v) => $v !== null && $v !== ''));
    $stageLabel = ['top' => ['Top 3', '#059669', '#ecfdf5'], 'page1' => ['Page 1', '#2563eb', '#eff6ff'], 'page2' => ['Page 2 — almost there', '#d97706', '#fffbeb'], 'deep' => ['Page 3+', '#6b7280', '#f3f4f6']];
@endphp
<style>
    .si-card { background: #fff; border: 1px solid #eef2f7; border-radius: 1rem; }
    .si-kpi { padding: .9rem 1rem; }
    .si-kpi span { display: block; font-size: .72rem; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: .04em; }
    .si-kpi b { display: block; font-size: 1.5rem; font-weight: 800; color: #111827; line-height: 1.2; margin-top: .15rem; }
    .si-kpi i { font-style: normal; font-size: .75rem; font-weight: 700; }
    .si-up { color: #059669; } .si-down { color: #dc2626; } .si-flat { color: #9ca3af; }
    .si-seg { display: inline-flex; background: #f3f4f6; border-radius: .7rem; padding: .2rem; gap: .15rem; }
    .si-seg a { padding: .35rem .75rem; border-radius: .55rem; font-size: .8rem; font-weight: 700; color: #6b7280; }
    .si-seg a.is-on { background: #fff; color: #111827; box-shadow: 0 1px 2px rgba(0,0,0,.08); }
    .si-tabs { display: flex; gap: .3rem; border-bottom: 1px solid #eef2f7; overflow-x: auto; }
    .si-tabs a { padding: .7rem 1rem; font-size: .88rem; font-weight: 700; color: #6b7280; border-bottom: 2px solid transparent; white-space: nowrap; }
    .si-tabs a.is-on { color: #ea580c; border-color: #ea580c; }
    .si-prod { padding: 1rem 1.1rem; }
    .si-prod + .si-prod { border-top: 1px solid #f1f5f9; }
    .si-thumb { width: 3.25rem; height: 3.25rem; border-radius: .8rem; background: #f8fafc; border: 1px solid #eef2f7; overflow: hidden; flex-shrink: 0; display: flex; align-items: center; justify-content: center; }
    .si-thumb img { max-width: 100%; max-height: 100%; object-fit: contain; }
    .si-badge { display: inline-flex; align-items: center; font-size: .68rem; font-weight: 800; padding: .12rem .5rem; border-radius: 999px; white-space: nowrap; }
    .si-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .4rem; }
    .si-stats div { background: #f8fafc; border-radius: .6rem; padding: .35rem .5rem; text-align: center; }
    .si-stats b { display: block; font-size: .95rem; font-weight: 800; color: #111827; }
    .si-stats span { font-size: .62rem; font-weight: 700; color: #6b7280; text-transform: uppercase; }
    .si-chip { display: inline-flex; align-items: center; gap: .3rem; padding: .2rem .55rem; border-radius: 999px; background: #f1f5f9; font-size: .75rem; color: #334155; }
    .si-chip b { font-weight: 800; }
    .si-tip { display: flex; gap: .5rem; font-size: .82rem; color: #374151; line-height: 1.4; }
    .si-btn { display: inline-flex; align-items: center; gap: .3rem; padding: .4rem .75rem; border-radius: .6rem; font-size: .78rem; font-weight: 700; border: 1px solid #e5e7eb; background: #fff; color: #374151; white-space: nowrap; }
    .si-btn:hover { border-color: #9ca3af; }
    .si-btn-main { background: #ea580c; border-color: #ea580c; color: #fff; }
    .si-btn-main:hover { background: #c2410c; }
    .si-fchip { display: inline-flex; align-items: center; gap: .35rem; padding: .35rem .75rem; border-radius: 999px; font-size: .8rem; font-weight: 600; border: 1px solid #e5e7eb; background: #fff; color: #4b5563; white-space: nowrap; }
    .si-fchip.is-on { background: #111827; border-color: #111827; color: #fff; }
    .si-fchip i { font-style: normal; font-size: .7rem; font-weight: 800; padding: 0 .4rem; border-radius: 999px; background: #f3f4f6; color: #374151; }
    .si-fchip.is-on i { background: rgba(255,255,255,.2); color: #fff; }
    .si-table th { font-size: .7rem; text-transform: uppercase; letter-spacing: .04em; color: #6b7280; font-weight: 700; padding: .6rem .75rem; text-align: left; background: #f8fafc; white-space: nowrap; }
    .si-table td { padding: .6rem .75rem; font-size: .85rem; border-top: 1px solid #f1f5f9; vertical-align: middle; }
    .si-step { display: flex; gap: .75rem; }
    .si-step b.n { width: 1.6rem; height: 1.6rem; border-radius: 999px; background: #ea580c; color: #fff; font-size: .8rem; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .si-in { border: 1px solid #e5e7eb; border-radius: .7rem; padding: .55rem .75rem; font-size: .875rem; background: #fff; width: 100%; }
    .dark .si-card, .dark .si-btn, .dark .si-fchip, .dark .si-in { background: #111827; border-color: #1f2937; color: #e5e7eb; }
    .dark .si-kpi b, .dark .si-stats b { color: #f9fafb; }
    .dark .si-stats div, .dark .si-chip, .dark .si-table th { background: #1f2937; color: #d1d5db; }
    .dark .si-seg { background: #1f2937; } .dark .si-seg a.is-on { background: #374151; color: #fff; }
    .dark .si-tip { color: #d1d5db; }
    @media (max-width: 767px) {
        .si-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; }
        .si-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .si-table .si-hide-m { display: none; }
    }
</style>

<div class="flex flex-wrap items-start justify-between gap-3 mb-5">
    <div>
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">🔎 SEO Insights</h1>
        <p class="text-sm text-gray-500 mt-0.5">
            What people search on Google, where your products rank — and what to fix next.
            @if($hasData) Data up to <b>{{ $end->format('d M Y') }}</b>@endif
            @if($lastSync) · synced {{ \Illuminate\Support\Carbon::parse($lastSync)->diffForHumans() }}@endif
        </p>
    </div>
    @if($hasData)
    <div class="flex flex-wrap items-center gap-2">
        <div class="si-seg">
            @foreach([7 => '7 days', 28 => '28 days', 90 => '3 months'] as $d => $l)
                <a href="{{ $link(['period' => $d]) }}" class="{{ $period === $d ? 'is-on' : '' }}">{{ $l }}</a>
            @endforeach
        </div>
        @if($connected)
        <form method="POST" action="{{ route('admin.seo-insights.sync') }}" data-show-loader data-loader-message="Loading from Google…">@csrf<input type="hidden" name="days" value="5">
            <button class="si-btn">↻ Sync now</button>
        </form>
        @endif
    </div>
    @endif
</div>

@if(session('success'))<div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">{{ session('success') }}</div>@endif
@if(session('error'))<div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">{{ session('error') }}</div>@endif

{{-- ───────── setup ───────── --}}
<details class="si-card mb-5" @if(! $hasData) open @endif>
    <summary class="px-5 py-4 cursor-pointer font-bold text-gray-800 dark:text-gray-100 flex items-center justify-between">
        <span>{{ $connected ? '✅ Google Search Console connected' : '🔌 Connect Google Search Console (one-time, ~10 minutes)' }}</span>
        <span class="text-xs font-semibold text-gray-400">{{ $connected ? 'settings' : 'step by step' }}</span>
    </summary>
    <div class="px-5 pb-5 grid lg:grid-cols-2 gap-6">
        <div class="space-y-3 text-sm text-gray-700 dark:text-gray-300">
            <div class="si-step"><b class="n">1</b><p>Open <b>console.cloud.google.com</b> (same Google account as Search Console) → top bar <b>Select a project → New project</b> → name it “Mitavin SEO” → Create.</p></div>
            <div class="si-step"><b class="n">2</b><p><b>APIs &amp; Services → Library</b> → search “<b>Google Search Console API</b>” → <b>Enable</b>.</p></div>
            <div class="si-step"><b class="n">3</b><p><b>IAM &amp; Admin → Service Accounts → Create service account</b> → any name (e.g. “seo-reader”) → Create and continue → Done (no roles needed).</p></div>
            <div class="si-step"><b class="n">4</b><p>Click the new service account → <b>Keys → Add key → Create new key → JSON</b>. A small .json file downloads — upload it on the right.</p></div>
            <div class="si-step"><b class="n">5</b><p>In <b>Search Console → Settings → Users and permissions → Add user</b>: paste the service account's email (ends with <code>iam.gserviceaccount.com</code>)@if($serviceEmail): <b class="select-all">{{ $serviceEmail }}</b>@endif — permission <b>Restricted</b> → Add.</p></div>
            <div class="si-step"><b class="n">6</b><p>Press <b>Connect</b>, then <b>Load last 90 days</b>. After that it updates by itself every morning.</p></div>
            <p class="text-xs text-gray-400">Access is read-only. The key is stored privately on your server (never public), and you can disconnect any time.</p>
        </div>
        <div class="space-y-3">
            <form method="POST" action="{{ route('admin.seo-insights.connect') }}" enctype="multipart/form-data" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Service-account key (.json)</label>
                    <input type="file" name="key" accept=".json,application/json" class="si-in">
                    @if($serviceEmail)<p class="text-xs text-gray-500 mt-1">Current: {{ $serviceEmail }} — choose a file only to replace it.</p>@endif
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Search Console property</label>
                    <input type="text" name="site_url" value="{{ $siteUrl }}" class="si-in" placeholder="sc-domain:mitavin.com">
                    <p class="text-xs text-gray-400 mt-1">Domain property: <code>sc-domain:mitavin.com</code> · URL-prefix property: <code>https://mitavin.com/</code> (as shown in Search Console's property menu).</p>
                </div>
                <button class="si-btn si-btn-main">🔌 {{ $connected ? 'Save & test' : 'Connect' }}</button>
            </form>
            @if($connected)
            <div class="flex flex-wrap gap-2 pt-2 border-t border-gray-100 dark:border-gray-800">
                <form method="POST" action="{{ route('admin.seo-insights.test') }}">@csrf<button class="si-btn">🧪 Test connection</button></form>
                <form method="POST" action="{{ route('admin.seo-insights.sync') }}" data-show-loader data-loader-message="Loading 90 days from Google…">@csrf<input type="hidden" name="days" value="90"><button class="si-btn si-btn-main">⬇ Load last 90 days</button></form>
                <form method="POST" action="{{ route('admin.seo-insights.disconnect') }}" onsubmit="return uiConfirm(event, 'Disconnect Search Console?')">@csrf<button class="si-btn" style="color:#dc2626">Disconnect</button></form>
            </div>
            @endif
        </div>
    </div>
</details>

@if($hasData)
    {{-- ───────── KPIs + trend ───────── --}}
    @php
        $ctrNow = $now->i ? $now->c / $now->i * 100 : 0;
        $ctrBefore = $before->i ? $before->c / $before->i * 100 : 0;
        $kpis = [
            ['Clicks', $fmt($now->c), $pct($now->c, $before->c), false],
            ['Impressions', $fmt($now->i), $pct($now->i, $before->i), false],
            ['CTR', number_format($ctrNow, 1) . '%', $ctrBefore ? $ctrNow - $ctrBefore : null, false, 'pts'],
            ['Avg. position', number_format($now->p, 1), $before->p ? $before->p - $now->p : null, false, 'pos'],
        ];
        $maxI = max(1, (int) $daily->max('i')); $maxC = max(1, (int) $daily->max('c')); $n = max(1, $daily->count() - 1);
        $pts = $daily->values()->map(fn ($d, $k) => round($k / $n * 600, 1) . ',' . round(110 - $d->i / $maxI * 100, 1))->implode(' ');
    @endphp
    <div class="grid gap-3 mb-4 si-kpis" style="grid-template-columns: repeat(4, minmax(0, 1fr));">
        @foreach($kpis as $k)
            @php $d = $k[2]; if ($d !== null && abs($d) < (($k[4] ?? null) ? 0.1 : 0.5)) $d = null; $cls = $d === null ? 'si-flat' : ($d > 0 ? 'si-up' : 'si-down'); @endphp
            <div class="si-card si-kpi">
                <span>{{ $k[0] }}</span><b>{{ $k[1] }}</b>
                <i class="{{ $cls }}">
                    @if($d === null) — @else {{ $d > 0 ? '▲' : ($d < 0 ? '▼' : '') }}
                        {{ ($k[4] ?? null) === 'pos' ? number_format(abs($d), 1) . ' places' : (($k[4] ?? null) === 'pts' ? number_format(abs($d), 1) . ' pts' : number_format(abs($d), 0) . '%') }}
                    @endif
                    <span style="color:#9ca3af;font-weight:500"> vs previous {{ $period }} days</span>
                </i>
            </div>
        @endforeach
    </div>
    <div class="si-card p-4 mb-5">
        <div class="flex items-center justify-between text-xs text-gray-500 mb-1"><span>Impressions (line) &amp; clicks (bars) per day</span><span>{{ $from->format('d M') }} – {{ $end->format('d M') }}</span></div>
        <svg viewBox="0 0 600 120" preserveAspectRatio="none" style="width:100%;height:110px">
            @foreach($daily->values() as $k => $d)
                @php $h = $d->c / $maxC * 60; @endphp
                <rect x="{{ round($k / $n * 600 - 3, 1) }}" y="{{ round(120 - $h, 1) }}" width="6" height="{{ round($h, 1) }}" rx="2" fill="#93c5fd"><title>{{ \Illuminate\Support\Carbon::parse($d->date)->format('d M') }}: {{ $d->c }} clicks, {{ $d->i }} impressions</title></rect>
            @endforeach
            <polyline points="{{ $pts }}" fill="none" stroke="#7c3aed" stroke-width="2" vector-effect="non-scaling-stroke"/>
        </svg>
    </div>

    {{-- ───────── tabs ───────── --}}
    <div class="si-card overflow-hidden">
        <div class="si-tabs px-3">
            <a href="{{ $link(['tab' => 'plan', 'f' => null, 'q' => null]) }}" class="{{ $tab === 'plan' ? 'is-on' : '' }}">🎯 Action plan — products</a>
            <a href="{{ $link(['tab' => 'keywords']) }}" class="{{ $tab === 'keywords' ? 'is-on' : '' }}">🔑 Keywords</a>
            <a href="{{ $link(['tab' => 'pages', 'f' => null, 'q' => null]) }}" class="{{ $tab === 'pages' ? 'is-on' : '' }}">📄 Category, brand &amp; blog pages</a>
        </div>

        @if($tab === 'plan')
            <p class="px-5 pt-4 text-sm text-gray-500">Products ordered by <b>how much there is to win</b> — many impressions just below the top. Work from the top down: fix the tips, then watch the position move here.</p>
            @forelse($plan as $row)
                @php $p = $row->product; $st = $stageLabel[$row->stage]; $topQ = $row->queries->first(); @endphp
                <div class="si-prod" x-data="{ open: {{ $loop->index < 3 ? 'true' : 'false' }} }">
                    <div class="flex items-start gap-3">
                        <div class="si-thumb">@if($p->image)<img src="{{ image_thumb($p->image, 96) }}" alt="" loading="lazy">@endif</div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="text-xs font-bold text-gray-400">#{{ $loop->iteration }}</span>
                                <span class="si-badge" style="color:{{ $st[1] }};background:{{ $st[2] }}">{{ $st[0] }}</span>
                                @if(! $p->is_active)<span class="si-badge" style="color:#b91c1c;background:#fee2e2">Inactive</span>@endif
                            </div>
                            <button type="button" @click="open = !open" class="block text-left font-bold text-gray-900 dark:text-gray-100 leading-snug mt-0.5 hover:text-orange-600">{{ $p->name }}</button>
                            <p class="text-xs text-gray-500 mt-0.5">⭐ {{ $p->reviews_count }} review{{ $p->reviews_count == 1 ? '' : 's' }}@if($p->reviews_avg_rating) ({{ number_format($p->reviews_avg_rating, 1) }})@endif · top search: <b>{{ $topQ->q ?? '—' }}</b></p>
                        </div>
                        <div class="hidden sm:flex flex-shrink-0 gap-1.5">
                            <a href="{{ route('admin.products.edit', $p->id) }}" class="si-btn si-btn-main">✏️ Edit</a>
                        </div>
                    </div>
                    <div class="si-stats mt-3">
                        <div><b>{{ $fmt($row->impressions) }}</b><span>Impressions</span></div>
                        <div><b>{{ $fmt($row->clicks) }}</b><span>Clicks</span></div>
                        <div><b>{{ number_format($row->ctr, 1) }}%</b><span>CTR</span></div>
                        <div><b style="color:{{ $st[1] }}">{{ number_format($row->position, 1) }}</b><span>Position</span></div>
                    </div>
                    <div x-show="open" x-cloak class="mt-3 grid lg:grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs font-bold text-gray-500 uppercase mb-1.5">What people searched</p>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($row->queries as $q)
                                    <span class="si-chip" title="{{ $fmt($q->i) }} impressions · {{ $q->c }} clicks">{{ $q->q }} · <b>#{{ number_format($q->p, 0) }}</b></span>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-gray-500 uppercase mb-1.5">Do next</p>
                            <div class="space-y-1.5">
                                @forelse($row->tips as $t)
                                    <div class="si-tip"><span>{{ $t[0] }}</span><span>{{ $t[1] }}</span></div>
                                @empty
                                    <div class="si-tip"><span>✅</span><span>Looks strong — keep collecting reviews and keep it in stock.</span></div>
                                @endforelse
                            </div>
                        </div>
                        <div class="lg:col-span-2 flex flex-wrap gap-1.5">
                            <a href="{{ route('admin.products.edit', $p->id) }}" class="si-btn si-btn-main">✏️ Edit description &amp; SEO</a>
                            <a href="{{ route('products.show', $p->slug) }}" target="_blank" rel="noopener" class="si-btn">👁 View page</a>
                            @if($topQ)<a href="https://www.google.com/search?q={{ urlencode($topQ->q) }}&gl=bd&hl=en" target="_blank" rel="noopener noreferrer" class="si-btn">🔍 See it on Google</a>@endif
                            <button type="button" class="si-btn" x-data @click="navigator.clipboard?.writeText(@js($row->queries->pluck('q')->implode(', '))); $el.textContent = '✓ Copied'">📋 Copy keywords</button>
                        </div>
                    </div>
                </div>
            @empty
                <p class="p-8 text-center text-gray-400">No product pages in this period yet.</p>
            @endforelse
        @endif

        @if($tab === 'keywords')
            @php $kw = $keywords; @endphp
            <div class="p-4 flex flex-wrap items-center gap-2">
                @foreach(['' => 'All', 'page1' => 'Page 1 (1–10)', 'near' => 'Almost page 1 (11–20)', 'lowctr' => 'Page 1, few clicks', 'new' => 'New this week', 'bangla' => 'বাংলা searches'] as $k => $l)
                    <a href="{{ $link(['f' => $k ?: null]) }}" class="si-fchip {{ (string) $kw->f === $k ? 'is-on' : '' }}">{{ $l }} <i>{{ $kw->counts[$k ?: 'all'] }}</i></a>
                @endforeach
                <form method="GET" action="{{ route('admin.seo-insights.index') }}" class="ml-auto flex gap-2" style="min-width:14rem">
                    <input type="hidden" name="tab" value="keywords"><input type="hidden" name="period" value="{{ $period }}">@if($kw->f)<input type="hidden" name="f" value="{{ $kw->f }}">@endif
                    <input type="search" name="q" value="{{ $kw->q }}" placeholder="Find a keyword…" class="si-in">
                </form>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full si-table">
                    <thead><tr><th>Keyword</th><th class="text-right">Impr.</th><th class="text-right">Clicks</th><th class="text-right si-hide-m">CTR</th><th class="text-right">Position</th><th class="si-hide-m">Shows page</th></tr></thead>
                    <tbody>
                    @forelse($kw->rows as $r)
                        <tr>
                            <td>
                                <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $r->q }}</span>
                                @if($r->isNew)<span class="si-badge" style="color:#7c3aed;background:#f5f3ff">NEW</span>@endif
                                @if($r->bangla)<span class="si-badge" style="color:#047857;background:#ecfdf5">বাংলা</span>@endif
                                <a href="https://www.google.com/search?q={{ urlencode($r->q) }}&gl=bd" target="_blank" rel="noopener noreferrer" class="text-xs text-gray-400 hover:text-orange-600 ml-1" title="See on Google">↗</a>
                            </td>
                            <td class="text-right">{{ $fmt($r->i) }}</td>
                            <td class="text-right">{{ $fmt($r->c) }}</td>
                            <td class="text-right si-hide-m">{{ number_format($r->ctr, 1) }}%</td>
                            <td class="text-right whitespace-nowrap">
                                <b style="color:{{ $r->p <= 3 ? '#059669' : ($r->p <= 10 ? '#2563eb' : ($r->p <= 20 ? '#d97706' : '#6b7280')) }}">{{ number_format($r->p, 1) }}</b>
                                @if($r->change !== null && abs($r->change) >= 0.5)
                                    <span class="text-xs {{ $r->change > 0 ? 'si-up' : 'si-down' }}">{{ $r->change > 0 ? '▲' : '▼' }}{{ number_format(abs($r->change), 0) }}</span>
                                @endif
                            </td>
                            <td class="si-hide-m">
                                @if($r->productId)
                                    <a href="{{ route('admin.products.edit', $r->productId) }}" class="text-orange-700 hover:underline line-clamp-1" title="Edit this product">{{ Str::limit($r->productName ?? 'Product', 48) }}</a>
                                @elseif($r->landPage)
                                    <span class="text-gray-500 text-xs">{{ ucfirst($r->landType) }} · {{ Str::limit(parse_url($r->landPage, PHP_URL_PATH) ?: '/', 40) }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-gray-400 py-8">No keywords match.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        @if($tab === 'pages')
            <div class="overflow-x-auto">
                <table class="w-full si-table">
                    <thead><tr><th>Page</th><th>Type</th><th class="text-right">Keywords</th><th class="text-right">Impr.</th><th class="text-right">Clicks</th><th class="text-right">Position</th></tr></thead>
                    <tbody>
                    @forelse($pages as $pg)
                        <tr>
                            <td><a href="{{ $pg->page }}" target="_blank" rel="noopener" class="text-orange-700 hover:underline">{{ Str::limit(urldecode(parse_url($pg->page, PHP_URL_PATH) ?: '/'), 60) }}</a></td>
                            <td><span class="si-chip">{{ ucfirst($pg->page_type ?? 'other') }}</span></td>
                            <td class="text-right">{{ $fmt($pg->qn) }}</td>
                            <td class="text-right">{{ $fmt($pg->i) }}</td>
                            <td class="text-right">{{ $fmt($pg->c) }}</td>
                            <td class="text-right"><b>{{ number_format($pg->p, 1) }}</b></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-gray-400 py-8">Nothing in this period.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endif
@endsection
