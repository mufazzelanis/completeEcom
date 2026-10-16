@extends('layouts.admin')
@section('title', 'Activity Log')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold text-gray-800">Activity Log</h1>
        <p class="text-sm text-gray-500 mt-0.5">Who logged in, viewed, and did what — across customers and admins</p>
    </div>
    <span class="text-sm text-gray-400">{{ $logs->total() }} total entries</span>
</div>

{{-- Stats --}}
<div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
    <div class="bg-white rounded-2xl shadow-sm p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wider font-medium">Logins Today</p>
        <p class="text-2xl font-bold text-gray-800 mt-1">{{ $stats['logins_today'] }}</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wider font-medium">Product Views Today</p>
        <p class="text-2xl font-bold text-gray-800 mt-1">{{ $stats['product_views_today'] }}</p>
        {{-- Split out so this number doesn't quietly read as "real people looked at a
             product today" when some of it is crawler/scraper traffic — a datacenter
             IP hammering product pages (the actual thing that prompted this) shows up
             here instead of inflating the headline count with nothing to back it. --}}
        @if($stats['product_views_today_bot'] > 0)
            <p class="text-[11px] text-gray-400 mt-1">incl. <span class="font-semibold text-orange-500">{{ $stats['product_views_today_bot'] }}</span> bot/crawler</p>
        @endif
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wider font-medium">Cart Adds Today</p>
        <p class="text-2xl font-bold text-gray-800 mt-1">{{ $stats['cart_adds_today'] }}</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-4 ring-1 ring-emerald-100">
        <p class="text-xs text-emerald-600 uppercase tracking-wider font-medium">Orders Placed Today</p>
        <p class="text-2xl font-bold text-emerald-700 mt-1">{{ $stats['orders_placed_today'] }}</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wider font-medium">Order Views Today</p>
        <p class="text-2xl font-bold text-gray-800 mt-1">{{ $stats['order_views_today'] }}</p>
    </div>
</div>

