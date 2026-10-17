{{-- The part of Admin → Activity Log that changes with the filters — count, cards, active
     filters and the table. Rendered on page load and, on every filter change, fetched on its own
     and swapped in without reloading the page (ActivityLogController returns just this for AJAX). --}}
@php
    // card link: filter by this event (keeping every other filter), or clear it if it's the active one
    $eventUrl = fn (string $event) => request()->fullUrlWithQuery(['event_type' => request('event_type') === $event ? null : $event, 'page' => null]);
@endphp
<div class="flex flex-wrap items-center justify-between gap-2 mb-4">
    <p class="text-sm text-gray-500">
        @if($logs->total())
            Showing <strong class="text-gray-800">{{ number_format($logs->firstItem()) }}–{{ number_format($logs->lastItem()) }}</strong> of
        @endif
        <strong class="text-gray-800">{{ number_format($logs->total()) }}</strong> {{ $activeFilters ? 'matching' : 'total' }} entries
    </p>
    @if($activeFilters)
        <div class="flex flex-wrap items-center gap-2">
            @foreach($activeFilters as $chip)
                <a href="{{ $chip['remove'] }}" data-al-link class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-indigo-50 text-indigo-700 text-xs font-medium hover:bg-indigo-100" title="Remove this filter">
                    {{ $chip['label'] }} <span aria-hidden="true">×</span>
                </a>
            @endforeach
            <a href="{{ route('admin.activity-logs.index') }}" data-al-link class="text-xs text-gray-500 hover:text-gray-800 underline">Clear all</a>
        </div>
    @endif
</div>

{{-- Counts for the selected period and filters (Traffic, Role, User, Search) — click a card to
     show only that event, click it again to show everything --}}
<div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
    @foreach([
        ['login', 'Logins'],
        ['product.view', 'Product Views'],
        ['cart.add', 'Cart Adds'],
        ['order.place', 'Orders Placed'],
        ['order.view', 'Order Views'],
    ] as [$event, $label])
        @php $active = request('event_type') === $event; $isOrder = $event === 'order.place'; @endphp
        <a href="{{ $eventUrl($event) }}" data-al-link title="{{ $active ? 'Show all events again' : 'Show only ' . strtolower($label) }}"
           class="block bg-white rounded-2xl shadow-sm p-4 transition hover:shadow-md hover:-translate-y-0.5 {{ $active ? 'ring-2 ring-indigo-500' : ($isOrder ? 'ring-1 ring-emerald-100' : '') }}">
            <p class="text-xs uppercase tracking-wider font-medium {{ $isOrder ? 'text-emerald-600' : 'text-gray-400' }}">{{ $label }}</p>
            <p class="text-[11px] text-gray-400">{{ $periodLabel }}</p>
            <p class="text-2xl font-bold mt-1 {{ $isOrder ? 'text-emerald-700' : 'text-gray-800' }}">{{ number_format($stats[$event]['count']) }}</p>
            @if($stats[$event]['bots'] > 0 && request('traffic') !== 'bots')
                {{-- so crawler traffic doesn't read as real people --}}
                <p class="text-[11px] text-gray-400 mt-1">incl. <span class="font-semibold text-orange-500">{{ number_format($stats[$event]['bots']) }}</span> bot/crawler</p>
            @endif
        </a>
    @endforeach
</div>

{{-- Log table --}}
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
                    <p class="text-gray-400">No activity matches these filters.</p>
                    @if($activeFilters)<a href="{{ route('admin.activity-logs.index') }}" data-al-link class="inline-block mt-2 text-sm text-indigo-600 hover:underline">Clear all filters</a>@endif
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-5 py-4 border-t border-gray-100">{{ $logs->links() }}</div>
</div>
