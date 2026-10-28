@extends('layouts.admin')
@section('title', 'Live Visitors')

@section('content')
{{-- Page-local layout (kept here rather than in the Tailwind build, which only has classes other pages use) --}}
<style>
    .lv-big { font-size: 3.75rem; }
    .lv-stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .75rem; }
    .lv-bars { display: flex; align-items: flex-end; gap: 3px; height: 3.5rem; }
    .lv-bar { flex: 1 1 0%; border-radius: 3px 3px 0 0; transition: height .4s ease; }
    .lv-grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: 1.5rem; }
    .lv-main { min-width: 0; }
    .lv-who { display: none; width: 12rem; }
    .lv-xs { font-size: 11px; } .lv-xxs { font-size: 10px; }
    @media (min-width: 640px) { .lv-who { display: block; } .lv-who-m { display: none; } }
    @media (min-width: 1100px) { .lv-grid { grid-template-columns: minmax(0, 2fr) minmax(0, 1fr); } }
    @media (max-width: 639px) { .lv-big { font-size: 3rem; } .lv-stats { gap: .5rem; } }
</style>
{{-- The feed below is re-fetched every 5 s (LiveVisitorController::feed) and swapped in place —
     no reload. Polling pauses while this tab is hidden and stops when Turbo navigates away. --}}
<div x-data="{
        team: {{ $includeTeam ? 'true' : 'false' }},
        paused: false,
        updated: 0,
        failed: false,
        timer: null,
        url() { return '{{ route('admin.live-visitors.feed') }}' + (this.team ? '?team=1' : ''); },
        async refresh() {
            if (this.paused || document.hidden) return;
            try {
                const r = await fetch(this.url(), { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
                if (!r.ok) throw new Error(r.status);
                const data = await r.json();
                this.$refs.feed.innerHTML = data.html;
                this.updated = 0; this.failed = false;
                document.title = '(' + data.count + ') Live Visitors';
            } catch (e) { this.failed = true; }
        },
        toggleTeam() {
            this.team = !this.team;
            history.replaceState(null, '', '{{ route('admin.live-visitors.index') }}' + (this.team ? '?team=1' : ''));
            this.refresh();
        },
        init() {
            this.timer = setInterval(() => { this.updated++; if (this.updated % 5 === 0) this.refresh(); }, 1000);
            const stop = () => { clearInterval(this.timer); document.removeEventListener('turbo:before-visit', stop); };
            document.addEventListener('turbo:before-visit', stop);
        },
        destroy() { clearInterval(this.timer); }
     }">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-xl font-bold text-gray-800 dark:text-gray-100">Live Visitors</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Who's on the store right now, what they're looking at, and what's in their cart</p>
        </div>
        <div class="flex items-center gap-2 text-sm">
            <span class="text-xs tabular-nums" :style="failed ? 'color:#ef4444' : 'color:#94a3b8'"
                  x-text="failed ? 'Connection problem — retrying…' : (paused ? 'Paused' : 'Updated ' + updated + 's ago')"></span>
            <button type="button" @click="toggleTeam()"
                    class="rounded-full px-3 py-1.5 font-medium transition"
                    :style="team ? 'background:#6366f1;color:#fff' : 'background:#6366f11a;color:#6366f1'">
                <span x-text="team ? '✓ Including team' : 'Include team'"></span>
            </button>
            <button type="button" @click="paused = !paused; if (!paused) refresh()"
                    class="rounded-full px-3 py-1.5 font-medium transition"
                    :style="paused ? 'background:#f59e0b;color:#fff' : 'background:#f59e0b1a;color:#d97706'">
                <span x-text="paused ? '▶ Resume' : '⏸ Pause'"></span>
            </button>
        </div>
    </div>

    <div x-ref="feed">
        @include('admin.live-visitors._feed')
    </div>

    <p class="text-xs text-gray-400 mt-6">
        "Live" = the site is open in a visible tab within the last minute. Bots and crawlers are left out.
        Location is looked up from the IP and may be approximate.
    </p>
</div>
@endsection
