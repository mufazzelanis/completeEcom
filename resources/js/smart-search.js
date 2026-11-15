// Storefront smart search (partials/storefront/smart-search.blade.php) — one component for the
// desktop header bar, the mobile menu and the mobile sticky bar.
//
//  - Placeholder types out, one after another, what shoppers search for most and the products
//    they open most (GET /search/trending — cached on the server and for 10 min in sessionStorage,
//    fetched when the browser is idle, so it never competes with the page load).
//  - Focus with nothing typed: recent searches (this browser only), trending searches, popular products.
//  - Typing: /search/suggest results with the typed words highlighted, discounts, and keyboard
//    navigation (↑ ↓ Enter Esc); "/" focuses the desktop bar from anywhere.
//  - Results are cached per query and stale requests aborted, so fast typing never flickers.

const TRENDING_KEY = 'ss:trending:v1';
const RECENT_KEY = 'ss:recent';
let trendingPromise = null;
const suggestCache = new Map();

function loadTrending(url) {
    if (trendingPromise) return trendingPromise;
    try {
        const cached = JSON.parse(sessionStorage.getItem(TRENDING_KEY) || 'null');
        if (cached && Date.now() - cached.t < 600000) return (trendingPromise = Promise.resolve(cached.d));
    } catch (e) { /* storage blocked */ }
    trendingPromise = fetch(url, { headers: { Accept: 'application/json' } })
        .then((r) => (r.ok ? r.json() : {}))
        .then((d) => { try { sessionStorage.setItem(TRENDING_KEY, JSON.stringify({ t: Date.now(), d })); } catch (e) {} return d; })
        .catch(() => ({}));
    return trendingPromise;
}

const readRecent = () => { try { return JSON.parse(localStorage.getItem(RECENT_KEY) || '[]').slice(0, 6); } catch (e) { return []; } };
const writeRecent = (list) => { try { localStorage.setItem(RECENT_KEY, JSON.stringify(list.slice(0, 6))); } catch (e) {} };
const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const go = (url) => (window.Turbo ? window.Turbo.visit(url) : (window.location.href = url));

