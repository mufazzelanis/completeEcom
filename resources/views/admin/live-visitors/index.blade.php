@extends('layouts.admin')
@section('title', 'Live & Traffic')

@section('content')
@php
    $s = $stats; $n = $s['now']; $p = $s['prev'];
    $fmt = fn ($v) => number_format((float) $v);
    $dur = fn ($secs) => $secs >= 60 ? floor($secs / 60) . 'm ' . ($secs % 60) . 's' : $secs . 's';
    $chg = function ($a, $b, $lowerIsBetter = false, $points = false) {
        if ($points) { $d = $a - $b; } elseif ($b > 0) { $d = ($a - $b) / $b * 100; } else { return null; }
        if (abs($d) < ($points ? 0.1 : 0.5)) return ['—', '#9ca3af'];
        $good = $lowerIsBetter ? $d < 0 : $d > 0;
        return [($d > 0 ? '▲ ' : '▼ ') . number_format(abs($d), $points ? 1 : 0) . ($points ? ' pts' : '%'), $good ? '#059669' : '#dc2626'];
    };
    $hourLabel = fn ($h) => $h === null ? '—' : \Illuminate\Support\Carbon::createFromTime($h)->format('g A') . ' – ' . \Illuminate\Support\Carbon::createFromTime(($h + 1) % 24)->format('g A');
    $q = fn (array $set) => route('admin.live-visitors.index', array_filter(array_merge(['range' => $s['key'], 'team' => $includeTeam ? 1 : null], $set), fn ($v) => $v !== null && $v !== ''));
    $funnel = [
        ['Visited', $n['sessions'], '#6366f1'],
        ['Viewed a product', $n['product'], '#0ea5e9'],
        ['Added to cart', $n['cart'], '#f59e0b'],
        ['Reached checkout', $n['checkout'], '#10b981'],
        ['Ordered', max($n['ordered'], 0), '#16a34a'],
    ];
    $srcColor = ['Google' => '#4285f4', 'Facebook' => '#1877f2', 'Direct / App' => '#64748b', 'Instagram' => '#e1306c', 'YouTube' => '#ff0000', 'WhatsApp' => '#25d366', 'Bing' => '#008373', 'Telegram' => '#229ed9', 'Twitter/X' => '#111827'];
