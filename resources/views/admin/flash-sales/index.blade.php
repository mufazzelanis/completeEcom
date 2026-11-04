@extends('layouts.admin')
@section('title', 'Flash Sales')

@section('content')
@php $tz = setting('timezone', 'Asia/Dhaka'); @endphp
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-xl font-bold text-gray-800">Flash Sales</h1>
        <p class="text-sm text-gray-500 mt-0.5">Time-limited deals with live countdowns, stock caps and per-order limits</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('flash-sale.show') }}" target="_blank" class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 transition">View sale page ↗</a>
        <a href="{{ route('admin.flash-sales.create') }}" class="flex items-center gap-2 bg-gradient-to-r from-orange-500 to-rose-500 text-white px-4 py-2 rounded-xl text-sm font-bold hover:opacity-95 transition shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            New Flash Sale
        </a>
    </div>
</div>

@if(session('success'))
<div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 mb-6 text-sm">{{ session('success') }}</div>
@endif

{{-- Stats --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
    @foreach([
        ['Live now', $stats['live'], 'text-emerald-600', 'bg-emerald-50'],
        ['Scheduled', $stats['upcoming'], 'text-blue-600', 'bg-blue-50'],
        ['Units sold (all sales)', number_format($stats['units']), 'text-orange-600', 'bg-orange-50'],
        ['Est. flash revenue', '৳' . number_format($stats['revenue']), 'text-rose-600', 'bg-rose-50'],
    ] as [$label, $value, $color, $bg])
        <div class="bg-white rounded-2xl shadow-sm p-4 flex items-center gap-3">
            <span class="w-10 h-10 rounded-xl {{ $bg }} {{ $color }} flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M11.983 1.907a.75.75 0 00-1.292-.657l-8 9.5A.75.75 0 003.25 12H9v6.093a.75.75 0 001.292.657l8-9.5A.75.75 0 0018 8H12V1.907z" clip-rule="evenodd"/></svg>
            </span>
            <div class="min-w-0">
                <p class="text-xl font-extrabold text-gray-900 leading-tight">{{ $value }}</p>
                <p class="text-xs text-gray-500 truncate">{{ $label }}</p>
            </div>
        </div>
    @endforeach
</div>

{{-- Filter --}}
<div class="flex gap-1.5 mb-4 overflow-x-auto">
    @foreach(['all' => 'All', 'live' => 'Live', 'upcoming' => 'Upcoming', 'ended' => 'Ended', 'inactive' => 'Paused'] as $key => $label)
        <a href="{{ route('admin.flash-sales.index', $key === 'all' ? [] : ['status' => $key]) }}"
           class="shrink-0 rounded-full px-3.5 py-1.5 text-xs font-bold transition {{ $status === $key ? 'bg-gray-900 text-white' : 'bg-white text-gray-600 shadow-sm hover:bg-gray-50' }}">{{ $label }}</a>
    @endforeach
</div>

<div class="bg-white rounded-2xl shadow-sm overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr class="text-xs text-gray-500 uppercase tracking-wider">
                <th class="px-5 py-3 text-left">Sale</th>
                <th class="px-5 py-3 text-center">Status</th>
                <th class="px-5 py-3 text-left">Schedule</th>
                <th class="px-5 py-3 text-center">Deals</th>
                <th class="px-5 py-3 text-center">Sold</th>
                <th class="px-5 py-3 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($sales as $sale)
            @php $p = $sale->palette; @endphp
            <tr class="hover:bg-gray-50 transition">
                <td class="px-5 py-4">
                    <div class="flex items-center gap-3">
                        <span class="w-9 h-9 rounded-xl shrink-0 flex items-center justify-center text-white" style="background: linear-gradient(135deg, {{ $p['from'] }}, {{ $p['to'] }})">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M11.983 1.907a.75.75 0 00-1.292-.657l-8 9.5A.75.75 0 003.25 12H9v6.093a.75.75 0 001.292.657l8-9.5A.75.75 0 0018 8H12V1.907z" clip-rule="evenodd"/></svg>
                        </span>
                        <div class="min-w-0">
                            <a href="{{ route('admin.flash-sales.edit', $sale) }}" class="font-semibold text-gray-800 hover:text-indigo-600">{{ $sale->name }}</a>
                            <p class="text-xs text-gray-400 mt-0.5 truncate max-w-xs">{{ $sale->banner_text ?: '/flash-sale/' . $sale->slug }}</p>
                        </div>
                    </div>
                </td>
                <td class="px-5 py-4 text-center">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold {{ $sale->status_badge }}">
                        @if($sale->status === 'live')<span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>@endif
                        {{ $sale->status === 'inactive' ? 'PAUSED' : strtoupper($sale->status) }}
                    </span>
                    @if($sale->priority)<p class="text-[10px] text-gray-400 mt-1">priority {{ $sale->priority }}</p>@endif
                </td>
                <td class="px-5 py-4 text-xs text-gray-500 min-w-[13rem]">
                    <p>{{ $sale->starts_at->copy()->timezone($tz)->format('M d, Y g:i A') }}</p>
                    <p class="text-gray-400">→ {{ $sale->ends_at->copy()->timezone($tz)->format('M d, Y g:i A') }}</p>
                    @if($sale->status === 'live')
                        <div class="mt-1.5 h-1 rounded-full bg-gray-100 overflow-hidden"><div class="h-full bg-emerald-500 rounded-full" style="width: {{ $sale->elapsed_percent }}%"></div></div>
                        <p class="mt-0.5 text-[10px] text-emerald-600 font-semibold">{{ $sale->ends_at->diffForHumans(['parts' => 2, 'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]) }} left</p>
                    @elseif($sale->status === 'upcoming')
                        <p class="mt-1 text-[10px] text-blue-600 font-semibold">starts {{ $sale->starts_at->diffForHumans(['parts' => 2]) }}</p>
                    @endif
                </td>
                <td class="px-5 py-4 text-center font-semibold text-gray-700">{{ $sale->products_count }}</td>
                <td class="px-5 py-4 text-center font-semibold text-gray-700">{{ number_format((int) $sale->units_sold) }}</td>
                <td class="px-5 py-4">
                    <div class="flex items-center justify-end gap-1">
                        <a href="{{ route('admin.flash-sales.edit', $sale) }}" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-indigo-600 hover:bg-indigo-50">Edit</a>
                        <a href="{{ $sale->url }}" target="_blank" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-gray-600 hover:bg-gray-100">View</a>
                        <form action="{{ route('admin.flash-sales.toggle', $sale) }}" method="POST">
                            @csrf @method('PATCH')
                            <button class="rounded-lg px-2.5 py-1.5 text-xs font-semibold {{ $sale->is_active ? 'text-amber-600 hover:bg-amber-50' : 'text-emerald-600 hover:bg-emerald-50' }}">{{ $sale->is_active ? 'Pause' : 'Activate' }}</button>
                        </form>
                        <form action="{{ route('admin.flash-sales.duplicate', $sale) }}" method="POST">
                            @csrf
                            <button class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-gray-600 hover:bg-gray-100">Duplicate</button>
                        </form>
                        <form action="{{ route('admin.flash-sales.destroy', $sale) }}" method="POST" onsubmit="return uiConfirm(event, 'Delete this flash sale?')">
                            @csrf @method('DELETE')
                            <button class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-red-500 hover:bg-red-50">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-6 py-16 text-center text-gray-400">No flash sales here yet. <a href="{{ route('admin.flash-sales.create') }}" class="text-indigo-600">Create one</a></td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-6 py-4 border-t border-gray-100">{{ $sales->links() }}</div>
</div>
@endsection
