@extends('layouts.admin')
@section('title', 'Analytics · ' . $landingPage->title)

@php
    $n = max(1, (int) $kpi['views']);
    $funnel = [
        ['Viewed the page', (int) $kpi['views']],
        ['Scrolled halfway or more', (int) $depth->d50],
        ['Reached the order form', (int) $kpi['reached_form']],
        ['Started filling it in', (int) $kpi['started_form']],
        ['Placed an order', (int) $kpi['orders']],
    ];
    $timeBuckets = ['Under 10s' => $time->t0, '10–30s' => $time->t1, '30s–1m' => $time->t2, '1–3 min' => $time->t3, '3–5 min' => $time->t4, '5 min +' => $time->t5];
    $timeMax = max(1, ...array_map('intval', array_values($timeBuckets)));
    $placementLabels = \App\Models\LandingPageCtaClick::PLACEMENTS;
    $sourceLabels = \App\Models\BlogRead::SOURCES;
@endphp

@section('content')
<div class="space-y-5">
    <a href="{{ route('admin.landing-pages.report') }}" class="text-sm text-gray-500 hover:text-gray-700 inline-flex items-center gap-1">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Landing Report
    </a>

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
            <h1 class="text-lg sm:text-xl font-bold text-gray-900 line-clamp-2">{{ $landingPage->title }}</h1>
            <p class="text-sm text-gray-500 mt-0.5 font-mono">/{{ $landingPage->slug }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.landing-pages.edit', $landingPage) }}" class="bg-white rounded-xl shadow-sm px-3 py-2 text-sm font-medium text-gray-700 hover:text-indigo-600">Edit page</a>
            <a href="{{ route('landing.show', $landingPage) }}" target="_blank" class="bg-indigo-600 rounded-xl shadow-sm px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700">View page →</a>
        </div>
    </div>

    {{-- period picker --}}
    <div class="flex flex-wrap gap-1.5 bg-white rounded-xl shadow-sm p-1.5 w-fit">
        @foreach(\App\Http\Controllers\Admin\LandingPageAnalyticsController::PERIODS as $key => $label)
            <a href="{{ route('admin.landing-pages.analytics', ['landingPage' => $landingPage, 'period' => $key]) }}"
               class="px-3 py-1.5 rounded-lg text-sm font-medium {{ $r['period'] === $key ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-50' }}">{{ $label }}</a>
        @endforeach
    </div>

    {{-- KPIs --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl shadow-sm p-5">
            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Views</p>
            <p class="text-2xl font-semibold text-gray-800">{{ number_format($kpi['views']) }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm p-5">
            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Engaged visitors</p>
            <p class="text-2xl font-semibold text-gray-800">{{ number_format($kpi['readers']) }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm p-5">
            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Orders</p>
            <p class="text-2xl font-semibold text-gray-800">{{ number_format($kpi['orders']) }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm p-5">
            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Conversion rate</p>
            <p class="text-2xl font-semibold text-gray-800">{{ round($kpi['conv_rate'], 1) }}%</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        {{-- the funnel --}}
        <div class="bg-white rounded-2xl shadow-sm p-5">
            <h3 class="font-semibold text-gray-800">Where visitors drop off</h3>
            <p class="text-xs text-gray-400 mb-4">Each step is what share of all viewers in this period got at least this far.</p>
            @foreach($funnel as $i => [$label, $count])
                @php $pct = $kpi['views'] ? round($count / $n * 100) : 0; @endphp
                <div class="flex items-center gap-3 mb-2.5 last:mb-0">
                    <span class="w-40 text-xs text-gray-600 flex-shrink-0">{{ $label }}</span>
                    <div class="flex-1 h-6 rounded-lg bg-gray-50 overflow-hidden">
                        <div class="h-full rounded-lg" style="width: {{ $kpi['views'] ? max(1.5, $pct) : 0 }}%; background:#2a78d6"></div>
                    </div>
                    <span class="w-24 text-right text-xs tabular-nums"><b class="text-gray-900">{{ $pct }}%</b> <span class="text-gray-400">{{ number_format($count) }}</span></span>
                </div>
                @if($i > 0 && $funnel[$i - 1][1] > 0)
                    @php $drop = round((1 - $count / $funnel[$i - 1][1]) * 100); @endphp
                    @if($drop > 0)
                        <p class="text-[11px] text-red-500 ml-[11.5rem] -mt-1 mb-2">↓ {{ $drop }}% dropped off here</p>
                    @endif
                @endif
            @endforeach
            @if($kpi['views'] && $kpi['reached_form'] / $n < .2)
                <p class="mt-4 text-xs text-amber-700 bg-amber-50 rounded-lg px-3 py-2">Most visitors never reach the order form — try a shorter page, or move an "Order Now" button higher up.</p>
            @elseif($kpi['started_form'] && $kpi['orders'] / max(1, $kpi['started_form']) < .5)
                <p class="mt-4 text-xs text-amber-700 bg-amber-50 rounded-lg px-3 py-2">People start filling the form but don't finish — check the form isn't asking too much, or that the delivery charge/total isn't a late surprise.</p>
            @endif
        </div>

        {{-- time on page --}}
        <div class="bg-white rounded-2xl shadow-sm p-5">
            <h3 class="font-semibold text-gray-800">Time spent on the page</h3>
            <p class="text-xs text-gray-400 mb-4">Active time: tab in front and the visitor scrolling, tapping or moving within the last 30 seconds.</p>
            <div class="flex items-end gap-2 h-40">
                @foreach($timeBuckets as $label => $count)
                    <div class="flex-1 h-full flex flex-col justify-end items-center gap-1" title="{{ $label }}: {{ (int) $count }}">
                        <span class="text-[11px] font-semibold text-gray-700 tabular-nums">{{ (int) $count }}</span>
                        <div class="w-full rounded-t" style="height: {{ max(2, (int) $count / $timeMax * 100) }}%; background:#2a78d6"></div>
                        <span class="text-[10px] text-gray-500 whitespace-nowrap">{{ $label }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        {{-- CTA clicks --}}
        <div class="bg-white rounded-2xl shadow-sm p-5">
            <h3 class="font-semibold text-gray-800">Which "Order Now" button</h3>
            <p class="text-xs text-gray-400 mb-4">Taps that scrolled to the order form, by button.</p>
            @forelse($placementLabels as $key => $label)
                @php $c = (int) ($ctaClicks[$key] ?? 0); $max = max(1, $ctaClicks->max() ?: 1); @endphp
                <div class="flex items-center gap-3 mb-2.5 last:mb-0">
                    <span class="w-28 text-xs text-gray-600 flex-shrink-0">{{ $label }}</span>
                    <div class="flex-1 h-5 rounded-lg bg-gray-50 overflow-hidden">
                        <div class="h-full rounded-lg" style="width: {{ $c ? max(2, $c / $max * 100) : 0 }}%; background:#f97316"></div>
                    </div>
                    <span class="w-10 text-right text-xs tabular-nums font-semibold text-gray-900">{{ $c }}</span>
                </div>
            @empty
                <p class="text-sm text-gray-400">No clicks recorded yet.</p>
            @endforelse
        </div>

        {{-- device --}}
        <div class="bg-white rounded-2xl shadow-sm p-5">
            <h3 class="font-semibold text-gray-800">Device</h3>
            @forelse($devices as $device => $count)
                <div class="flex items-center gap-3 mb-2.5 last:mb-0">
                    <span class="w-20 text-xs text-gray-600 capitalize flex-shrink-0">{{ $device }}</span>
                    <div class="flex-1 h-5 rounded-lg bg-gray-50 overflow-hidden">
                        <div class="h-full rounded-lg" style="width: {{ max(2, $count / $n * 100) }}%; background:#10b981"></div>
                    </div>
                    <span class="w-10 text-right text-xs tabular-nums font-semibold text-gray-900">{{ $count }}</span>
                </div>
            @empty
                <p class="text-sm text-gray-400">No visits yet.</p>
            @endforelse
        </div>

        {{-- source --}}
        <div class="bg-white rounded-2xl shadow-sm p-5">
            <h3 class="font-semibold text-gray-800">Where visitors came from</h3>
            @forelse($sources as $source => $count)
                <div class="flex items-center gap-3 mb-2.5 last:mb-0">
                    <span class="w-20 text-xs text-gray-600 flex-shrink-0">{{ $sourceLabels[$source] ?? ucfirst($source) }}</span>
                    <div class="flex-1 h-5 rounded-lg bg-gray-50 overflow-hidden">
                        <div class="h-full rounded-lg" style="width: {{ max(2, $count / $n * 100) }}%; background:#6366f1"></div>
                    </div>
                    <span class="w-10 text-right text-xs tabular-nums font-semibold text-gray-900">{{ $count }}</span>
                </div>
            @empty
                <p class="text-sm text-gray-400">No visits yet.</p>
            @endforelse
        </div>
    </div>

    {{-- Pixel / CAPI health --}}
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <div class="flex items-center justify-between mb-1">
            <h3 class="font-semibold text-gray-800">Facebook Pixel + Conversions API</h3>
            <a href="{{ route('admin.facebook-conversion-logs.index') }}" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">Full event log →</a>
        </div>
        @if(!$capiConfigured)
            <p class="text-sm text-amber-700 bg-amber-50 rounded-lg px-3 py-2 mt-3">No Facebook Pixel ID is set for this page or the site (Admin → Settings → Facebook Pixel, or this page's own Pixel Tracking tab). Nothing is being sent to Meta yet.</p>
        @elseif($capi->isEmpty())
            <p class="text-sm text-gray-500 mt-3">Conversions API hasn't sent anything for this page in this period yet — it may not be enabled (Admin → Settings → Facebook Pixel → "Enable Conversions API"), or there simply haven't been any events.</p>
        @else
            <p class="text-xs text-gray-400 mb-3">Server-side backup of the browser pixel — recovers visitors an ad-blocker or iOS privacy setting would otherwise hide from Meta entirely.</p>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                @foreach(['ViewContent', 'InitiateCheckout', 'AddToCart', 'Purchase'] as $event)
                    @php $row = $capi->get($event, ['sent' => 0, 'failed' => 0]); $total = $row['sent'] + $row['failed']; @endphp
                    <div class="rounded-xl border border-gray-100 p-3">
                        <p class="text-xs text-gray-500">{{ $event }}</p>
                        <p class="text-lg font-semibold {{ $row['failed'] > 0 ? 'text-red-600' : 'text-gray-900' }}">{{ $row['sent'] }}<span class="text-xs text-gray-400">/{{ $total }}</span></p>
                        @if($row['failed'] > 0)
                            <p class="text-[11px] text-red-500">{{ $row['failed'] }} failed</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
