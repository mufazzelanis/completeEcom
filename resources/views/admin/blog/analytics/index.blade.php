@extends('layouts.admin')
@section('title', 'Blog Analytics')

@php
    $dur = fn ($s) => $s >= 60 ? floor($s / 60) . 'm ' . str_pad((int) round(fmod($s, 60)), 2, '0', STR_PAD_LEFT) . 's' : round($s) . 's';
    $sortLink = fn ($col) => route('admin.blog.analytics', array_filter(['period' => $r['period'], 'from' => $r['period'] === 'custom' ? $r['from']->toDateString() : null, 'to' => $r['period'] === 'custom' ? $r['to']->toDateString() : null, 'search' => request('search'), 'sort' => $col]));
    $cols = [
        'readers' => 'Readers', 'reads' => 'Views', 'avg_time' => 'Avg. time', 'avg_scroll' => 'Read depth',
        'finish_rate' => 'Finished', 'clicks' => 'Product clicks', 'ctr' => 'Click rate', 'orders' => 'Orders', 'revenue' => 'Sales',
    ];
@endphp

@section('content')
<div class="space-y-5">
    {{-- header --}}
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Blog analytics</h1>
            <p class="text-sm text-gray-500 mt-0.5">Who read which post, how long and how far they read, and which products they clicked and bought. <span class="text-gray-400">{{ $r['label'] }} · {{ $r['tz'] }}</span></p>
        </div>
        <div class="flex items-center gap-2">
            <div class="relative group">
                <span class="inline-flex items-center gap-2 bg-white rounded-xl shadow-sm px-3 py-2 text-sm font-semibold text-gray-800">
                    <span class="relative flex w-2.5 h-2.5">
                        @if($live['total'])<span class="absolute inset-0 rounded-full bg-emerald-400 animate-ping opacity-60"></span>@endif
                        <span class="relative w-2.5 h-2.5 rounded-full {{ $live['total'] ? 'bg-emerald-500' : 'bg-gray-300' }}"></span>
                    </span>
                    {{ $live['total'] }} reading now
                </span>
                @if($live['posts']->isNotEmpty())
                    <div class="hidden group-hover:block absolute right-0 top-full mt-1.5 z-20 w-72 bg-white rounded-xl shadow-xl ring-1 ring-gray-100 p-3 space-y-1.5">
                        @foreach($live['posts'] as $lp)
                            <a href="{{ route('admin.blog.analytics.show', $lp['slug']) }}" class="flex items-center justify-between gap-2 text-xs hover:text-indigo-600">
                                <span class="truncate text-gray-700">{{ $lp['title'] }}</span><span class="font-bold text-emerald-600">{{ $lp['n'] }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
            <a href="{{ route('admin.blog.posts.index') }}" class="bg-white rounded-xl shadow-sm px-3 py-2 text-sm font-medium text-gray-700 hover:text-indigo-600">All posts</a>
        </div>
    </div>

    @include('admin.blog.analytics._period', ['route' => 'admin.blog.analytics'])

    @include('admin.blog.analytics._kpis')

    @include('admin.blog.analytics._trend')

    @include('admin.blog.analytics._traffic', ['logParams' => array_filter(['period' => $r['period'], 'from' => $r['period'] === 'custom' ? $r['from']->toDateString() : null, 'to' => $r['period'] === 'custom' ? $r['to']->toDateString() : null])])

    {{-- posts --}}
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 border-b border-gray-100">
            <div>
                <h3 class="font-semibold text-gray-800">Posts · {{ $r['label'] }}</h3>
                <p class="text-xs text-gray-400 mt-0.5">{{ $rows->count() }} {{ Str::plural('post', $rows->count()) }} read @if($silent) · {{ $silent }} published {{ Str::plural('post', $silent) }} had no readers @endif · click a column to sort</p>
            </div>
            <form method="GET" class="flex items-center gap-2">
                @foreach(['period' => $r['period'], 'sort' => $sort] as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
                @if($r['period'] === 'custom')<input type="hidden" name="from" value="{{ $r['from']->toDateString() }}"><input type="hidden" name="to" value="{{ $r['to']->toDateString() }}">@endif
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Find a post…" class="border border-gray-200 rounded-xl px-3 py-2 text-sm w-48 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-[11px] uppercase tracking-wider text-gray-500">
                    <tr>
                        <th class="text-left font-semibold px-5 py-3">Post</th>
                        @foreach($cols as $key => $label)
                            <th class="text-right font-semibold px-3 py-3 whitespace-nowrap">
                                <a href="{{ $sortLink($key) }}" class="inline-flex items-center gap-0.5 hover:text-indigo-600 {{ $sort === $key ? 'text-indigo-600' : '' }}">{{ $label }}@if($sort === $key)<span>↓</span>@endif</a>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($rows as $row)
                        @php
                            $chg = $row->prev ? round(($row->readers - $row->prev) / $row->prev * 100) : null;
                        @endphp
                        <tr class="hover:bg-gray-50/60">
                            <td class="px-5 py-3 min-w-[16rem]">
                                <a href="{{ route('admin.blog.analytics.show', [$row->post] + array_filter(['period' => $r['period'] === 'today' ? null : $r['period']])) }}" class="flex items-center gap-3 group">
                                    @if($row->post->image)
                                        <img src="{{ image_thumb($row->post->image, 96) }}" alt="" class="w-10 h-10 rounded-lg object-cover flex-shrink-0">
                                    @else
                                        <span class="w-10 h-10 rounded-lg bg-gray-100 flex-shrink-0"></span>
                                    @endif
                                    <span class="min-w-0">
                                        <span class="block font-medium text-gray-800 group-hover:text-indigo-600 line-clamp-1">{{ $row->post->title }}</span>
                                        <span class="block text-[11px] text-gray-400">{{ $row->post->category->name ?? 'Uncategorized' }}</span>
                                    </span>
                                </a>
                            </td>
                            <td class="px-3 py-3 text-right whitespace-nowrap">
                                <span class="font-bold text-gray-900 tabular-nums">{{ number_format($row->readers) }}</span>
                                @if($chg !== null && $chg != 0)
                                    <span class="block text-[10px] font-semibold {{ $chg > 0 ? 'text-emerald-600' : 'text-red-500' }}">{{ $chg > 0 ? '▲' : '▼' }} {{ abs($chg) }}%</span>
                                @elseif(!$row->prev && $row->readers)
                                    <span class="block text-[10px] font-semibold text-emerald-600">new</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-right tabular-nums text-gray-600">{{ number_format($row->reads) }}</td>
                            <td class="px-3 py-3 text-right tabular-nums text-gray-600 whitespace-nowrap">{{ $dur($row->avg_time) }}</td>
                            <td class="px-3 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <div class="w-16 h-1.5 rounded-full bg-gray-100 overflow-hidden"><div class="h-full rounded-full" style="width: {{ $row->avg_scroll }}%; background:#2a78d6"></div></div>
                                    <span class="tabular-nums text-gray-600 w-8 text-right">{{ round($row->avg_scroll) }}%</span>
                                </div>
                            </td>
                            <td class="px-3 py-3 text-right tabular-nums text-gray-600">{{ round($row->finish_rate) }}%</td>
                            <td class="px-3 py-3 text-right tabular-nums whitespace-nowrap">
                                <span class="font-semibold text-gray-800">{{ number_format($row->clicks) }}</span>
                                @if($row->intents)<span class="block text-[10px] text-gray-400">{{ $row->intents }} cart/buy</span>@endif
                            </td>
                            <td class="px-3 py-3 text-right tabular-nums text-gray-600">{{ number_format($row->ctr, 1) }}%</td>
                            <td class="px-3 py-3 text-right tabular-nums font-semibold {{ $row->orders ? 'text-gray-900' : 'text-gray-300' }}">{{ $row->orders }}</td>
                            <td class="px-3 py-3 text-right tabular-nums font-semibold whitespace-nowrap {{ $row->revenue ? 'text-emerald-700' : 'text-gray-300' }}">৳{{ number_format($row->revenue) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="px-5 py-12 text-center text-sm text-gray-400">No one has read a post in this period yet. Reads are counted from the moment this tracking went live.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        @include('admin.blog.analytics._bars', ['title' => 'Where readers came from', 'data' => $sources, 'labels' => \App\Models\BlogRead::SOURCES])
        @include('admin.blog.analytics._bars', ['title' => 'Devices', 'data' => $devices])

        <div class="bg-white rounded-2xl shadow-sm p-5">
            <h3 class="font-semibold text-gray-800 mb-3">Most-clicked products in posts</h3>
            @forelse($topProducts as $tp)
                <div class="flex items-center gap-3 py-2 border-b border-gray-50 last:border-0">
                    <span class="w-9 h-9 rounded-lg bg-gray-50 overflow-hidden flex-shrink-0">@if($tp->product->image)<img src="{{ image_thumb($tp->product->image, 96) }}" alt="" class="w-full h-full object-contain">@endif</span>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-medium text-gray-800 truncate">{{ $tp->product->name }}</p>
                        <p class="text-[11px] text-gray-400">{{ $tp->opens }} opened · {{ $tp->intents }} cart/buy · in {{ $tp->posts }} {{ Str::plural('post', $tp->posts) }}</p>
                    </div>
                    <span class="text-sm font-bold text-gray-900 tabular-nums">{{ $tp->total }}</span>
                </div>
            @empty
                <p class="text-sm text-gray-400">No product clicks yet. Add products to your posts (Edit post → Products in this article).</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
