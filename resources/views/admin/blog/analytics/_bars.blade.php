{{-- A labelled share-of-total list (traffic sources, devices, referrers). $data: key => count; $labels: key => name --}}
@php $total = max(1, $data->sum()); @endphp
<div class="bg-white rounded-2xl shadow-sm p-5">
    <h3 class="font-semibold text-gray-800 mb-3">{{ $title }}</h3>
    @forelse($data as $key => $n)
        <div class="mb-2.5 last:mb-0">
            <div class="flex items-center justify-between text-xs mb-1">
                <span class="text-gray-700 font-medium truncate">{{ ($labels ?? [])[$key] ?? ucfirst($key) }}</span>
                <span class="text-gray-500 tabular-nums">{{ number_format($n) }} <span class="text-gray-400">· {{ round($n / $total * 100) }}%</span></span>
            </div>
            <div class="h-1.5 rounded-full bg-gray-100 overflow-hidden"><div class="h-full rounded-full" style="width: {{ max(2, $n / $total * 100) }}%; background:#2a78d6"></div></div>
        </div>
    @empty
        <p class="text-sm text-gray-400">No data for this period.</p>
    @endforelse
</div>
