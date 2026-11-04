@extends('layouts.admin')
@section('title', 'Analytics · ' . $post->title)

@php
    $dur = fn ($s) => $s >= 60 ? floor($s / 60) . 'm ' . str_pad((int) round(fmod($s, 60)), 2, '0', STR_PAD_LEFT) . 's' : round($s) . 's';
    $n = max(1, (int) $depth->n);
    $funnel = [
        ['Started reading', (int) $depth->n],
        ['Reached 25%', (int) $depth->d25],
        ['Reached half', (int) $depth->d50],
        ['Reached 75%', (int) $depth->d75],
        ['Read to the end', (int) $depth->d100],
    ];
    $timeBuckets = ['Under 10s' => $time->t0, '10–30s' => $time->t1, '30s–1m' => $time->t2, '1–3 min' => $time->t3, '3–5 min' => $time->t4, '5 min +' => $time->t5];
    $timeMax = max(1, ...array_map('intval', array_values($timeBuckets)));
    $tz = $r['tz'];
@endphp

@section('content')
<div class="space-y-5">
    <a href="{{ route('admin.blog.analytics', array_filter(['period' => $r['period'] === 'custom' ? null : $r['period']])) }}" class="text-sm text-gray-500 hover:text-gray-700 inline-flex items-center gap-1">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Blog analytics
    </a>

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="flex items-center gap-4 min-w-0">
            @if($post->image)<img src="{{ image_thumb($post->image, 200) }}" alt="" class="w-16 h-16 rounded-xl object-cover flex-shrink-0">@endif
            <div class="min-w-0">
                <h1 class="text-lg sm:text-xl font-bold text-gray-900 line-clamp-2">{{ $post->title }}</h1>
                <p class="text-sm text-gray-500 mt-0.5">
                    {{ $r['label'] }} ·
                    <span class="{{ $liveNow ? 'text-emerald-600 font-semibold' : '' }}">{{ $liveNow }} reading now</span>
                    · {{ number_format($post->views) }} total views
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.blog.posts.edit', $post) }}" class="bg-white rounded-xl shadow-sm px-3 py-2 text-sm font-medium text-gray-700 hover:text-indigo-600">Edit post</a>
            <a href="{{ route('blog.show', $post) }}" target="_blank" class="bg-indigo-600 rounded-xl shadow-sm px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700">View post →</a>
        </div>
    </div>

    @include('admin.blog.analytics._period', ['route' => 'admin.blog.analytics.show', 'params' => ['post' => $post]])

    @include('admin.blog.analytics._kpis')

    @include('admin.blog.analytics._trend')

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        {{-- how far people read --}}
        <div class="bg-white rounded-2xl shadow-sm p-5">
            <h3 class="font-semibold text-gray-800">How far people read</h3>
            <p class="text-xs text-gray-400 mb-4">Share of article views that scrolled at least this far through the text.</p>
            @foreach($funnel as [$label, $count])
                <div class="flex items-center gap-3 mb-2.5 last:mb-0">
                    <span class="w-28 text-xs text-gray-600 flex-shrink-0">{{ $label }}</span>
                    <div class="flex-1 h-6 rounded-lg bg-gray-50 overflow-hidden">
                        <div class="h-full rounded-lg" style="width: {{ $depth->n ? max(1.5, $count / $n * 100) : 0 }}%; background:#2a78d6"></div>
                    </div>
                    <span class="w-20 text-right text-xs tabular-nums"><b class="text-gray-900">{{ $depth->n ? round($count / $n * 100) : 0 }}%</b> <span class="text-gray-400">{{ number_format($count) }}</span></span>
                </div>
            @endforeach
            @if($depth->n && $depth->d50 / $n < .4)
                <p class="mt-4 text-xs text-amber-700 bg-amber-50 rounded-lg px-3 py-2">Most readers leave before the middle — put the first product block higher up, near the top of the article.</p>
            @endif
        </div>

        {{-- time spent --}}
        <div class="bg-white rounded-2xl shadow-sm p-5">
            <h3 class="font-semibold text-gray-800">Time spent reading</h3>
            <p class="text-xs text-gray-400 mb-4">Active time: tab in front and the reader scrolling, tapping or moving within the last 30 seconds.</p>
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

    {{-- products --}}
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800">Products in this post</h3>
            <p class="text-xs text-gray-400 mt-0.5">Opens, add-to-carts and buy-now taps from this post, and where on the page they happened.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-[11px] uppercase tracking-wider text-gray-500">
                    <tr>
                        <th class="text-left font-semibold px-5 py-3">Product</th>
                        <th class="text-right font-semibold px-3 py-3">Clicks</th>
                        <th class="text-right font-semibold px-3 py-3">Opened</th>
                        <th class="text-right font-semibold px-3 py-3">To cart</th>
                        <th class="text-right font-semibold px-3 py-3">Buy now</th>
                        <th class="text-left font-semibold px-3 py-3 whitespace-nowrap">Where clicked</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @php $shown = $productStats->pluck('product_id'); @endphp
                    @foreach($productStats as $ps)
                        @php $p = $products->get($ps->product_id); @endphp
                        @continue(!$p)
                        <tr>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="w-10 h-10 rounded-lg bg-gray-50 overflow-hidden flex-shrink-0">@if($p->image)<img src="{{ image_thumb($p->image, 96) }}" alt="" class="w-full h-full object-contain">@endif</span>
                                    <div class="min-w-0">
                                        <a href="{{ route('products.show', $p->slug) }}" target="_blank" class="font-medium text-gray-800 hover:text-indigo-600 line-clamp-1">{{ $p->name }}</a>
                                        @unless($attachedIds->contains($p->id))<span class="text-[10px] text-gray-400">matched automatically / removed from post</span>@endunless
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-3 text-right font-bold text-gray-900 tabular-nums">{{ $ps->total }}</td>
                            <td class="px-3 py-3 text-right tabular-nums text-gray-600">{{ (int) $ps->opens }}</td>
                            <td class="px-3 py-3 text-right tabular-nums text-gray-600">{{ (int) $ps->carts }}</td>
                            <td class="px-3 py-3 text-right tabular-nums text-gray-600">{{ (int) $ps->buys }}</td>
                            <td class="px-3 py-3">
                                <div class="flex flex-wrap gap-1">
                                    @foreach(\App\Models\BlogProductClick::PLACEMENTS as $key => $label)
                                        @if($ps->{'p_' . $key})<span class="text-[10px] font-medium text-gray-600 bg-gray-100 rounded-full px-2 py-0.5 whitespace-nowrap">{{ $label }} · {{ (int) $ps->{'p_' . $key} }}</span>@endif
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    @foreach($attachedIds->diff($shown) as $pid)
                        @php $p = $products->get($pid); @endphp
                        @continue(!$p)
                        <tr class="text-gray-400">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="w-10 h-10 rounded-lg bg-gray-50 overflow-hidden flex-shrink-0 opacity-60">@if($p->image)<img src="{{ image_thumb($p->image, 96) }}" alt="" class="w-full h-full object-contain">@endif</span>
                                    <span class="line-clamp-1">{{ $p->name }}</span>
                                </div>
                            </td>
                            <td class="px-3 py-3 text-right" colspan="5">No clicks in this period</td>
                        </tr>
                    @endforeach
                    @if($productStats->isEmpty() && $attachedIds->isEmpty())
                        <tr><td colspan="6" class="px-5 py-10 text-center text-sm text-gray-400">This post has no products yet. <a href="{{ route('admin.blog.posts.edit', $post) }}" class="text-indigo-600 font-medium">Add some →</a></td></tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        @include('admin.blog.analytics._bars', ['title' => 'Where readers came from', 'data' => $sources, 'labels' => \App\Models\BlogRead::SOURCES])
        @include('admin.blog.analytics._bars', ['title' => 'Referring sites', 'data' => $referrers])
        @include('admin.blog.analytics._bars', ['title' => 'Devices', 'data' => $devices])
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        {{-- orders --}}
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-800">Orders from this post</h3>
                <p class="text-xs text-gray-400 mt-0.5">Placed within 7 days of clicking a product here.</p>
            </div>
            <div class="divide-y divide-gray-50">
                @forelse($conversions as $cv)
                    <div class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                        <div class="min-w-0">
                            @if($cv->order)
                                <a href="{{ route('admin.orders.show', $cv->order) }}" class="font-semibold text-gray-800 hover:text-indigo-600">{{ $cv->order->order_number }}</a>
                                <span class="text-xs text-gray-400"> · {{ $cv->order->shipping_name }} · {{ ucfirst($cv->order->status) }}</span>
                            @endif
                            <p class="text-[11px] text-gray-400">{{ $cv->created_at->timezone($tz)->format('d M, g:i A') }}</p>
                        </div>
                        <div class="text-right whitespace-nowrap">
                            <p class="font-bold text-emerald-700">৳{{ number_format($cv->matched_revenue) }}</p>
                            <p class="text-[11px] text-gray-400">order ৳{{ number_format($cv->revenue) }}</p>
                        </div>
                    </div>
                @empty
                    <p class="px-5 py-10 text-center text-sm text-gray-400">No orders from this post in this period.</p>
                @endforelse
            </div>
        </div>

        {{-- latest reads --}}
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-800">Latest readers</h3>
                <p class="text-xs text-gray-400 mt-0.5">The 25 most recent views of this post.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <tbody class="divide-y divide-gray-50">
                        @forelse($recent as $rd)
                            <tr>
                                <td class="px-5 py-2.5 whitespace-nowrap text-gray-500">
                                    {{ $rd->created_at->timezone($tz)->format('d M, g:i A') }}
                                    @if($rd->updated_at->gte(now()->subMinutes(2)))<span class="ml-1 inline-block w-1.5 h-1.5 rounded-full bg-emerald-500" title="reading now"></span>@endif
                                </td>
                                <td class="px-2 py-2.5 whitespace-nowrap text-gray-600">{{ \App\Models\BlogRead::SOURCES[$rd->source] ?? $rd->source }}@if($rd->referrer_host)<span class="text-gray-400"> · {{ Str::limit($rd->referrer_host, 22) }}</span>@endif</td>
                                <td class="px-2 py-2.5 text-gray-500">{{ ucfirst($rd->device) }}</td>
                                <td class="px-2 py-2.5 text-right tabular-nums font-medium text-gray-700 whitespace-nowrap">{{ $dur($rd->active_seconds) }}</td>
                                <td class="px-2 py-2.5 text-right tabular-nums text-gray-600">{{ $rd->max_scroll }}%</td>
                                <td class="px-5 py-2.5 text-right whitespace-nowrap">
                                    @if($rd->product_clicks)<span class="font-semibold text-orange-600">🛍 {{ $rd->product_clicks }}</span>@else<span class="text-gray-300">—</span>@endif
                                </td>
                            </tr>
                        @empty
                            <tr><td class="px-5 py-10 text-center text-sm text-gray-400">No readers yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