export default function smartSearch(opts = {}) {
    return {
        query: opts.query || '',
        open: false,
        loading: false,
        focused: false,
        active: -1,
        results: { products: [], categories: [] },
        trending: { searches: [], products: [] },
        recent: [],
        placeholder: opts.placeholder || 'Search products…',
        _t: null,
        _type: null,
        _ctl: null,

        init() {
            this.recent = readRecent();
            const start = () => loadTrending(opts.trendingUrl).then((d) => {
                this.trending = { searches: d.searches || [], products: d.products || [] };
                if (opts.animate !== false) this.typeLoop(d.placeholders || []);
            });
            'requestIdleCallback' in window ? requestIdleCallback(start, { timeout: 3000 }) : setTimeout(start, 1500);

            if (opts.hotkey) {
                this._key = (e) => {
                    if (e.key !== '/' || e.ctrlKey || e.metaKey || e.altKey) return;
                    const t = e.target;
                    if (t && (t.isContentEditable || /^(INPUT|TEXTAREA|SELECT)$/.test(t.tagName))) return;
                    if (!this.$refs.input || !this.$refs.input.offsetParent) return; // not visible (phone layout)
                    e.preventDefault();
                    this.$refs.input.focus();
                };
                window.addEventListener('keydown', this._key);
            }
        },

        destroy() {
            clearTimeout(this._t);
            clearTimeout(this._type);
            if (this._key) window.removeEventListener('keydown', this._key);
            if (this._ctl) this._ctl.abort();
        },

        // "Search “CeraVe Moisturizing…”" typed out letter by letter; pauses while the box is in use
        typeLoop(words) {
            if (!words.length) return;
            const base = opts.placeholder || 'Search products…';
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                let n = 0;
                const rotate = () => { if (!this.focused && !this.query) this.placeholder = `Search “${words[n++ % words.length]}”`; this._type = setTimeout(rotate, 3500); };
                return rotate();
            }
            let i = 0, j = 0, back = false;
            const step = () => {
                if (this.focused || this.query) { this.placeholder = base; this._type = setTimeout(step, 1200); return; }
                const w = words[i % words.length];
                this.placeholder = `Search “${w.slice(0, j)}${back || j < w.length ? '' : '”'}`;
                let wait = back ? 25 : 65;
                if (!back && j < w.length) j++;
                else if (!back) { back = true; wait = 1800; }
                else if (j > 0) j--;
                else { back = false; i++; wait = 350; }
                this._type = setTimeout(step, wait);
            };
            this._type = setTimeout(step, 600);
        },

        onFocus() {
            this.focused = true;
            this.recent = readRecent();
            this.active = -1;
            this.open = true;
            if (this.query.trim().length >= 2) this.fetch();
        },
        onBlur() { this.focused = false; },
        close() { this.open = false; this.active = -1; },

        onInput() {
            this.active = -1;
            this.open = true;
            clearTimeout(this._t);
            if (this.query.trim().length < 2) { this.results = { products: [], categories: [] }; this.loading = false; return; }
            this.loading = true;
            this._t = setTimeout(() => this.fetch(), 160);
        },

        fetch() {
            const q = this.query.trim();
            if (suggestCache.has(q)) { this.results = suggestCache.get(q); this.loading = false; return; }
            if (this._ctl) this._ctl.abort();
            this._ctl = new AbortController();
            this.loading = true;
            fetch(`${opts.suggestUrl}?q=${encodeURIComponent(q)}`, { headers: { Accept: 'application/json' }, signal: this._ctl.signal })
                .then((r) => r.json())
                .then((d) => {
                    const res = { products: d.products || [], categories: d.categories || [] };
                    suggestCache.set(q, res);
                    if (this.query.trim() === q) { this.results = res; this.loading = false; }
                })
                .catch((e) => { if (e.name !== 'AbortError') this.loading = false; });
        },

        get typing() { return this.query.trim().length >= 2; },
        get empty() { return this.typing && !this.loading && !this.results.products.length && !this.results.categories.length; },
        searchUrl(term) { return `${opts.shopUrl}?search=${encodeURIComponent(term)}`; },

        // the typed words in bold (text is escaped first, so product names can never inject HTML)
        hl(text) {
            const words = this.query.trim().split(/\s+/).filter((w) => w.length > 1).map((w) => w.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'));
            const safe = esc(text);
            return words.length ? safe.replace(new RegExp(`(${words.map(esc).join('|')})`, 'gi'), '<b>$1</b>') : safe;
        },

        // every row the arrow keys can reach, in screen order
        get rows() {
            if (this.typing) {
                return [
                    ...this.results.categories.map((c) => c.url),
                    ...this.results.products.map((p) => p.url),
                    this.searchUrl(this.query.trim()),
                ];
            }
            return [
                ...this.recent.map((r) => this.searchUrl(r)),
                ...this.trending.searches.map((s) => this.searchUrl(s)),
                ...this.trending.products.map((p) => p.url),
            ];
        },
        // a row's position in `rows`: section offset + index
        idx(section, i) {
            const n = this.typing
                ? { cat: 0, prod: this.results.categories.length, all: this.results.categories.length + this.results.products.length }
                : { recent: 0, trend: this.recent.length, pop: this.recent.length + this.trending.searches.length };
            return n[section] + i;
        },

        onKey(e) {
            const rows = this.rows;
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                if (!rows.length) return;
                e.preventDefault();
                this.open = true;
                this.active = (this.active + (e.key === 'ArrowDown' ? 1 : -1) + rows.length) % rows.length;
                this.$nextTick(() => this.$root.querySelector('[data-ss-active="1"]')?.scrollIntoView({ block: 'nearest' }));
            } else if (e.key === 'Enter' && this.active >= 0 && rows[this.active]) {
                e.preventDefault();
                const url = rows[this.active]; // read before close() resets the highlighted row
                this.remember(this.query);
                this.close();
                go(url);
            } else if (e.key === 'Escape') {
                this.close();
                this.$refs.input.blur();
            }
        },

        remember(term) {
            term = String(term || '').trim();
            if (term.length < 2) return;
            this.recent = [term, ...readRecent().filter((r) => r.toLowerCase() !== term.toLowerCase())].slice(0, 6);
            writeRecent(this.recent);
        },
        forget(term) { this.recent = this.recent.filter((r) => r !== term); writeRecent(this.recent); },
        clearRecent() { this.recent = []; writeRecent([]); },
        pick(term) { this.query = term; this.remember(term); this.close(); go(this.searchUrl(term)); },
        submit(e) {
            if (!this.query.trim()) { e.preventDefault(); this.$refs.input.focus(); return; }
            this.remember(this.query);
            this.close();
        },
        clear() { this.query = ''; this.results = { products: [], categories: [] }; this.active = -1; this.$refs.input.focus(); },
    };
}
