{{-- Period picker shared by Blog analytics pages. $r: BlogAnalyticsController::range(); $route + $params build the links. --}}
@php
    $params = $params ?? [];
    $keep = array_filter(['search' => request('search'), 'sort' => request('sort')]);
@endphp
<div class="flex flex-wrap items-center gap-2">
    <div class="inline-flex flex-wrap bg-white rounded-xl shadow-sm p-1 gap-0.5">
        @foreach(\App\Http\Controllers\Admin\BlogAnalyticsController::PERIODS as $key => $label)
            <a href="{{ route($route, $params + $keep + ['period' => $key]) }}"
               class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $r['period'] === $key ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100' }}">{{ $label }}</a>
        @endforeach
    </div>
    <form method="GET" action="{{ route($route, $params) }}" class="inline-flex items-center gap-1.5 bg-white rounded-xl shadow-sm px-2 py-1 {{ $r['period'] === 'custom' ? 'ring-2 ring-indigo-500' : '' }}">
        <input type="hidden" name="period" value="custom">
        @foreach($keep as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
        <input type="date" name="from" value="{{ $r['from']->toDateString() }}" max="{{ now($r['tz'])->toDateString() }}" class="border-0 text-xs text-gray-700 p-1 focus:ring-0 bg-transparent">
        <span class="text-gray-300 text-xs">→</span>
        <input type="date" name="to" value="{{ $r['to']->toDateString() }}" max="{{ now($r['tz'])->toDateString() }}" class="border-0 text-xs text-gray-700 p-1 focus:ring-0 bg-transparent">
        <button class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 px-1.5">Apply</button>
    </form>
</div>
