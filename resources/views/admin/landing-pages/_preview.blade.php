{{-- Live Preview panel for the landing page builder (included at the top of _form).

     Renders the REAL public template (landing.show via Admin\LandingPageController::preview)
     from the form's current, unsaved state, inside a phone frame — every keystroke, editor
     change, repeater add/remove and image pick refreshes it (debounced). Images are uploaded
     once when picked (previewUpload) and referenced by temp path afterwards, so a refresh
     never re-sends files. Two stacked iframes are used as a double buffer: the next render
     loads in the hidden one, takes over the scroll position, then swaps in — no white flash
     and no jump back to the top while typing. --}}
@once
@push('styles')
<style>
    @media (min-width: 1280px) {
        html.lp-preview-open .lp-builder { max-width: none; margin-right: 470px; }
    }
    @media (min-width: 1280px) and (max-width: 1599px) {
        html.lp-preview-open .lp-builder form > .grid { grid-template-columns: minmax(0, 1fr); }
        html.lp-preview-open .lp-builder form > .grid > .col-span-2 { grid-column: auto; }
    }
    .lp-phone { width: 390px; height: 800px; transform-origin: top center; }
</style>
@endpush
@endonce

<div x-data="{
        open: false,
        status: 'idle',
        notice: '',
        updatedAt: '',
        scale: 1,
        front: 'a',
        html: '',
        timer: null,
        ctl: null,
        pendingY: 0,
        form: null,
        init() {
            this.form = this.$el.closest('form');
            let pref = null;
            try { pref = localStorage.getItem('lp-preview-open'); } catch (e) {}
            this.setOpen(pref === null ? window.innerWidth >= 1280 : pref === '1', false);

            this.form.addEventListener('input', (e) => { if (e.target.type !== 'file') this.schedule(); });
            this.form.addEventListener('change', (e) => {
                if (e.target.type === 'file') this.upload(e.target); else this.schedule();
            });
            this.form.addEventListener('editor-change', () => this.schedule(900));
            {{-- Repeater add/remove buttons change the DOM without an input event. --}}
            this.form.addEventListener('click', (e) => { if (e.target.closest('button[type=button]')) this.schedule(350); });
            this._onMessage = (e) => { if (e.data && e.data.type === 'lp-preview-notice') this.flash(e.data.text); };
            this._onResize = () => this.fit();
            window.addEventListener('message', this._onMessage);
            window.addEventListener('resize', this._onResize);
            this.$nextTick(() => this.fit());
        },
        {{-- Admin pages navigate with Turbo: leaving this page must not leave the layout
             class or window listeners behind on the next page. --}}
        destroy() {
            document.documentElement.classList.remove('lp-preview-open');
            window.removeEventListener('message', this._onMessage);
            window.removeEventListener('resize', this._onResize);
            clearTimeout(this.timer);
            if (this.ctl) this.ctl.abort();
        },
        setOpen(v, remember = true) {
            this.open = v;
            document.documentElement.classList.toggle('lp-preview-open', v);
            if (remember) { try { localStorage.setItem('lp-preview-open', v ? '1' : '0'); } catch (e) {} }
            if (v) { this.$nextTick(() => { this.fit(); this.refresh(); }); }
        },
        fit() {
            const box = this.$refs.stage;
            if (!box) return;
            this.scale = Math.min(1, (box.clientWidth - 24) / 390, (box.clientHeight - 24) / 800);
        },
        schedule(ms = 650) {
            if (!this.open) return;
            clearTimeout(this.timer);
            this.timer = setTimeout(() => this.refresh(), ms);
        },
        flash(text) {
            this.notice = text;
            clearTimeout(this._n);
            this._n = setTimeout(() => this.notice = '', 2600);
        },
        async upload(input) {
            input._pvPaths = [];
            if (!input.files.length) { this.schedule(); return; }
            this.status = 'loading';
            input._pvPaths = await Promise.all([...input.files].map((file) => {
                const fd = new FormData();
                fd.append('image', file);
                return fetch({{ Js::from(route('admin.landing-pages.preview-upload')) }}, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': {{ Js::from(csrf_token()) }}, 'Accept': 'application/json' },
                    body: fd,
                }).then(r => r.ok ? r.json() : null).then(d => (d && d.path) || '').catch(() => '');
            }));
            this.refresh();
        },
        buildData() {
            const fd = new FormData(this.form);
            fd.delete('_method');
            const fileInputs = [...this.form.querySelectorAll('input[type=file][name]')];
            new Set(fileInputs.map(el => el.name)).forEach(name => fd.delete(name));
            {{-- Real files are never re-sent — each picked image is swapped for the temp
                 path upload() got back, keeping per-row [] inputs index-aligned with their
                 text columns (an empty entry for rows with no new image). --}}
            fileInputs.forEach((el) => {
                const base = el.name.replace(/\[\]$/, '');
                const paths = el.files.length ? (el._pvPaths || []) : [];
                if (el.multiple) {
                    paths.filter(Boolean).forEach(p => fd.append('_pv_' + base + '[]', p));
                } else if (el.name.endsWith('[]')) {
                    fd.append('_pv_' + base + '[]', paths[0] || '');
                } else if (paths[0]) {
                    fd.append('_pv_' + base, paths[0]);
                }
            });
            @if($lp)
            fd.append('_preview_id', {{ Js::from($lp->id) }});
            @endif
            return fd;
        },
        async refresh() {
            if (!this.open) return;
            if (this.ctl) this.ctl.abort();
            this.ctl = new AbortController();
            this.status = 'loading';
            try {
                const res = await fetch({{ Js::from(route('admin.landing-pages.preview')) }}, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': {{ Js::from(csrf_token()) }}, 'Accept': 'text/html' },
                    body: this.buildData(),
                    signal: this.ctl.signal,
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                this.html = await res.text();
                const shown = this.$refs[this.front === 'a' ? 'frameA' : 'frameB'];
                const next = this.$refs[this.front === 'a' ? 'frameB' : 'frameA'];
                try { this.pendingY = shown.contentWindow.scrollY || 0; } catch (e) { this.pendingY = 0; }
                next.srcdoc = this.html;
            } catch (e) {
                if (e.name !== 'AbortError') this.status = 'error';
            }
        },
        swapIn(which) {
            if (!this.html) return;
            const frame = this.$refs[which === 'a' ? 'frameA' : 'frameB'];
            try { frame.contentWindow.scrollTo(0, this.pendingY); } catch (e) {}
            this.front = which;
            this.status = 'ready';
            this.updatedAt = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        },
        openTab() {
            if (!this.html) return;
            window.open(URL.createObjectURL(new Blob([this.html], { type: 'text/html' })), '_blank');
        },
    }">
    {{-- Floating toggle --}}
    <button type="button" x-show="!open" x-cloak @click="setOpen(true)"
        class="fixed bottom-6 right-6 z-40 inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-indigo-600 to-fuchsia-600 px-5 py-3 text-sm font-semibold text-white shadow-xl shadow-indigo-600/30 transition hover:-translate-y-0.5 hover:shadow-2xl">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
        Live Preview
    </button>

    {{-- Panel --}}
    <aside x-show="open" x-cloak
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full opacity-0" x-transition:enter-end="translate-x-0 opacity-100"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0 opacity-100" x-transition:leave-end="translate-x-full opacity-0"
        class="fixed inset-y-0 right-0 z-40 flex w-full max-w-[460px] flex-col border-l border-gray-200 bg-gradient-to-b from-slate-100 to-slate-200 shadow-2xl dark:border-gray-800 dark:from-gray-900 dark:to-gray-950">
        <div class="flex items-center gap-2 border-b border-gray-200 bg-white/80 px-4 py-3 backdrop-blur dark:border-gray-800 dark:bg-gray-900/80">
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-gray-900 dark:text-white">Live Preview</p>
                <p class="flex items-center gap-1.5 text-[11px] text-gray-500">
                    <span class="h-2 w-2 rounded-full"
                        :class="{ 'bg-emerald-500': status === 'ready', 'bg-amber-400 animate-pulse': status === 'loading', 'bg-red-500': status === 'error', 'bg-gray-300': status === 'idle' }"></span>
                    <span x-show="status === 'loading'">Updating…</span>
                    <span x-show="status === 'ready'" x-text="'Up to date · ' + updatedAt"></span>
                    <span x-show="status === 'error'">Couldn't render — keep editing or press refresh</span>
                    <span x-show="status === 'idle'">Waiting…</span>
                </p>
            </div>
            <button type="button" @click="refresh()" title="Refresh preview" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-indigo-600 dark:hover:bg-gray-800">
                <svg class="h-4 w-4" :class="status === 'loading' && 'animate-spin'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            </button>
            <button type="button" @click="openTab()" title="Open preview in a new tab" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-indigo-600 dark:hover:bg-gray-800">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
            </button>
            <button type="button" @click="form.requestSubmit()" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">
                {{ $lp ? 'Save' : 'Create' }}
            </button>
            <button type="button" @click="setOpen(false)" title="Close preview" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div x-ref="stage" class="relative flex flex-1 justify-center overflow-hidden pt-3">
            {{-- Phone frame (390×800 viewport), scaled down to fit the panel --}}
            <div class="lp-phone relative shrink-0 rounded-[2.6rem] bg-gray-900 p-2.5 shadow-[0_30px_60px_-20px_rgba(0,0,0,0.5)] ring-1 ring-black/40" :style="`transform: scale(${scale}); margin-bottom: ${(scale - 1) * 800}px`">
                <div class="absolute left-1/2 top-2.5 z-10 h-5 w-28 -translate-x-1/2 rounded-b-2xl bg-gray-900"></div>
                <div class="relative h-full w-full overflow-hidden rounded-[2.1rem] bg-white">
                    <iframe x-ref="frameA" title="Landing page preview" @load="swapIn('a')"
                        class="absolute inset-0 h-full w-full border-0 bg-white transition-opacity duration-150"
                        :class="front === 'a' ? 'opacity-100 z-[1]' : 'opacity-0 z-0 pointer-events-none'"></iframe>
                    <iframe x-ref="frameB" title="Landing page preview" @load="swapIn('b')"
                        class="absolute inset-0 h-full w-full border-0 bg-white transition-opacity duration-150"
                        :class="front === 'b' ? 'opacity-100 z-[1]' : 'opacity-0 z-0 pointer-events-none'"></iframe>
                    <div x-show="!html" class="absolute inset-0 z-[2] flex flex-col items-center justify-center gap-3 bg-white text-sm text-gray-400">
                        <svg class="h-6 w-6 animate-spin text-indigo-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                        Building preview…
                    </div>
                </div>
            </div>

            <div x-show="notice" x-cloak x-transition
                class="absolute bottom-5 left-1/2 z-10 -translate-x-1/2 rounded-full bg-gray-900/90 px-4 py-2 text-xs font-medium text-white shadow-lg" x-text="notice"></div>
        </div>

        <p class="border-t border-gray-200 bg-white/70 px-4 py-2 text-center text-[11px] text-gray-500 dark:border-gray-800 dark:bg-gray-900/70">
            Unsaved changes shown live · nothing is published until you Save
        </p>
    </aside>
</div>
