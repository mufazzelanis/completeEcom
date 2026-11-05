@extends('layouts.admin')
@section('title', 'Blog visitor log')

@php
    $dur = fn ($s) => $s >= 60 ? floor($s / 60) . 'm ' . str_pad((int) round(fmod($s, 60)), 2, '0', STR_PAD_LEFT) . 's' : round($s) . 's';
    $tz = $r['tz'];
    $base = array_filter(['period' => $r['period'], 'from' => $r['period'] === 'custom' ? $r['from']->toDateString() : null, 'to' => $r['period'] === 'custom' ? $r['to']->toDateString() : null, 'post' => $post?->slug, 'ip' => request('ip')]);
    $tabs = [null => 'Everyone', 'human' => '👤 Real people', 'bot' => '🤖 Bots', 'cloud' => '☁️ Cloud servers', 'unknown' => '❔ No interaction'];
@endphp

@section('content')
<div class="space-y-5">
    <a href="{{ $post ? route('admin.blog.analytics.show', [$post, 'period' => $r['period']]) : route('admin.blog.analytics', ['period' => $r['period']]) }}" class="text-sm text-gray-500 hover:text-gray-700 inline-flex items-center gap-1">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        {{ $post ? Str::limit($post->title, 50) : 'Blog analytics' }}
    </a>

    <div>
        <h1 class="text-xl font-bold text-gray-900">Visitor log</h1>
        <p class="text-sm text-gray-500 mt-0.5">Every view of {{ $post ? 'this post' : 'your blog' }} and who it really was: a person, a named bot (Googlebot, GPTBot …), a cloud server (Amazon AWS …), or a view with no interaction. {{ $r['label'] }}.</p>
    </div>

    @include('admin.blog.analytics._period', ['route' => 'admin.blog.analytics.visits', 'params' => array_filter(['post' => $post?->slug, 'type' => $type, 'ip' => request('ip')])])

    @include('admin.blog.analytics._traffic', ['logParams' => $base])

    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <div class="flex flex-wrap items-center gap-1.5 px-5 py-3 border-b border-gray-100">
            @foreach($tabs as $key => $label)
                <a href="{{ route('admin.blog.analytics.visits', $base + array_filter(['type' => $key])) }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $type === $key ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100' }}">{{ $label }}</a>
            @endforeach
            @if(request('ip'))
                <a href="{{ route('admin.blog.analytics.visits', array_diff_key($base, ['ip' => 1]) + array_filter(['type' => $type])) }}" class="ml-auto text-xs font-semibold text-gray-600 bg-gray-100 rounded-full px-2.5 py-1">IP {{ request('ip') }} ✕</a>
            @endif
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-gray-50 text-[11px] uppercase tracking-wider text-gray-500">
                    <tr>
                        <th class="text-left font-semibold px-5 py-3">When</th>
                        <th class="text-left font-semibold px-3 py-3">Who</th>
                        @unless($post)<th class="text-left font-semibold px-3 py-3">Post</th>@endunless
                        <th class="text-left font-semibold px-3 py-3">IP · network · location</th>
                        <th class="text-left font-semibold px-3 py-3">Device</th>
                        <th class="text-left font-semibold px-3 py-3">Came from</th>
                        <th class="text-right font-semibold px-3 py-3">Read</th>
                        <th class="text-right font-semibold px-3 py-3">Depth</th>
                        <th class="text-right font-semibold px-5 py-3">Products</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($visits as $v)
                        <tr class="{{ $v->verdict['type'] === 'human' ? '' : 'bg-gray-50/40' }}">
                            <td class="px-5 py-2.5 whitespace-nowrap text-gray-500">
                                {{ $v->created_at->timezone($tz)->format('d M, g:i:s A') }}
                                @if($v->engaged_at && $v->updated_at->gte(now()->subMinutes(2)))<span class="block text-[10px] font-semibold text-emerald-600">● reading now</span>@endif
                            </td>
                            <td class="px-3 py-2.5 min-w-[11rem]">@include('admin.blog.analytics._verdict', ['v' => $v->verdict, 'showKind' => true, 'showNote' => true])</td>
                            @unless($post)
                                <td class="px-3 py-2.5 max-w-[14rem]">
                                    @if($v->post)<a href="{{ route('admin.blog.analytics.visits', $base + ['post' => $v->post->slug]) }}" class="text-gray-700 hover:text-indigo-600 line-clamp-1">{{ $v->post->title }}</a>@endif
                                </td>
                            @endunless
                            <td class="px-3 py-2.5 whitespace-nowrap">
                                @if($v->ip)
                                    <a href="{{ route('admin.blog.analytics.visits', $base + ['ip' => $v->ip]) }}" class="font-mono text-gray-600 hover:text-indigo-600">{{ $v->ip }}</a>
                                @else<span class="text-gray-300">—</span>@endif
                                <span class="block text-[10px] text-gray-400">{{ collect([$v->verdict['network'], $v->verdict['location']])->filter()->implode(' · ') ?: '…looking up' }}</span>
                            </td>
                            <td class="px-3 py-2.5 text-gray-500 whitespace-nowrap">
                                {{ $v->ua_device }}@if($v->ua_browser) · {{ $v->ua_browser }}@endif
                                @if($v->ua_platform)<span class="block text-[10px] text-gray-400">{{ $v->ua_platform }}</span>@endif
                            </td>
                            <td class="px-3 py-2.5 text-gray-500 whitespace-nowrap">{{ \App\Models\BlogRead::SOURCES[$v->source] ?? $v->source }}@if($v->referrer_host)<span class="block text-[10px] text-gray-400">{{ Str::limit($v->referrer_host, 24) }}</span>@endif</td>
                            <td class="px-3 py-2.5 text-right tabular-nums {{ $v->engaged_at ? 'font-medium text-gray-700' : 'text-gray-300' }}">{{ $v->engaged_at ? $dur($v->active_seconds) : '—' }}</td>
                            <td class="px-3 py-2.5 text-right tabular-nums {{ $v->engaged_at ? 'text-gray-600' : 'text-gray-300' }}">{{ $v->engaged_at ? $v->max_scroll . '%' : '—' }}</td>
                            <td class="px-5 py-2.5 text-right">@if($v->product_clicks)<span class="font-semibold text-orange-600">🛍 {{ $v->product_clicks }}</span>@else<span class="text-gray-300">—</span>@endif</td>
                        </tr>
                        @if($v->user_agent && $v->verdict['type'] !== 'human')
                            <tr class="bg-gray-50/40"><td></td><td colspan="{{ $post ? 7 : 8 }}" class="px-3 pb-2 -mt-1 text-[10px] font-mono text-gray-400 break-all">{{ Str::limit($v->user_agent, 220) }}</td></tr>
                        @endif
                    @empty
                        <tr><td colspan="9" class="px-5 py-12 text-center text-sm text-gray-400">No visits match.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($visits->hasPages())<div class="px-5 py-3 border-t border-gray-100">{{ $visits->links() }}</div>@endif
        @if(in_array($type, ['cloud', 'unknown'], true))
            <p class="px-5 pb-3 text-[11px] text-gray-400">Cloud and no-interaction visits are told apart by the IP's network, so a page can show fewer than 50 rows.</p>
        @endif
    </div>
</div>
@endsection
