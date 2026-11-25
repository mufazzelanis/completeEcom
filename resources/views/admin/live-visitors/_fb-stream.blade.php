{{-- Admin → Live & Traffic → Meta events stream: every Conversions API event the server sent,
     filterable by event ID, event, main site / landing page, status and time. The filter bar is
     rendered once; only the results (_fb-results) are re-fetched — every 5 s while the chosen time
     reaches "now" — so typing in a filter is never interrupted. Filters live in the URL (fb_*),
     so a reload or a shared link opens the same view. --}}
@php
    $f = $fbx['f'];
    $hasFilter = $f['q'] !== '' || $f['event'] !== '' || $f['status'] !== '' || $f['source'] !== '' || $f['range'] !== 'today';
@endphp
<style>
    .fbx { --fb:#0866ff; background:#fff; border:1px solid #e8edf5; border-radius:1.25rem; overflow:hidden; box-shadow:0 1px 3px rgba(15,23,42,.06); }
    .fbx-hero { position:relative; padding:1.1rem 1.25rem; color:#fff; background:radial-gradient(120% 140% at 0% 0%, #0866ff 0%, #4f46e5 45%, #7c3aed 75%, #c026d3 100%); overflow:hidden; }
    .fbx-hero::after { content:""; position:absolute; inset:0; background-image:linear-gradient(rgba(255,255,255,.07) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.07) 1px, transparent 1px); background-size:22px 22px; mask-image:linear-gradient(90deg, transparent, #000 60%); pointer-events:none; }
    .fbx-logo { width:2.4rem; height:2.4rem; border-radius:.8rem; background:rgba(255,255,255,.16); display:flex; align-items:center; justify-content:center; box-shadow:inset 0 0 0 1px rgba(255,255,255,.25); }
    .fbx-live { display:inline-flex; align-items:center; gap:.4rem; font-size:.7rem; font-weight:800; letter-spacing:.08em; padding:.3rem .65rem; border-radius:999px; background:rgba(255,255,255,.16); box-shadow:inset 0 0 0 1px rgba(255,255,255,.25); cursor:pointer; }
    .fbx-dot { width:.5rem; height:.5rem; border-radius:999px; background:#4ade80; box-shadow:0 0 0 0 #4ade80aa; animation:fbxPulse 1.6s infinite; }
    .fbx-live.is-off .fbx-dot { background:#fbbf24; animation:none; }
    @keyframes fbxPulse { 0% { box-shadow:0 0 0 0 #4ade8099 } 70% { box-shadow:0 0 0 .5rem #4ade8000 } 100% { box-shadow:0 0 0 0 #4ade8000 } }

    .fbx-bar { display:flex; flex-wrap:wrap; gap:.6rem; align-items:center; padding:.9rem 1.25rem; border-bottom:1px solid #eef2f7; background:#fafbff; }
    .fbx-search { position:relative; flex:1 1 15rem; min-width:0; }
    .fbx-search svg { position:absolute; left:.7rem; top:50%; width:1rem; height:1rem; transform:translateY(-50%); color:#94a3b8; }
    .fbx-in { width:100%; border:1px solid #e2e8f0; border-radius:.75rem; padding:.5rem .7rem; font-size:.82rem; background:#fff; color:#0f172a; outline:none; transition:border-color .15s, box-shadow .15s; }
    .fbx-search .fbx-in { padding-left:2.1rem; font-family:ui-monospace, SFMono-Regular, Menlo, monospace; }
    .fbx-in:focus { border-color:var(--fb); box-shadow:0 0 0 3px #0866ff22; }
    .fbx-seg { display:inline-flex; padding:.2rem; gap:.15rem; border-radius:.8rem; background:#eef2f7; }
    .fbx-seg button { padding:.35rem .7rem; border-radius:.6rem; font-size:.75rem; font-weight:700; color:#475569; white-space:nowrap; }
    .fbx-seg button.is-on { background:#fff; color:#0f172a; box-shadow:0 1px 2px rgba(15,23,42,.12); }
    .fbx-seg button.is-on.ok { color:#16a34a } .fbx-seg button.is-on.bad { color:#dc2626 }
    .fbx-sel { width:auto; padding-right:1.8rem; font-weight:600; }
    .fbx-link { font-size:.78rem; font-weight:700; color:var(--fb); }
    .fbx-link:hover { text-decoration:underline; }

    .fbx-body { padding:1rem 1.25rem 1.1rem; position:relative; }
    .fbx-body.is-loading { opacity:.55; transition:opacity .2s; }
    .fbx-cards { display:grid; grid-template-columns:repeat(auto-fit, minmax(9.5rem, 1fr)); gap:.6rem; margin-bottom:1rem; }
    .fbx-card { text-align:left; border-radius:1rem; padding:.7rem .85rem; background:color-mix(in srgb, var(--c) 7%, #fff); box-shadow:inset 0 0 0 1px color-mix(in srgb, var(--c) 22%, transparent); transition:transform .15s, box-shadow .15s; position:relative; }
    .fbx-card:hover { transform:translateY(-1px); }
    .fbx-card.is-on { box-shadow:inset 0 0 0 2px var(--c), 0 6px 18px -8px var(--c); }
    .fbx-card.is-on::after { content:"✓"; position:absolute; top:.5rem; right:.6rem; width:1.1rem; height:1.1rem; border-radius:999px; background:var(--c); color:#fff; font-size:.65rem; font-weight:900; display:flex; align-items:center; justify-content:center; }
    .fbx-card-l { display:block; font-size:.72rem; font-weight:800; color:var(--c); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .fbx-card-n { display:block; font-size:1.5rem; font-weight:900; color:#0f172a; line-height:1.2; font-variant-numeric:tabular-nums; }
    .fbx-card-s { display:block; font-size:.7rem; font-weight:600; color:#64748b; }

    .fbx-table { border:1px solid #eef2f7; border-radius:1rem; overflow:hidden; }
    .fbx-row { display:grid; grid-template-columns:6.4rem 8.4rem minmax(0,1fr) 10.5rem 5rem 11.5rem 6.4rem; gap:.75rem; align-items:center; padding:.6rem .9rem; border-top:1px solid #f1f5f9; position:relative; font-size:.8rem; }
    .fbx-row::before { content:""; position:absolute; left:0; top:.55rem; bottom:.55rem; width:3px; border-radius:0 3px 3px 0; background:var(--c); }
    .fbx-row:hover { background:#f8fafc; }
    .fbx-head { border-top:0; background:#f8fafc; font-size:.66rem; font-weight:800; text-transform:uppercase; letter-spacing:.06em; color:#94a3b8; padding-top:.5rem; padding-bottom:.5rem; }
    .fbx-head::before { display:none; }
    .fbx-row.is-failed { background:#fff7f7; }
    .fbx-new { animation:fbxNew 2.4s ease-out; }
    @keyframes fbxNew { 0% { background:#dbeafe; box-shadow:inset 0 0 0 2px #60a5fa } 100% { background:transparent; box-shadow:none } }
    .fbx-r { text-align:right; }
    .fbx-time b { display:block; font-weight:700; color:#0f172a; font-variant-numeric:tabular-nums; }
    .fbx-time small, .fbx-what small { display:block; font-size:.68rem; color:#94a3b8; }
    .fbx-badge { display:inline-flex; align-items:center; gap:.25rem; padding:.2rem .55rem; border-radius:999px; font-size:.72rem; font-weight:800; color:var(--c); background:color-mix(in srgb, var(--c) 12%, transparent); white-space:nowrap; }
    .fbx-what { display:flex; align-items:center; gap:.6rem; min-width:0; }
    .fbx-thumb { width:2.3rem; height:2.3rem; flex-shrink:0; border-radius:.6rem; overflow:hidden; display:flex; align-items:center; justify-content:center; background:color-mix(in srgb, var(--c) 8%, #fff); box-shadow:inset 0 0 0 1px color-mix(in srgb, var(--c) 20%, transparent); }
    .fbx-thumb img { width:100%; height:100%; object-fit:contain; padding:2px; }
    .fbx-name { display:block; font-weight:600; color:#1e293b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    a.fbx-name:hover { color:var(--fb); text-decoration:underline; }
    .fbx-muted { color:#94a3b8; font-weight:400; }
    .fbx-order { display:inline-block; margin-left:.15rem; padding:0 .4rem; border-radius:.35rem; font-weight:700; color:#16a34a; background:#16a34a1a; }
    .fbx-tag { display:inline-block; max-width:100%; padding:.15rem .5rem; border-radius:.45rem; font-size:.7rem; font-weight:700; color:var(--t); background:color-mix(in srgb, var(--t) 10%, transparent); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .fbx-sub { display:flex !important; flex-wrap:wrap; align-items:center; gap:.3rem; margin-top:.15rem; }
    .fbx-sub .fbx-tag { font-size:.64rem; padding:.05rem .4rem; }
    .fbx-vis { min-width:0; font-size:.7rem; line-height:1.35; }
    .fbx-ip { font-family:ui-monospace, SFMono-Regular, Menlo, monospace; font-size:.72rem; font-weight:600; color:#64748b; }
    .fbx-ip:hover { color:var(--fb); text-decoration:underline; }
    .fbx-vis-tags { display:flex; flex-wrap:wrap; gap:.2rem; margin:.15rem 0; }
    .fbx-pill { display:inline-block; padding:0 .4rem; border-radius:999px; font-size:.64rem; font-weight:800; white-space:nowrap; }
    .fbx-net { display:block; font-weight:600; color:#64748b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .fbx-loc { display:block; color:#94a3b8; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .dark .fbx-ip, .dark .fbx-net { color:#94a3b8; } .dark .fbx-loc { color:#64748b; }
    .fbx-val { font-weight:800; color:#0f172a; font-variant-numeric:tabular-nums; }
    .fbx-id { display:flex; align-items:center; gap:.3rem; min-width:0; }
    .fbx-id code { flex:1; min-width:0; padding:.22rem .45rem; border-radius:.45rem; background:#f1f5f9; color:#334155; font-size:.7rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; cursor:copy; border:1px dashed transparent; }
    .fbx-id code:hover { border-color:#cbd5e1; }
    .fbx-copy { width:1.75rem; height:1.75rem; flex-shrink:0; border-radius:.5rem; display:flex; align-items:center; justify-content:center; color:#64748b; background:#fff; box-shadow:inset 0 0 0 1px #e2e8f0; transition:all .15s; }
    .fbx-copy svg { width:.85rem; height:.85rem; }
    .fbx-copy:hover { color:var(--fb); box-shadow:inset 0 0 0 1px var(--fb); }
    .fbx-copy.is-done { color:#fff; background:#16a34a; box-shadow:none; transform:scale(1.08); }
    .fbx-status { display:inline-flex; align-items:center; gap:.3rem; padding:.22rem .55rem; border-radius:999px; font-size:.72rem; font-weight:800; white-space:nowrap; }
    .fbx-status i { font-style:normal; font-weight:700; opacity:.7; font-family:ui-monospace, monospace; }
    .fbx-status.is-ok { color:#15803d; background:#dcfce7; }
    .fbx-status.is-bad { color:#b91c1c; background:#fee2e2; }
    .fbx-err { grid-column:1 / -1; margin:-.2rem 0 0; font-size:.72rem; color:#b91c1c; background:#fef2f2; border-radius:.5rem; padding:.35rem .6rem; }
    .fbx-empty { padding:2.2rem 1rem; text-align:center; color:#64748b; }
    .fbx-foot { display:flex; align-items:center; justify-content:space-between; gap:.5rem; margin-top:.75rem; font-size:.75rem; color:#94a3b8; }
    .fbx-more { padding:.45rem .9rem; border-radius:.7rem; font-weight:700; color:var(--fb); background:#0866ff12; }
    .fbx-more:hover { background:#0866ff22; }
    .fbx-toast { position:fixed; left:50%; bottom:1.5rem; transform:translateX(-50%); z-index:60; padding:.55rem 1rem; border-radius:.8rem; background:#0f172a; color:#fff; font-size:.8rem; font-weight:600; box-shadow:0 10px 30px -10px rgba(0,0,0,.5); }

    .dark .fbx { background:#0f172a; border-color:#1e293b; }
    .dark .fbx-bar { background:#0b1222; border-color:#1e293b; }
    .dark .fbx-in { background:#111827; border-color:#334155; color:#e2e8f0; }
    .dark .fbx-seg { background:#1e293b; }
    .dark .fbx-seg button { color:#94a3b8; }
    .dark .fbx-seg button.is-on { background:#334155; color:#f8fafc; }
    .dark .fbx-card { background:color-mix(in srgb, var(--c) 12%, #0f172a); }
    .dark .fbx-card-n, .dark .fbx-time b, .dark .fbx-val { color:#f8fafc; }
    .dark .fbx-table { border-color:#1e293b; }
    .dark .fbx-row { border-color:#1e293b; }
    .dark .fbx-row:hover, .dark .fbx-head { background:#111c33; }
    .dark .fbx-row.is-failed { background:#2a1215; }
    .dark .fbx-name { color:#e2e8f0; }
    .dark .fbx-thumb { background:color-mix(in srgb, var(--c) 14%, #0f172a); }
    .dark .fbx-id code { background:#1e293b; color:#cbd5e1; }
    .dark .fbx-copy { background:#111827; box-shadow:inset 0 0 0 1px #334155; color:#94a3b8; }
    .dark .fbx-status.is-ok { background:#14532d66; color:#4ade80; }
    .dark .fbx-status.is-bad { background:#7f1d1d66; color:#f87171; }
    .dark .fbx-err { background:#450a0a99; color:#fca5a5; }
    /* customer match: per-row signal + details, and the summary strip */
    .fbx-stat { display:flex; flex-direction:column; align-items:flex-start; gap:.3rem; }
    .fbx-match { display:inline-flex; align-items:center; gap:.3rem; padding:.15rem .4rem .15rem .35rem; border-radius:.5rem; font-size:.68rem; color:var(--m); background:color-mix(in srgb, var(--m) 10%, transparent); }
    .fbx-match:hover { background:color-mix(in srgb, var(--m) 18%, transparent); }
    .fbx-match svg { width:.85rem; height:.85rem; transition:transform .2s; color:#94a3b8; }
    .fbx-match svg.rot { transform:rotate(180deg); }
    .fbx-sig { display:inline-flex; align-items:flex-end; gap:1.5px; height:13px; }
    .fbx-sig i { width:3px; border-radius:1px; background:color-mix(in srgb, var(--m) 25%, #e2e8f0); }
    .fbx-sig i.on { background:var(--m); }
    .fbx-ring { --s:3.5rem; width:var(--s); height:var(--s); flex-shrink:0; border-radius:999px; display:flex; align-items:baseline; justify-content:center; padding-top:1rem; position:relative; letter-spacing:-.02em;
        background:radial-gradient(closest-side, #fff 76%, transparent 78% 100%), conic-gradient(var(--m) calc(var(--p) * 1%), #e2e8f0 0); }
    .fbx-ring b { font-size:.9rem; font-weight:900; color:#0f172a; line-height:1; } .fbx-ring small { font-size:.55rem; color:#94a3b8; font-weight:700; }
    .fbx-detail { grid-column:1 / -1; margin:.15rem 0 .2rem; padding:.9rem 1rem; border-radius:.9rem; background:#f8fafc; box-shadow:inset 0 0 0 1px #e2e8f0; }
    .fbx-d-head { display:flex; gap:.8rem; align-items:center; }
    .fbx-d-title { font-weight:800; font-size:.85rem; color:#0f172a; }
    .fbx-d-text { font-size:.76rem; color:#475569; margin-top:.15rem; }
    .fbx-keys { display:flex; flex-wrap:wrap; gap:.35rem; margin:.8rem 0 .5rem; }
    .fbx-key { display:inline-flex; align-items:center; gap:.3rem; padding:.25rem .55rem .25rem .3rem; border-radius:999px; font-size:.72rem; font-weight:600; color:#94a3b8; background:#fff; box-shadow:inset 0 0 0 1px #e2e8f0; cursor:help; }
    .fbx-key-i { width:1rem; height:1rem; border-radius:999px; display:inline-flex; align-items:center; justify-content:center; font-size:.6rem; font-weight:900; background:#f1f5f9; color:#94a3b8; }
    .fbx-key.on { color:#166534; background:#f0fdf4; box-shadow:inset 0 0 0 1px #bbf7d0; }
    .fbx-key.on .fbx-key-i { background:#16a34a; color:#fff; }
    .fbx-d-note { font-size:.68rem; color:#94a3b8; }
    .fbx-d-tech { display:grid; grid-template-columns:repeat(auto-fit, minmax(14rem, 1fr)); gap:.35rem 1rem; margin-top:.7rem; padding-top:.7rem; border-top:1px dashed #e2e8f0; font-size:.7rem; }
    .fbx-d-tech dt { color:#94a3b8; font-weight:700; text-transform:uppercase; letter-spacing:.04em; font-size:.6rem; }
    .fbx-d-tech dd { color:#334155; word-break:break-all; } .fbx-d-tech code { font-size:.68rem; }
    .fbx-mq { display:grid; grid-template-columns:minmax(0, 1.1fr) minmax(0, 1.4fr); gap:1rem 1.5rem; align-items:center; padding:.85rem 1rem; margin-bottom:1rem; border-radius:1rem; background:linear-gradient(135deg, #f8fafc, #eef2ff); box-shadow:inset 0 0 0 1px #e0e7ff; }
    .fbx-mq-score { display:flex; gap:.8rem; align-items:center; }
    .fbx-mq-t { font-weight:800; font-size:.88rem; color:#0f172a; } .fbx-mq-s { font-size:.72rem; color:#64748b; margin-top:.1rem; }
    .fbx-mq-bars { display:grid; gap:.35rem; }
    .fbx-mq-bar { display:grid; grid-template-columns:7.5rem minmax(0,1fr) 2.6rem; gap:.5rem; align-items:center; font-size:.72rem; cursor:help; }
    .fbx-mq-l { color:#475569; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .fbx-mq-track { height:.45rem; border-radius:999px; background:#e2e8f0; overflow:hidden; } .fbx-mq-track span { display:block; height:100%; border-radius:999px; transition:width .5s; }
    .fbx-mq-bar b { text-align:right; color:#0f172a; font-variant-numeric:tabular-nums; }
    .dark .fbx-detail { background:#0b1222; box-shadow:inset 0 0 0 1px #1e293b; }
    .dark .fbx-d-title, .dark .fbx-mq-t, .dark .fbx-ring b, .dark .fbx-mq-bar b { color:#f8fafc; }
    .dark .fbx-d-text, .dark .fbx-mq-l { color:#94a3b8; } .dark .fbx-d-tech dd { color:#cbd5e1; } .dark .fbx-d-tech { border-color:#1e293b; }
    .dark .fbx-key { background:#111827; box-shadow:inset 0 0 0 1px #1e293b; color:#64748b; } .dark .fbx-key-i { background:#1e293b; }
    .dark .fbx-key.on { background:#052e1666; color:#86efac; box-shadow:inset 0 0 0 1px #14532d; }
    .dark .fbx-ring { background:radial-gradient(closest-side, #0f172a 76%, transparent 78% 100%), conic-gradient(var(--m) calc(var(--p) * 1%), #1e293b 0); }
    .dark .fbx-mq { background:linear-gradient(135deg, #0b1222, #111c3a); box-shadow:inset 0 0 0 1px #1e293b; } .dark .fbx-mq-track { background:#1e293b; }
    @media (max-width: 1023px) { .fbx-mq { grid-template-columns:minmax(0,1fr); } .fbx-stat { flex-direction:row; align-items:center; } .fbx-mq-bar { grid-template-columns:6.5rem minmax(0,1fr) 2.4rem; } }

    /* phones / narrow: each event becomes a small card */
    @media (max-width: 1023px) {
        .fbx-head { display:none; }
        .fbx-row { grid-template-columns:minmax(0,1fr) auto; grid-template-areas:"what what" "ev status" "vis vis" "id id" "time val"; row-gap:.45rem; padding:.8rem .9rem; }
        .fbx-row > :nth-child(1) { grid-area:time; } .fbx-row > :nth-child(2) { grid-area:ev; } .fbx-row > :nth-child(3) { grid-area:what; }
        .fbx-row > :nth-child(4) { grid-area:vis; } .fbx-row > :nth-child(5) { grid-area:val; } .fbx-row > :nth-child(6) { grid-area:id; } .fbx-row > :nth-child(7) { grid-area:status; justify-self:end; }
        .fbx-time { display:flex; gap:.4rem; align-items:baseline; } .fbx-time small { display:inline; }
        .fbx-bar { padding:.8rem 1rem; } .fbx-body { padding:.9rem 1rem 1rem; } .fbx-hero { padding:1rem; }
        .fbx-seg { overflow-x:auto; max-width:100%; }
    }
</style>

<section class="fbx mb-8" id="meta-events"
    x-data="{
        q: @js($f['q']), event: @js($f['event']), status: @js($f['status']), source: @js($f['source']),
        range: @js($f['range']), from: @js($f['from'] ?? ''), to: @js($f['to'] ?? ''), limit: {{ $f['limit'] }},
        top: {{ $fbx['topId'] }}, total: {{ $fbx['total'] }}, live: true, loading: false, copied: null, toast: '', opened: {},
        canLive: @js($fbx['isLive']), timer: null, _q: null, _c: null, _req: 0,
        params(extra = {}) {
            const p = { fb_q: this.q.trim(), fb_event: this.event, fb_status: this.status, fb_source: this.source, fb_range: this.range, fb_limit: this.limit };
            if (this.range === 'custom') { p.fb_from = this.from; p.fb_to = this.to; }
            const u = new URLSearchParams();
            Object.entries({ ...p, ...extra }).forEach(([k, v]) => { if (v !== '' && v !== null && v !== undefined && !(k === 'fb_range' && v === 'today') && !(k === 'fb_limit' && v == 5)) u.set(k, v); });
            return u;
        },
        syncUrl() {
            const u = new URL(location.href);
            [...u.searchParams.keys()].filter(k => k.startsWith('fb_')).forEach(k => u.searchParams.delete(k));
            this.params().forEach((v, k) => u.searchParams.set(k, v));
            history.replaceState(history.state, '', u.toString());
        },
        async load(poll = false) {
            const id = ++this._req;
            if (!poll) this.loading = true;
            try {
                const r = await fetch('{{ route('admin.live-visitors.fb-events') }}?' + this.params(poll ? { fb_after: this.top } : {}), { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                if (!r.ok) throw new Error(r.status);
                const d = await r.json();
                if (id !== this._req) return; // a newer filter change already went out
                this.$refs.results.innerHTML = d.html;
                this.top = Math.max(poll ? this.top : 0, d.top); this.total = d.total;
            } catch (e) { /* keep showing the last results */ }
            if (id === this._req) this.loading = false;
        },
        apply() { this.limit = 5; this.canLive = ['live', 'today', '7d', '30d'].includes(this.range) || (this.range === 'custom' && !this.to); this.syncUrl(); this.load(); },
        search() { clearTimeout(this._q); this._q = setTimeout(() => this.apply(), 350); },
        more() { this.limit = Math.min({{ \App\Http\Controllers\Admin\LiveFacebookEventsController::MAX }}, this.limit + 10); this.syncUrl(); this.load(); },
        less() { this.limit = 5; this.syncUrl(); this.load(); document.getElementById('meta-events').scrollIntoView({ behavior: 'smooth', block: 'start' }); },
        setEvent(e) { this.event = e; this.apply(); },
        setRange(r) { this.range = r; if (r !== 'custom') this.apply(); },
        toggle(id) { this.opened = { ...this.opened, [id]: !this.opened[id] }; },
        reset() { Object.assign(this, { q: '', event: '', status: '', source: '', range: 'today', from: '', to: '' }); this.apply(); },
        copy(text) {
            const done = () => { this.copied = text; this.toast = 'Event ID copied — ' + text; clearTimeout(this._c); this._c = setTimeout(() => { this.copied = null; this.toast = ''; }, 1800); };
            const fallback = () => { const t = document.createElement('textarea'); t.value = text; t.setAttribute('readonly', ''); t.style.cssText = 'position:fixed;opacity:0'; document.body.appendChild(t); t.select(); try { document.execCommand('copy'); done(); } catch (e) {} t.remove(); };
            navigator.clipboard && window.isSecureContext ? navigator.clipboard.writeText(text).then(done, fallback) : fallback();
        },
        init() {
            this.timer = setInterval(() => { if (this.live && this.canLive && !document.hidden && !this.loading) this.load(true); }, 5000);
            const stop = () => { clearInterval(this.timer); document.removeEventListener('turbo:before-visit', stop); };
            document.addEventListener('turbo:before-visit', stop);
        },
        destroy() { clearInterval(this.timer); }
    }">

    {{-- ── header ── --}}
    <div class="fbx-hero">
        <div class="relative flex flex-wrap items-center justify-between gap-3" style="z-index:1">
            <div class="flex items-center gap-3 min-w-0">
                <span class="fbx-logo">
                    <svg viewBox="0 0 36 36" width="22" height="22" aria-hidden="true"><path fill="#fff" d="M20.2 35.8v-12.6h4.2l.7-5h-4.9v-3.2c0-1.4.4-2.4 2.5-2.4h2.6V8.2c-.5-.1-2-.2-3.8-.2-3.7 0-6.3 2.3-6.3 6.5v3.7h-4.2v5h4.2v12.6z"/></svg>
                </span>
                <div class="min-w-0">
                    <h2 class="font-extrabold text-lg leading-tight">Meta events stream</h2>
                    <p class="text-xs" style="color:rgba(255,255,255,.8)">Every event the server sent to Meta (Conversions API) · click an ID to copy it</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" class="fbx-live" :class="!(live && canLive) && 'is-off'" @click="live = !live; if (live) load(true)"
                        :title="canLive ? 'New events appear by themselves every 5 seconds' : 'This time range has ended — nothing new will arrive'">
                    <span class="fbx-dot"></span><span x-text="!canLive ? 'HISTORY' : (live ? 'LIVE' : 'PAUSED')"></span>
                </button>
                <a href="{{ route('admin.facebook-conversion-logs.index') }}" class="fbx-live" style="letter-spacing:0">All logs →</a>
            </div>
        </div>
    </div>

    @if(!$fbx['enabled'] && $fbx['total'] === 0 && !$hasFilter)
        <div class="fbx-body">
            <p class="text-sm text-gray-500 dark:text-gray-400">Conversions API is off — turn it on in
                <a href="{{ route('admin.settings.show', 'facebook_pixel') }}" class="fbx-link">Settings → Facebook Pixel</a> and events will stream in here.</p>
        </div>
    @else
        {{-- ── filters ── --}}
        <div class="fbx-bar">
            <label class="fbx-search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input type="search" class="fbx-in" x-model="q" @input="search()" placeholder="Search event ID or IP… e.g. purchase_135" aria-label="Search by event ID or IP" autocomplete="off" spellcheck="false">
            </label>

            <select class="fbx-in fbx-sel" x-model="source" @change="apply()" aria-label="Main site or landing page">
                <option value="">All sources</option>
                <option value="main">🏬 Main site</option>
                @foreach($fbx['landings'] as $lp)
                    <option value="lp:{{ $lp->id }}">🚀 {{ \Illuminate\Support\Str::limit($lp->title, 40) }}</option>
                @endforeach
            </select>

            <div class="fbx-seg" role="group" aria-label="Status">
                <button type="button" :class="status === '' && 'is-on'" @click="status = ''; apply()">All</button>
                <button type="button" class="ok" :class="status === 'sent' && 'is-on'" @click="status = 'sent'; apply()">✓ Sent</button>
                <button type="button" class="bad" :class="status === 'failed' && 'is-on'" @click="status = 'failed'; apply()">✕ Failed</button>
            </div>

            <div class="fbx-seg" role="group" aria-label="Time">
                @foreach(\App\Http\Controllers\Admin\LiveFacebookEventsController::RANGES as $key => $label)
                    <button type="button" :class="range === @js($key) && 'is-on'" @click="setRange(@js($key))">{{ $label }}</button>
                @endforeach
            </div>

            <div x-show="range === 'custom'" x-cloak class="flex flex-wrap items-center gap-1.5">
                <input type="datetime-local" class="fbx-in" style="width:auto" x-model="from" aria-label="From">
                <span class="text-xs text-gray-400">→</span>
                <input type="datetime-local" class="fbx-in" style="width:auto" x-model="to" aria-label="To (empty = now)">
                <button type="button" class="fbx-more" @click="apply()">Apply</button>
            </div>

            <button type="button" class="fbx-link ml-auto" x-show="q || event || status || source || range !== 'today'" @click="reset()" @if(!$hasFilter) x-cloak @endif>✕ Clear filters</button>
        </div>

        {{-- ── results (refreshed) ── --}}
        <div class="fbx-body" :class="loading && 'is-loading'">
            <div x-ref="results">
                @include('admin.live-visitors._fb-results', $fbx)
            </div>
        </div>
    @endif

    <div class="fbx-toast" x-show="toast" x-transition x-cloak x-text="toast" role="status"></div>
</section>