{{-- Filters --}}
<div class="bg-white rounded-2xl shadow-sm p-4 mb-6">
    <form action="{{ route('admin.activity-logs.index') }}" method="GET" class="flex flex-wrap items-end gap-3"
        x-data="{ period: '{{ request('period') ?: (request('from') || request('to') ? 'custom' : '') }}' }">
        <div class="flex-1 min-w-40">
            <label class="text-xs font-medium text-gray-500 uppercase mb-1 block">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search description..."
                class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div>
            <label class="text-xs font-medium text-gray-500 uppercase mb-1 block">Event</label>
            <select name="event_type" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">All Events</option>
                @foreach($eventTypes as $e)
                <option value="{{ $e }}" {{ request('event_type') === $e ? 'selected' : '' }}>{{ $e }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-medium text-gray-500 uppercase mb-1 block">Role</label>
            <select name="role" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">All Roles</option>
                <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                <option value="customer" {{ request('role') === 'customer' ? 'selected' : '' }}>Customer</option>
            </select>
        </div>
        <div>
            <label class="text-xs font-medium text-gray-500 uppercase mb-1 block">User</label>
            <select name="user_id" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">All Users</option>
                @foreach($logUsers as $u)
                <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-medium text-gray-500 uppercase mb-1 block">Traffic</label>
            <select name="traffic" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="" {{ request('traffic') === null || request('traffic') === '' ? 'selected' : '' }}>All</option>
                <option value="humans" {{ request('traffic') === 'humans' ? 'selected' : '' }}>Real users only</option>
                <option value="bots" {{ request('traffic') === 'bots' ? 'selected' : '' }}>Bots/crawlers only</option>
            </select>
        </div>
        <div>
            <label class="text-xs font-medium text-gray-500 uppercase mb-1 block">Period</label>
            <select name="period" x-model="period" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="" {{ request('period', request('from') || request('to') ? 'custom' : '') === '' ? 'selected' : '' }}>Lifetime</option>
                <option value="today" {{ request('period') === 'today' ? 'selected' : '' }}>Today</option>
                <option value="this_week" {{ request('period') === 'this_week' ? 'selected' : '' }}>This Week</option>
                <option value="this_month" {{ request('period') === 'this_month' ? 'selected' : '' }}>This Month</option>
                <option value="last_month" {{ request('period') === 'last_month' ? 'selected' : '' }}>Last Month</option>
                <option value="this_year" {{ request('period') === 'this_year' ? 'selected' : '' }}>This Year</option>
                <option value="custom" {{ request('period') === 'custom' || (!request('period') && (request('from') || request('to'))) ? 'selected' : '' }}>Custom Range</option>
            </select>
        </div>
        <div x-show="period === 'custom'" x-cloak>
            <label class="text-xs font-medium text-gray-500 uppercase mb-1 block">From</label>
            <input type="date" name="from" value="{{ request('from') }}"
                class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div x-show="period === 'custom'" x-cloak>
            <label class="text-xs font-medium text-gray-500 uppercase mb-1 block">To</label>
            <input type="date" name="to" value="{{ request('to') }}"
                class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div class="flex gap-2">
            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm hover:bg-indigo-700 transition">Filter</button>
            <a href="{{ route('admin.activity-logs.index') }}" class="bg-gray-100 text-gray-600 px-4 py-2 rounded-xl text-sm hover:bg-gray-200 transition">Clear</a>
        </div>
    </form>
</div>

{{-- Log Table --}}
<div class="bg-white rounded-2xl shadow-sm overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr class="text-xs text-gray-500 uppercase tracking-wider">
                <th class="px-5 py-3 text-left">Time</th>
                <th class="px-5 py-3 text-left">User</th>
                <th class="px-5 py-3 text-left">Phone</th>
                <th class="px-5 py-3 text-left">Event</th>
                <th class="px-5 py-3 text-left">Description</th>
                <th class="px-5 py-3 text-left">Source</th>
                <th class="px-5 py-3 text-left">Device</th>
                <th class="px-5 py-3 text-left">IP</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($logs as $log)
            <tr class="hover:bg-gray-50 transition">
                <td class="px-5 py-3 whitespace-nowrap">
                    {{-- setTimezone — created_at is stored in app.timezone (UTC); shown
                         bare, every timestamp on this page read 6 hours behind an admin's
                         actual Dhaka time, reported as the log "not looking real-time". --}}
                    @php $logTime = \Illuminate\Support\Carbon::parse($log->created_at)->setTimezone(setting('timezone', 'Asia/Dhaka')); @endphp
                    <p class="font-medium text-gray-800 text-xs">{{ $logTime->format('M d, Y') }}</p>
                    <p class="text-gray-400 text-xs">{{ $logTime->format('H:i:s') }}</p>
                </td>
                <td class="px-5 py-3">
                    @if($log->user_name)
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 bg-indigo-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <span class="text-indigo-600 text-xs font-bold">{{ strtoupper(substr($log->user_name, 0, 1)) }}</span>
                        </div>
                        <div>
                            <p class="text-gray-700 text-xs font-medium leading-tight">{{ $log->user_name }}</p>
                            @if($log->user_role)
                            <span class="inline-block mt-0.5 px-1.5 py-0 rounded-full text-[10px] font-medium {{ \App\Models\User::roleBadgeClass($log->user_role) }}">
                                {{ \App\Models\User::roleLabel($log->user_role) }}
                            </span>
                            @endif
                        </div>
                    </div>
                    @else
                    <span class="text-gray-400 text-xs italic">Guest</span>
                    @endif
                </td>
                <td class="px-5 py-3 text-xs text-gray-600 font-mono whitespace-nowrap">
                    @php
                        // order.place captures the shipping phone as of that specific order
                        // (properties), which stays accurate even if the account's phone changes
                        // later — falls back to the account's current phone for every other event.
                        $orderPhone = $log->properties ? (json_decode($log->properties, true)['phone'] ?? null) : null;
                        $phone = $orderPhone ?: $log->user_phone;
                    @endphp
                    {{ $phone ?: '—' }}
                </td>
                <td class="px-5 py-3">
                    @php
                        $color = match(true) {
                            $log->event_type === 'login' => 'bg-green-100 text-green-700',
                            $log->event_type === 'logout' => 'bg-gray-100 text-gray-600',
                            str_starts_with($log->event_type, 'product.') => 'bg-blue-100 text-blue-700',
                            $log->event_type === 'order.place' => 'bg-emerald-100 text-emerald-700',
                            str_starts_with($log->event_type, 'order.') => 'bg-purple-100 text-purple-700',
                            $log->event_type === 'cart.add' => 'bg-orange-100 text-orange-700',
                            $log->event_type === 'cart.remove' => 'bg-red-100 text-red-700',
                            str_contains($log->event_type, 'deleted') => 'bg-red-100 text-red-700',
                            str_contains($log->event_type, 'created') => 'bg-green-100 text-green-700',
                            str_contains($log->event_type, 'updated') => 'bg-blue-100 text-blue-700',
                            default => 'bg-gray-100 text-gray-700',
                        };
                        // Which provider this particular login came through — set only on
                        // social sign-ins (see RecordLoginActivityListener); a plain password
                        // login has no login_provider key and shows no badge at all.
                        $loginProvider = $log->event_type === 'login' && $log->properties
                            ? (json_decode($log->properties, true)['login_provider'] ?? null)
                            : null;
                        $providerBadge = match ($loginProvider) {
                            'google' => ['bg-red-50 text-red-600', 'Google'],
                            'facebook' => ['bg-blue-50 text-blue-600', 'Facebook'],
                            'linkedin' => ['bg-sky-50 text-sky-600', 'LinkedIn'],
                            default => null,
                        };
                    @endphp
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $color }}">
                        {{ $log->event_type }}
                    </span>
                    @if($log->source === 'audit')
                    <span class="block text-[10px] text-gray-400 mt-0.5">admin action</span>
                    @elseif($log->is_bot)
                    <span class="inline-flex items-center gap-0.5 text-[10px] text-orange-500 font-medium mt-0.5">🤖 bot/crawler</span>
                    @elseif($providerBadge)
                    <span class="inline-flex items-center px-1.5 py-0 rounded-full text-[10px] font-medium mt-0.5 {{ $providerBadge[0] }}">via {{ $providerBadge[1] }}</span>
                    @endif
                </td>
                <td class="px-5 py-3 text-gray-700 max-w-xs">
                    <div class="flex items-center gap-1.5" x-data="{ copied: false }">
                        {{-- $log->url is the exact page this event happened on (stored as
                             Request::fullUrl() at write time) — reusing it instead of
                             reconstructing a products.show() URL from subject_id means this
                             links correctly for every event type (product, order, page...)
                             without a route-specific lookup or an extra query per row. --}}
                        @if($log->url)
                            <a href="{{ $log->url }}" target="_blank" rel="noopener" class="truncate text-xs text-gray-700 hover:text-indigo-600 hover:underline" title="Open this page">{{ $log->description }}</a>
                        @else
                            <p class="truncate text-xs">{{ $log->description }}</p>
                        @endif
                        {{-- Copies the full description text (e.g. the product title inside
                             "Added "X" to cart") — an admin watching a specific product's
                             traffic needs the exact title to search/filter elsewhere without
                             retyping it by hand from a truncated cell. --}}
                        <button type="button" @click="navigator.clipboard.writeText(@js($log->description)); copied = true; setTimeout(() => copied = false, 1500)"
                            class="flex-shrink-0 text-gray-300 hover:text-indigo-600 transition" :title="copied ? 'Copied!' : 'Copy'">
                            <svg x-show="!copied" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            <svg x-show="copied" x-cloak class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </button>
                    </div>
                </td>
                <td class="px-5 py-3 text-xs text-gray-500 whitespace-nowrap">
                    {{ \App\Models\ActivityLog::channelFromReferrer($log->referrer) }}
                </td>
                <td class="px-5 py-3 text-xs text-gray-500">
                    @if($log->device)
                        {{ $log->device }}@if($log->browser) · {{ $log->browser }}@endif
                        @if($log->platform)<span class="block text-gray-400">{{ $log->platform }}</span>@endif
                    @else
                        <span class="text-gray-300">—</span>
                    @endif
                </td>
                <td class="px-5 py-3 text-xs text-gray-400 font-mono">
                    {{ $log->ip_address ?? '—' }}
                    {{-- City/country only — never a street address or anything close to it;
                         that precision isn't available from an IP to anyone outside the ISP
                         itself, not to this site or any other. --}}
                    @if($log->ip_address && ($loc = $ipLocations[$log->ip_address] ?? null))
                        <span class="block text-gray-300 font-sans">{{ $loc }}</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="px-5 py-16 text-center">
                    <svg class="w-12 h-12 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    <p class="text-gray-400">No activity found.</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-5 py-4 border-t border-gray-100">{{ $logs->links() }}</div>
</div>
@endsection
