{{-- Who came, really: people vs named bots vs cloud servers vs no-interaction views.
     $traffic: BlogAnalyticsController::traffic(); $logParams: query for the visitor-log links --}}
@php
    $t = $traffic['types'];
    $all = max(1, $traffic['total']);
    $logParams = $logParams ?? [];
    $tiles = [
        ['human', '👤', 'Real people', 'touched, scrolled or moved the mouse', '#047857', '#ecfdf5'],
        ['bot', '🤖', 'Bots', 'said so, or a script posing as a browser', '#c2410c', '#fff7ed'],
        ['cloud', '☁️', 'Cloud servers', 'Amazon, Google Cloud … — not homes or phones', '#1d4ed8', '#eff6ff'],
        ['unknown', '❔', 'No interaction', 'opened and left at once, or a quiet bot', '#4b5563', '#f3f4f6'],
    ];
@endphp
<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 border-b border-gray-100">
        <div>
            <h3 class="font-semibold text-gray-800">Who visited — people, bots and servers</h3>
            <p class="text-xs text-gray-400 mt-0.5">{{ number_format($traffic['total']) }} page views in this period. Only real people count in the numbers above.</p>
        </div>
        <a href="{{ route('admin.blog.analytics.visits', $logParams) }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">Open visitor log →</a>
    </div>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-px bg-gray-100">
        @foreach($tiles as [$key, $icon, $label, $sub, $ink, $bg])
            <a href="{{ route('admin.blog.analytics.visits', $logParams + ['type' => $key]) }}" class="bg-white p-4 hover:bg-gray-50 transition">
                <div class="flex items-center gap-2">
                    <span class="w-7 h-7 rounded-lg flex items-center justify-center text-sm" style="background: {{ $bg }}">{{ $icon }}</span>
                    <span class="text-xs font-semibold" style="color: {{ $ink }}">{{ $label }}</span>
                </div>
                <p class="mt-2 text-2xl font-extrabold text-gray-900 tabular-nums">{{ number_format($t[$key]) }} <span class="text-xs font-semibold text-gray-400">{{ round($t[$key] / $all * 100) }}%</span></p>
                <p class="text-[11px] text-gray-400 leading-snug">{{ $sub }}</p>
            </a>
        @endforeach
    </div>
    <div class="h-2 flex">
        @foreach($tiles as [$key, , , , $ink])
            @if($t[$key])<div style="width: {{ $t[$key] / $all * 100 }}%; background: {{ $ink }}" title="{{ $key }}"></div>@endif
        @endforeach
    </div>

    @if($traffic['names']->isNotEmpty())
        <div class="px-5 py-4">
            <p class="text-xs font-semibold text-gray-500 mb-2">Bots and servers by name</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6">
                @foreach($traffic['names']->take(16) as $b)
                    <div class="flex items-center justify-between gap-3 py-1.5 border-b border-gray-50">
                        <div class="min-w-0 flex items-center gap-2">
                            <span class="text-sm" aria-hidden="true">{{ $b['icon'] }}</span>
                            <div class="min-w-0">
                                <p class="text-xs font-semibold truncate {{ $b['kind'] === 'fake' ? 'text-red-600' : 'text-gray-800' }}">
                                    {{ $b['name'] }}@if($b['verified'] === true)<span class="ml-1 text-emerald-600" title="Its IPs really belong to this company">✓ verified</span>@endif
                                </p>
                                <p class="text-[10px] text-gray-400 truncate">{{ \App\Support\VisitorIntel::KINDS[$b['kind']] ?? 'No interaction' }}@if($b['ips'] !== null) · {{ number_format($b['ips']) }} {{ Str::plural('IP', $b['ips']) }}@endif @if($b['note']) · {{ $b['note'] }}@endif</p>
                            </div>
                        </div>
                        <span class="text-sm font-bold text-gray-900 tabular-nums">{{ number_format($b['views']) }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
    @if($traffic['pending'] > 0)
        <p class="px-5 pb-4 text-[11px] text-gray-400">⏳ Looking up the network behind {{ number_format($traffic['pending']) }} {{ Str::plural('visit', $traffic['pending']) }} (Amazon, Google Cloud, mobile operator …) — refresh in a minute; they show as “No interaction” until then.</p>
    @endif
</div>