@endphp
{{-- Page-local layout (kept here rather than in the Tailwind build, which only has classes other pages use) --}}
<style>
    .lv-big { font-size: 3.75rem; }
    .lv-stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .75rem; }
    .lv-bars { display: flex; align-items: flex-end; gap: 3px; height: 3.5rem; }
    .lv-bar { flex: 1 1 0%; border-radius: 3px 3px 0 0; transition: height .4s ease; }
    .lv-grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: 1.5rem; }
    .lv-main { min-width: 0; }
    .lv-who { display: none; width: 12rem; }
    .lv-xs { font-size: 11px; } .lv-xxs { font-size: 10px; }
    @media (min-width: 640px) { .lv-who { display: block; } .lv-who-m { display: none; } }
    @media (min-width: 1100px) { .lv-grid { grid-template-columns: minmax(0, 2fr) minmax(0, 1fr); } }
    @media (max-width: 639px) { .lv-big { font-size: 3rem; } .lv-stats { gap: .5rem; } }

    /* traffic analytics */
    .ta-card { background: #fff; border: 1px solid #eef2f7; border-radius: 1rem; }
    .ta-h { font-size: .78rem; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; color: #6b7280; }
    .ta-range { display: flex; flex-wrap: wrap; gap: .35rem; }
    .ta-range a { padding: .4rem .8rem; border-radius: 999px; font-size: .8rem; font-weight: 700; color: #4b5563; background: #fff; border: 1px solid #e5e7eb; white-space: nowrap; }
    .ta-range a.is-on { background: #111827; color: #fff; border-color: #111827; }
    .ta-kpis { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: .75rem; }
    .ta-kpi { padding: .85rem 1rem; }
    .ta-kpi span { display: block; font-size: .7rem; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: .04em; }
    .ta-kpi b { display: block; font-size: 1.45rem; font-weight: 800; color: #111827; line-height: 1.25; margin-top: .15rem; font-variant-numeric: tabular-nums; }
    .ta-kpi i { font-style: normal; font-size: .72rem; font-weight: 700; }
    .ta-kpi small { display: block; font-size: .7rem; color: #9ca3af; margin-top: .1rem; }
    .ta-2 { display: grid; grid-template-columns: minmax(0, 2fr) minmax(0, 1fr); gap: 1rem; }
    .ta-3 { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem; }
    .ta-peak { border-radius: 1rem; padding: 1rem 1.1rem; color: #fff; }
    .ta-peak p.t { font-size: 1.35rem; font-weight: 900; margin-top: .3rem; }
    .ta-fun { display: grid; grid-template-columns: 9.5rem minmax(0, 1fr) 4rem; align-items: center; gap: .6rem; font-size: .82rem; }
    .ta-fun + .ta-fun { margin-top: .55rem; }
    .ta-fun .bar { height: 1.6rem; border-radius: .45rem; background: #f1f5f9; overflow: hidden; }
    .ta-fun .bar span { display: flex; align-items: center; height: 100%; padding-left: .5rem; color: #fff; font-weight: 800; font-size: .75rem; border-radius: .45rem; min-width: 2.2rem; }
    .ta-drop { font-size: .7rem; color: #dc2626; font-weight: 700; padding-left: 10.1rem; margin-top: .1rem; }
    .ta-row { display: flex; align-items: center; gap: .6rem; font-size: .84rem; padding: .45rem 0; }
    .ta-row + .ta-row { border-top: 1px solid #f1f5f9; }
    .ta-meter { height: .4rem; border-radius: 999px; background: #f1f5f9; overflow: hidden; margin-top: .25rem; }
    .ta-meter span { display: block; height: 100%; border-radius: 999px; }
    .ta-thumb { width: 2.4rem; height: 2.4rem; border-radius: .6rem; background: #f8fafc; border: 1px solid #eef2f7; overflow: hidden; flex-shrink: 0; display: flex; align-items: center; justify-content: center; }
    .ta-thumb img { max-width: 100%; max-height: 100%; object-fit: contain; }
    .ta-pill { font-size: .68rem; font-weight: 800; padding: .1rem .45rem; border-radius: 999px; white-space: nowrap; }
    .ta-in { border: 1px solid #e5e7eb; border-radius: .6rem; padding: .35rem .55rem; font-size: .8rem; background: #fff; }
    .dark .ta-card, .dark .ta-range a, .dark .ta-in { background: #111827; border-color: #1f2937; color: #e5e7eb; }
    .dark .ta-range a.is-on { background: #f97316; border-color: #f97316; color: #fff; }
    .dark .ta-kpi b { color: #f9fafb; }
    .dark .ta-fun .bar, .dark .ta-meter { background: #1f2937; }
    .dark .ta-row + .ta-row { border-color: #1f2937; }
    @media (max-width: 1279px) { .ta-kpis { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    @media (max-width: 1023px) { .ta-2, .ta-3 { grid-template-columns: minmax(0, 1fr); } }
    @media (max-width: 639px) {
        .ta-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .5rem; }
        .ta-kpi b { font-size: 1.2rem; }
        .ta-fun { grid-template-columns: 6.5rem minmax(0, 1fr) 3rem; font-size: .75rem; }
        .ta-drop { padding-left: 7.1rem; }
        .ta-dates { width: 100%; margin-left: 0; display: grid !important; grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr) auto; }
        .ta-dates input[type=date] { width: 100%; min-width: 0; }
    }
</style>
{{-- The live feed at the bottom is re-fetched every 5 s (LiveVisitorController::feed) and swapped
     in place — no reload. Polling pauses while this tab is hidden and stops when Turbo navigates away.
     Everything above it is the chosen date range, computed on page load (cached for a minute). --}}
<div x-data="{
        team: {{ $includeTeam ? 'true' : 'false' }},
        paused: false,
        updated: 0,
        failed: false,
        timer: null,
        liveCount: {{ $live->count() }},
        url() { return '{{ route('admin.live-visitors.feed') }}' + (this.team ? '?team=1' : ''); },
        async refresh() {
            if (this.paused || document.hidden) return;
            try {
                const r = await fetch(this.url(), { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
                if (!r.ok) throw new Error(r.status);
                const data = await r.json();
                this.$refs.feed.innerHTML = data.html;
                this.liveCount = data.count;
                this.updated = 0; this.failed = false;
                document.title = '(' + data.count + ') Live & Traffic';
            } catch (e) { this.failed = true; }
        },
        toggleTeam() {
            // the stats above depend on it too, so reload the page with the new setting
            const u = new URL(location.href);
            this.team ? u.searchParams.delete('team') : u.searchParams.set('team', '1');
            Turbo.visit(u.toString());
        },
        init() {
            this.timer = setInterval(() => { this.updated++; if (this.updated % 5 === 0) this.refresh(); }, 1000);
            const stop = () => { clearInterval(this.timer); document.removeEventListener('turbo:before-visit', stop); };
            document.addEventListener('turbo:before-visit', stop);
        },
        destroy() { clearInterval(this.timer); }
     }">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <div>
            <h1 class="text-xl font-bold text-gray-800 dark:text-gray-100">Live &amp; Traffic</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Who's on the store right now — and how visits turn into orders</p>
        </div>
        <div class="flex flex-wrap items-center gap-2 text-sm">
            <span class="text-xs tabular-nums" :style="failed ? 'color:#ef4444' : 'color:#94a3b8'"
                  x-text="failed ? 'Connection problem — retrying…' : (paused ? 'Paused' : 'Updated ' + updated + 's ago')"></span>
            <button type="button" @click="paused = !paused; if (!paused) refresh()"
                    class="rounded-full px-3 py-1.5 font-medium transition"
                    :style="paused ? 'background:#f59e0b;color:#fff' : 'background:#f59e0b1a;color:#d97706'">
                <span x-text="paused ? '▶ Resume' : '⏸ Pause'"></span>
            </button>
            <button type="button" @click="toggleTeam()"
                    class="rounded-full px-3 py-1.5 font-medium transition"
                    :style="team ? 'background:#6366f1;color:#fff' : 'background:#6366f11a;color:#6366f1'">
                <span x-text="team ? '✓ Including team' : 'Include team'"></span>
            </button>
        </div>
    </div>

    {{-- ───────── right now (first — it's the part that changes by the second) ───────── --}}
    <div x-ref="feed" class="mb-8">
        @include('admin.live-visitors._feed')
    </div>

    {{-- ───────── traffic & sales for a date range ───────── --}}
    <div class="flex flex-wrap items-baseline gap-2 mb-3 pt-6 border-t border-gray-200 dark:border-gray-800">
        <h2 class="text-lg font-bold text-gray-800 dark:text-gray-100">📊 Traffic &amp; sales</h2>
        <span class="text-sm text-gray-400">{{ $s['label'] }}</span>
    </div>

    {{-- ───────── range ───────── --}}
    <div class="flex flex-wrap items-center gap-2 mb-4">
        <div class="ta-range">
            @foreach(['today' => 'Today', 'yesterday' => 'Yesterday', '7d' => '7 days', '30d' => '30 days', 'month' => 'This month'] as $k => $l)
                <a href="{{ $q(['range' => $k, 'from' => null, 'to' => null]) }}" class="{{ $s['key'] === $k ? 'is-on' : '' }}">{{ $l }}</a>
            @endforeach
        </div>
        <form method="GET" action="{{ route('admin.live-visitors.index') }}" class="ta-dates flex items-center gap-1.5 ml-auto">
            <input type="hidden" name="range" value="custom">@if($includeTeam)<input type="hidden" name="team" value="1">@endif
            <input type="date" name="from" class="ta-in" value="{{ $s['key'] === 'custom' ? $s['from']->toDateString() : '' }}" aria-label="From">
            <span class="text-gray-400 text-xs">→</span>
            <input type="date" name="to" class="ta-in" value="{{ $s['key'] === 'custom' ? $s['to']->copy()->subDay()->toDateString() : '' }}" aria-label="To">
            <button class="ta-in font-bold {{ $s['key'] === 'custom' ? 'text-white' : '' }}" style="{{ $s['key'] === 'custom' ? 'background:#111827;border-color:#111827' : '' }}">Apply</button>
        </form>
    </div>
    @if($s['since'] && \Illuminate\Support\Carbon::parse($s['since'])->gt($s['from']->copy()->utc()))
        <p class="text-xs mb-3 rounded-lg px-3 py-2" style="background:#fffbeb;color:#92400e">Visit history is recorded from {{ \Illuminate\Support\Carbon::parse($s['since'])->timezone(setting('timezone', 'Asia/Dhaka'))->format('d M Y, g:i A') }} — earlier days show orders only.</p>
    @elseif(! $s['since'])
        <p class="text-xs mb-3 rounded-lg px-3 py-2" style="background:#fffbeb;color:#92400e">Visit history starts recording now — the funnel, sources and peak hours fill in as customers browse.</p>
    @endif

    {{-- ───────── KPIs ───────── --}}
    @php
        $kpis = [
            ['Visitors', $fmt($n['visitors']), $chg($n['visitors'], $p['visitors']), $fmt($n['sessions']) . ' visits'],
            ['Orders', $fmt($n['orders']), $chg($n['orders'], $p['orders']), 'not counting cancelled'],
            ['Sales', '৳' . $fmt($n['revenue']), $chg($n['revenue'], $p['revenue']), $n['orders'] ? 'avg ৳' . $fmt($n['revenue'] / $n['orders']) . ' / order' : '—'],
            ['Conversion', number_format($n['conversion'], 1) . '%', $chg($n['conversion'], $p['conversion'], false, true), 'orders ÷ visits'],
            ['Time on site', $dur($n['avg_secs']), $chg($n['avg_secs'], $p['avg_secs']), 'average per visit'],
            ['Bounce', number_format($n['bounce'], 0) . '%', $chg($n['bounce'], $p['bounce'], true, true), 'left within 30 s, 1 page'],
        ];
    @endphp
    <div class="ta-kpis mb-4">
        @foreach($kpis as $k)
            <div class="ta-card ta-kpi">
                <span>{{ $k[0] }}</span><b>{{ $k[1] }}</b>
                <i style="color:{{ $k[2][1] ?? '#9ca3af' }}">{{ $k[2][0] ?? '—' }}</i> <i style="color:#9ca3af;font-weight:500">vs before</i>
                <small>{{ $k[3] }}</small>
            </div>
        @endforeach
    </div>

    {{-- ───────── chart + peak hours ───────── --}}
    @php
        $byDay = $s['days'] > 2;
        $series = $byDay ? $s['daily'] : collect($s['hourly'])->map(fn ($h, $i) => ['d' => $i, 's' => $h['s'], 'o' => $h['o']])->values()->all();
        $maxS = max(1, max(array_column($series, 's') ?: [0])); $maxO = max(1, max(array_column($series, 'o') ?: [0]));
        $cnt = max(1, count($series)); $w = 720 / $cnt;
    @endphp
    <div class="ta-2 mb-4">
        <div class="ta-card p-4 min-w-0">
            <div class="flex items-center justify-between mb-2">
                <span class="ta-h">{{ $byDay ? 'Visits & orders per day' : 'Visits & orders by hour' }}</span>
                <span class="text-xs text-gray-400"><span style="color:#6366f1">■</span> visits &nbsp; <span style="color:#f59e0b">■</span> orders</span>
            </div>
            <svg viewBox="0 0 720 170" preserveAspectRatio="none" style="width:100%;height:170px" role="img" aria-label="Visits and orders chart">
                @foreach($series as $i => $pt)
                    @php $hs = $pt['s'] / $maxS * 135; $ho = $pt['o'] / $maxO * 60; @endphp
                    <rect x="{{ round($i * $w + $w * .12, 1) }}" y="{{ round(150 - $hs, 1) }}" width="{{ round($w * .5, 1) }}" height="{{ round(max($hs, $pt['s'] ? 2 : 0), 1) }}" rx="3" fill="#6366f1" opacity=".85"><title>{{ $byDay ? $pt['d'] : $hourLabel($pt['d']) }} — {{ $pt['s'] }} visits, {{ $pt['o'] }} orders</title></rect>
                    @if($pt['o'])<rect x="{{ round($i * $w + $w * .64, 1) }}" y="{{ round(150 - $ho, 1) }}" width="{{ round($w * .24, 1) }}" height="{{ round(max($ho, 2), 1) }}" rx="2" fill="#f59e0b"><title>{{ $pt['o'] }} orders</title></rect>@endif
                    @if($byDay ? ($cnt <= 10 || $i % (int) ceil($cnt / 10) === 0) : $i % 3 === 0)
                        <text x="{{ round($i * $w + $w * .37, 1) }}" y="166" font-size="10" text-anchor="middle" fill="#9ca3af">{{ $byDay ? $pt['d'] : \Illuminate\Support\Carbon::createFromTime($pt['d'])->format('ga') }}</text>
                    @endif
                @endforeach
                <line x1="0" y1="150.5" x2="720" y2="150.5" stroke="#e5e7eb"/>
            </svg>
        </div>
        <div class="grid gap-3" style="grid-template-rows: 1fr 1fr">
            <div class="ta-peak" style="background:linear-gradient(135deg,#f97316,#ea580c)">
                <p class="text-xs font-bold uppercase tracking-wider opacity-90">🔥 Busiest hour</p>
                <p class="t">{{ $hourLabel($s['peak']) }}</p>
                <p class="text-sm opacity-90 mt-1">@if($s['peak'] !== null){{ $s['hourly'][$s['peak']]['s'] }} visits · {{ $s['hourly'][$s['peak']]['o'] }} orders — best time for posts &amp; offers @else Not enough visits yet @endif</p>
            </div>
            <div class="ta-peak" style="background:linear-gradient(135deg,#334155,#0f172a)">
                <p class="text-xs font-bold uppercase tracking-wider opacity-80">🌙 Quietest hour</p>
                <p class="t">{{ $hourLabel($s['quiet']) }}</p>
                <p class="text-sm opacity-80 mt-1">@if($s['quiet'] !== null){{ $s['hourly'][$s['quiet']]['s'] }} visits — cheapest time to run ads @else — @endif</p>
            </div>
        </div>
    </div>

    {{-- ───────── funnel · sources · devices ───────── --}}
    <div class="ta-3 mb-4">
        <div class="ta-card p-4 min-w-0">
            <p class="ta-h mb-3">🔻 Path to an order</p>
            @foreach($funnel as $i => $f)
                @php $pct = $n['sessions'] ? $f[1] / $n['sessions'] * 100 : 0; @endphp
                <div class="ta-fun">
                    <span class="text-gray-600 dark:text-gray-300 font-semibold">{{ $f[0] }}</span>
                    <div class="bar"><span style="width:{{ max(4, $pct) }}%;background:{{ $f[2] }}">{{ $fmt($f[1]) }}</span></div>
                    <span class="text-right font-bold text-gray-800 dark:text-gray-100">{{ number_format($pct, $pct < 10 && $pct > 0 ? 1 : 0) }}%</span>
                </div>
                @if($i > 0 && $funnel[$i - 1][1] > 0 && $f[1] < $funnel[$i - 1][1])
                    @php $lost = ($funnel[$i - 1][1] - $f[1]) / $funnel[$i - 1][1] * 100; @endphp
                    @if($lost >= 50)<p class="ta-drop">↓ {{ number_format($lost, 0) }}% stop here</p>@endif
                @endif
            @endforeach
            <p class="text-xs text-gray-400 mt-3">The biggest red drop is where most buyers are lost — fix that step first.</p>
        </div>

        <div class="ta-card p-4 min-w-0">
            <p class="ta-h mb-2">🌐 Where visitors come from</p>
            @forelse($s['sources'] as $src)
                @php $share = $n['sessions'] ? $src->c / $n['sessions'] * 100 : 0; $cv = $src->c ? $src->o / $src->c * 100 : 0; $col = $srcColor[$src->src] ?? '#8b5cf6'; @endphp
                <div class="ta-row">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-semibold text-gray-800 dark:text-gray-100 truncate">{{ $src->src }}</span>
                            <span class="text-gray-500 text-xs whitespace-nowrap">{{ $fmt($src->c) }} · {{ number_format($share, 0) }}%</span>
                        </div>
                        <div class="ta-meter"><span style="width:{{ $share }}%;background:{{ $col }}"></span></div>
                    </div>
                    <span class="ta-pill" style="{{ $src->o ? 'background:#ecfdf5;color:#047857' : 'background:#f3f4f6;color:#6b7280' }}" title="{{ $src->o }} of {{ $src->c }} visits ordered">{{ number_format($cv, 1) }}% buy</span>
                </div>
            @empty
                <p class="text-sm text-gray-400 py-4 text-center">No visits in this range yet.</p>
            @endforelse
        </div>

        <div class="ta-card p-4 min-w-0">
            <p class="ta-h mb-2">📱 Devices</p>
            @forelse($s['devices'] as $dv)
                @php $share = $n['sessions'] ? $dv->c / $n['sessions'] * 100 : 0; @endphp
                <div class="ta-row">
                    <span class="text-lg">{{ ['Mobile' => '📱', 'Tablet' => '📲', 'Desktop' => '💻'][$dv->dev] ?? '🖥️' }}</span>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-2"><span class="font-semibold text-gray-800 dark:text-gray-100">{{ $dv->dev }}</span><span class="text-xs text-gray-500">{{ number_format($share, 0) }}% · {{ $dv->o }} orders</span></div>
                        <div class="ta-meter"><span style="width:{{ $share }}%;background:#0ea5e9"></span></div>
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-400 py-4 text-center">—</p>
            @endforelse
            @if($s['landings']->isNotEmpty())
                <p class="ta-h mt-4 mb-1">🚪 Top entry pages</p>
                @foreach($s['landings'] as $lp)
                    <div class="ta-row">
                        <a href="{{ $lp->u }}" target="_blank" rel="noopener" class="min-w-0 flex-1 truncate text-gray-700 dark:text-gray-200 hover:text-orange-600" title="{{ $lp->u }}">{{ \Illuminate\Support\Str::limit(urldecode(parse_url($lp->u, PHP_URL_PATH) ?: '/'), 34) }}</a>
                        <span class="text-xs text-gray-500 whitespace-nowrap">{{ $fmt($lp->c) }}@if($lp->o) · <b style="color:#047857">{{ $lp->o }} 🛒</b>@endif</span>
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    {{-- ───────── products · abandoned checkouts ───────── --}}
    <div class="ta-3 mb-6">
        <div class="ta-card p-4 min-w-0">
            <p class="ta-h mb-2">👀 Most viewed products</p>
            @forelse($s['topViewed'] as $r)
                <div class="ta-row">
                    <div class="ta-thumb">@if($r->p->image)<img src="{{ image_thumb($r->p->image, 96) }}" alt="" loading="lazy">@endif</div>
                    <a href="{{ route('admin.products.edit', $r->p->id) }}" class="min-w-0 flex-1 truncate font-medium text-gray-800 dark:text-gray-100 hover:text-orange-600">{{ $r->p->name }}</a>
                    <span class="text-xs text-gray-500 whitespace-nowrap text-right">{{ $r->viewers }} 👀<br>{{ $r->sold }} sold</span>
                </div>
            @empty
                <p class="text-sm text-gray-400 py-4 text-center">No product views in this range.</p>
            @endforelse
        </div>

        <div class="ta-card p-4 min-w-0">
            <p class="ta-h mb-1">⚠️ Viewed, but nobody bought</p>
            <p class="text-xs text-gray-400 mb-2">Check the price, photos and description of these.</p>
            @forelse($s['notBuying'] as $r)
                <div class="ta-row">
                    <div class="ta-thumb">@if($r->p->image)<img src="{{ image_thumb($r->p->image, 96) }}" alt="" loading="lazy">@endif</div>
                    <div class="min-w-0 flex-1">
                        <a href="{{ route('admin.products.edit', $r->p->id) }}" class="block truncate font-medium text-gray-800 dark:text-gray-100 hover:text-orange-600">{{ $r->p->name }}</a>
                        <span class="text-xs text-gray-500">৳{{ $fmt($r->p->sale_price ?? $r->p->price) }}@if($r->p->stock <= 0) · <b style="color:#dc2626">out of stock</b>@endif</span>
                    </div>
                    <span class="text-xs whitespace-nowrap text-right"><b>{{ $r->viewers }}</b> 👀<br><span style="color:{{ $r->adds ? '#d97706' : '#9ca3af' }}">{{ $r->adds }} to cart</span></span>
                </div>
            @empty
                <p class="text-sm text-gray-400 py-4 text-center">🎉 Every well-viewed product sold at least once.</p>
            @endforelse
        </div>

        <div class="ta-card p-4 min-w-0">
            <div class="flex items-center justify-between mb-1">
                <p class="ta-h">🛒 Left at checkout</p>
                <a href="{{ route('admin.checkout-leads.index') }}" class="text-xs font-bold text-orange-700 hover:underline">All ({{ $s['leadCount'] }}) →</a>
            </div>
            <p class="text-xs text-gray-400 mb-2">They typed their number but didn't order — a call often saves the sale.</p>
            @forelse($s['leads'] as $lead)
                @php $digits = preg_replace('/\D/', '', (string) $lead->phone); $wa = str_starts_with($digits, '01') ? '88' . $digits : $digits; @endphp
                <div class="ta-row">
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-gray-800 dark:text-gray-100 truncate">{{ $lead->name ?: 'No name' }} · <span class="font-normal text-gray-500">{{ $lead->phone }}</span></p>
                        <p class="text-xs text-gray-500">৳{{ $fmt($lead->subtotal) }} · {{ $lead->ago }}</p>
                    </div>
                    @if($digits)
                        <a href="tel:{{ $digits }}" class="ta-pill" style="background:#eff6ff;color:#2563eb">📞</a>
                        <a href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener" class="ta-pill" style="background:#f0fdf4;color:#16a34a">💬</a>
                    @endif
                </div>
            @empty
                <p class="text-sm text-gray-400 py-4 text-center">Nobody left at checkout in this range.</p>
            @endforelse
        </div>
    </div>

    <p class="text-xs text-gray-400 mt-6">
        "Live" = the site is open in a visible tab within the last minute. A visit ends after 30 minutes without activity.
        Visitors are counted per browser (not per IP), bots and your team are left out. Location is looked up from the IP and may be approximate.
    </p>
</div>
@endsection
