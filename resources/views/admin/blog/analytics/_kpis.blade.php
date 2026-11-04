{{-- KPI tiles with the change vs the previous period. $kpi / $prev: BlogAnalyticsController::kpis() --}}
@php
    $dur = fn ($s) => $s >= 60 ? floor($s / 60) . 'm ' . str_pad((int) round(fmod($s, 60)), 2, '0', STR_PAD_LEFT) . 's' : round($s) . 's';
    $delta = function ($cur, $old, $points = false) {
        if ($points) {
            $d = $cur - $old;
            return abs($d) < 0.5 ? null : [($d > 0 ? '+' : '') . number_format($d, 0) . ' pts', $d > 0];
        }
        if (!$old) return $cur > 0 ? ['new', true] : null;
        $d = ($cur - $old) / $old * 100;
        return abs($d) < 0.5 ? null : [($d > 0 ? '+' : '') . number_format($d, 0) . '%', $d > 0];
    };
    $tiles = [
        ['Readers', number_format($kpi['readers']), 'unique people', $delta($kpi['readers'], $prev['readers'])],
        ['Article views', number_format($kpi['reads']), $kpi['readers'] ? number_format($kpi['reads'] / max(1, $kpi['readers']), 1) . ' per reader' : '—', $delta($kpi['reads'], $prev['reads'])],
        ['Avg. reading time', $dur($kpi['avg_time']), 'active time on the page', $delta($kpi['avg_time'], $prev['avg_time'])],
        ['Read to the end', number_format($kpi['finish_rate'], 0) . '%', 'avg. depth ' . number_format($kpi['avg_scroll'], 0) . '%', $delta($kpi['finish_rate'], $prev['finish_rate'], true)],
        ['Product clicks', number_format($kpi['clicks']), number_format($kpi['ctr'], 1) . '% of views clicked a product', $delta($kpi['clicks'], $prev['clicks'])],
        ['Cart / Buy now', number_format($kpi['carts'] + $kpi['buys']), $kpi['carts'] . ' to cart · ' . $kpi['buys'] . ' buy now', $delta($kpi['carts'] + $kpi['buys'], $prev['carts'] + $prev['buys'])],
        ['Orders from blog', number_format($kpi['orders']), number_format($kpi['conv_rate'], 1) . '% of readers ordered', $delta($kpi['orders'], $prev['orders'])],
        ['Blog product sales', '৳' . number_format($kpi['revenue']), 'whole orders ৳' . number_format($kpi['basket']), $delta($kpi['revenue'], $prev['revenue'])],
    ];
@endphp
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
    @foreach($tiles as [$label, $value, $sub, $d])
        <div class="bg-white rounded-2xl shadow-sm p-4">
            <div class="flex items-center justify-between gap-2">
                <p class="text-xs font-medium text-gray-500">{{ $label }}</p>
                @if($d)
                    <span class="text-[11px] font-bold rounded-full px-1.5 py-0.5 {{ $d[1] ? 'text-emerald-700 bg-emerald-50' : 'text-red-600 bg-red-50' }}" title="vs {{ $r['prevLabel'] }}">{{ $d[1] ? '▲' : '▼' }} {{ $d[0] }}</span>
                @endif
            </div>
            <p class="mt-1.5 text-2xl font-extrabold tracking-tight text-gray-900">{{ $value }}</p>
            <p class="mt-0.5 text-[11px] text-gray-400 truncate" title="{{ $sub }}">{{ $sub }}</p>
        </div>
    @endforeach
</div>
<p class="mt-2 text-[11px] text-gray-400">Changes compared with {{ $r['prevLabel'] }}. Sales count only the products from the post in each order.</p>
