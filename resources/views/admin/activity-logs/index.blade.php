@extends('layouts.admin')
@section('title', 'Activity Log')

@section('content')
<div class="mb-6">
    <h1 class="text-xl font-bold text-gray-800">Activity Log</h1>
    <p class="text-sm text-gray-500 mt-0.5">Who logged in, viewed, and did what — across customers and admins</p>
</div>

{{-- Filters — applied as soon as a choice changes (search: shortly after typing stops), without
     reloading the page: only the results block below is fetched and swapped in, the URL is
     updated (so Back/Forward and copied links work), and these controls keep their values. --}}
<div class="bg-white rounded-2xl shadow-sm p-4 mb-6"
     x-data="{
        period: '{{ request('period') ?: (request('from') || request('to') ? 'custom' : '') }}',
        loading: false,
        timer: null,
        controller: null,
        url() {
            const params = new URLSearchParams();
            this.$refs.form.querySelectorAll('[name]').forEach(i => { if (i.value) params.set(i.name, i.value); });
            if (this.period !== 'custom') { params.delete('from'); params.delete('to'); }
            if (this.rolling()) { params.delete('t_from'); params.delete('t_to'); }
            const qs = params.toString();
            return '{{ route('admin.activity-logs.index') }}' + (qs ? '?' + qs : '');
        },
        apply() {
            if (this.period === 'custom' && !this.$refs.from.value && !this.$refs.to.value) return; // wait for a date
            this.load(this.url(), true);
        },
        typed() { clearTimeout(this.timer); this.timer = setTimeout(() => this.apply(), 500); },
        times: { from: '{{ request('t_from') }}', to: '{{ request('t_to') }}' },
        rolling() { return ['last_15m', 'last_1h', 'last_6h', 'last_24h'].includes(this.period); },
        setTime(from, to) { this.$refs.tFrom.value = from; this.$refs.tTo.value = to; this.times = { from, to }; this.apply(); },
        timeIs(from, to) { return this.times.from === from && this.times.to === to; },
        hasTime() { return !!(this.times.from || this.times.to); },
        async load(url, push) {
            this.controller?.abort(); // a newer choice replaces a request still in flight
            this.controller = new AbortController();
            this.loading = true;
            const results = document.getElementById('activity-log-results');
            results.classList.add('opacity-50');
            try {
                const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }, signal: this.controller.signal });
                if (!res.ok) throw new Error(res.status);
                results.innerHTML = await res.text();
                if (push) history.pushState({ activityLog: true }, '', url);
                this.syncForm(url);
            } catch (e) {
                if (e.name !== 'AbortError') window.location = url; // fall back to a normal page load
            } finally {
                results.classList.remove('opacity-50');
                this.loading = false;
            }
        },
        // after a chip/card/page link or Back/Forward, show that URL's filters in the controls
        syncForm(url) {
            const params = new URL(url, location.origin).searchParams;
            this.$refs.form.querySelectorAll('[name]').forEach(i => { if (document.activeElement !== i) i.value = params.get(i.name) ?? ''; });
            this.period = params.get('period') || (params.get('from') || params.get('to') ? 'custom' : '');
            this.times = { from: params.get('t_from') || '', to: params.get('t_to') || '' };
        },
        init() {
            // links inside the results (chips, cards, pagination) load in place too
            document.getElementById('activity-log-results').addEventListener('click', (e) => {
                const a = e.target.closest('a[data-al-link], nav[role=navigation] a[href]');
                if (!a || e.ctrlKey || e.metaKey || e.shiftKey || e.button !== 0) return;
                e.preventDefault();
                this.load(a.href, true);
                if (a.closest('nav')) document.getElementById('activity-log-results').scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
            window.addEventListener('popstate', () => { if (location.pathname.endsWith('/activity-logs')) this.load(location.href, false); });
        },
     }">
    <form x-ref="form" action="{{ route('admin.activity-logs.index') }}" method="GET" class="flex flex-wrap items-end gap-3"
          @change="if ($event.target.name !== 'search') apply()" @submit.prevent="apply()">
        <div class="flex-1 min-w-40">
            <label class="text-xs font-medium text-gray-500 uppercase mb-1 block">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Product, order no., IP…" @input="typed()" autocomplete="off"
                class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div>
            <label class="text-xs font-medium text-gray-500 uppercase mb-1 block">Event</label>
            <select name="event_type" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">All Events</option>
                @foreach($eventTypes as $e)
                <option value="{{ $e }}" @selected(request('event_type') === $e)>{{ $e }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-medium text-gray-500 uppercase mb-1 block">Role</label>
            <select name="role" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">All Roles</option>
                <option value="admin" @selected(request('role') === 'admin')>Admin</option>
                <option value="customer" @selected(request('role') === 'customer')>Customer</option>
                <option value="guest" @selected(request('role') === 'guest')>Guest (not logged in)</option>
            </select>
        </div>
        <div>
            <label class="text-xs font-medium text-gray-500 uppercase mb-1 block">User</label>
            <select name="user_id" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 max-w-[12rem]">
                <option value="">All Users</option>
                @foreach($logUsers as $u)
                <option value="{{ $u->id }}" @selected(request('user_id') == $u->id)>{{ $u->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-medium text-gray-500 uppercase mb-1 block">Traffic</label>
            <select name="traffic" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">All</option>
                <option value="humans" @selected(request('traffic') === 'humans')>Real users only</option>
                <option value="bots" @selected(request('traffic') === 'bots')>Bots/crawlers only</option>
            </select>
        </div>
        <div>
            <label class="text-xs font-medium text-gray-500 uppercase mb-1 block">Type</label>
            <select name="source" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">Everything</option>
                <option value="activity" @selected(request('source') === 'activity')>Site activity</option>
                <option value="audit" @selected(request('source') === 'audit')>Admin actions</option>
            </select>
        </div>
        <div>
            <label class="text-xs font-medium text-gray-500 uppercase mb-1 block">Period</label>
            <select name="period" x-model="period" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">Lifetime</option>
                <optgroup label="Recent">
                    <option value="last_15m">Last 15 minutes</option>
                    <option value="last_1h">Last 1 hour</option>
                    <option value="last_6h">Last 6 hours</option>
                    <option value="last_24h">Last 24 hours</option>
                </optgroup>
                <optgroup label="Days">
                <option value="today">Today</option>
                <option value="yesterday">Yesterday</option>
                <option value="last_7">Last 7 days</option>
                <option value="this_week">This Week</option>
                <option value="this_month">This Month</option>
                <option value="last_month">Last Month</option>
                <option value="this_year">This Year</option>
                <option value="custom">Custom Range</option>
                </optgroup>
            </select>
        </div>
        <div x-show="period === 'custom'" x-cloak>
            <label class="text-xs font-medium text-gray-500 uppercase mb-1 block">From</label>
            <input type="date" name="from" x-ref="from" value="{{ request('from') }}"
                class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div x-show="period === 'custom'" x-cloak>
            <label class="text-xs font-medium text-gray-500 uppercase mb-1 block">To</label>
            <input type="date" name="to" x-ref="to" value="{{ request('to') }}"
                class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        {{-- Time of day (Dhaka time) on every day the Period covers — hidden for the rolling "last N" periods, where it means nothing --}}
        <div x-show="!rolling()">
            <label class="text-xs font-medium text-gray-500 uppercase mb-1 block">Time <span class="normal-case font-normal text-gray-400">(any day in period)</span></label>
            <div class="flex items-center gap-1.5">
                <input type="time" name="t_from" x-ref="tFrom" value="{{ request('t_from') }}" title="From this time"
                    class="border border-gray-200 rounded-xl px-2.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <span class="text-gray-400 text-sm">–</span>
                <input type="time" name="t_to" x-ref="tTo" value="{{ request('t_to') }}" title="Until this time"
                    class="border border-gray-200 rounded-xl px-2.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div class="flex flex-wrap gap-1 mt-1.5">
                @foreach([['🌅 Morning', '06:00', '11:59'], ['☀️ Afternoon', '12:00', '16:59'], ['🌇 Evening', '17:00', '20:59'], ['🌙 Night', '21:00', '05:59']] as [$name, $tf, $tt])
                    <button type="button" @click="setTime('{{ $tf }}', '{{ $tt }}')"
                        class="rounded-full px-2 py-0.5 text-xs transition"
                        :class="timeIs('{{ $tf }}', '{{ $tt }}') ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">{{ $name }}</button>
                @endforeach
                <button type="button" x-show="hasTime()" x-cloak @click="setTime('', '')" class="rounded-full px-2 py-0.5 text-xs text-red-500 hover:bg-red-50">✕ Any time</button>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm hover:bg-indigo-700 transition inline-flex items-center gap-1.5">
                <svg x-show="loading" x-cloak class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                <span x-text="loading ? 'Loading…' : 'Filter'">Filter</span>
            </button>
            <button type="button" @click="$refs.form.reset(); $refs.form.querySelectorAll('[name]').forEach(i => i.value = ''); period = ''; load('{{ route('admin.activity-logs.index') }}', true)"
                class="bg-gray-100 text-gray-600 px-4 py-2 rounded-xl text-sm hover:bg-gray-200 transition">Clear</button>
        </div>
    </form>
</div>

<div id="activity-log-results" class="transition-opacity">
    @include('admin.activity-logs._results')
</div>
@endsection
