{{-- Sale settings, shared by create and edit. Expects $flashSale (unsaved on create). --}}
@php
    $tz = setting('timezone', 'Asia/Dhaka');
    $dt = fn ($key) => old($key, $flashSale->{$key}?->copy()->timezone($tz)->format('Y-m-d\TH:i'));
    $field = 'w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-orange-400';
    $flag = fn ($key) => (bool) old($key, $flashSale->{$key});
    $init = [
        'name' => old('name', $flashSale->name),
        'banner' => old('banner_text', $flashSale->banner_text),
        'theme' => old('theme', $flashSale->theme ?: 'inferno'),
        'color' => old('banner_color', $flashSale->banner_color ?: '#ef4444'),
        'layout' => old('layout', $flashSale->layout ?: 'carousel'),
        'start' => $dt('starts_at'),
        'end' => $dt('ends_at'),
        'themes' => \App\Models\FlashSale::THEMES,
    ];
@endphp
<script>
window.flashSaleForm = function (init) {
    const pad = n => String(n).padStart(2, '0');
    const local = d => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
    const shade = (hex, amt) => '#' + hex.replace('#', '').match(/../g)
        .map(h => Math.round(Math.max(0, Math.min(255, parseInt(h, 16) * (1 + amt)))).toString(16).padStart(2, '0')).join('');
    return {
        ...init,
        now: Date.now(),
        init() { setInterval(() => this.now = Date.now(), 1000); },
        grad(key) {
            const t = this.themes[key];
            const [a, b, c] = key === 'custom' || !t.from ? [this.color, shade(this.color, -.12), shade(this.color, -.3)] : [t.from, t.via, t.to];
            return `linear-gradient(120deg, ${a}, ${b} 55%, ${c})`;
        },
        preset(hours) {
            const s = new Date(); s.setSeconds(0, 0);
            this.start = local(s);
            this.end = local(new Date(s.getTime() + hours * 3600e3));
        },
        tonight() {
            const s = new Date(); s.setHours(20, 0, 0, 0);
            if (s < new Date()) s.setDate(s.getDate() + 1);
            this.start = local(s);
            const e = new Date(s); e.setHours(23, 59, 0, 0);
            this.end = local(e);
        },
        get startMs() { return this.start ? new Date(this.start).getTime() : NaN; },
        get endMs() { return this.end ? new Date(this.end).getTime() : NaN; },
        get durationOk() { return this.endMs > this.startMs; },
        get isUpcoming() { return this.startMs > this.now; },
        get durationLabel() {
            if (!this.start || !this.end) return 'Set start & end';
            if (!this.durationOk) return 'End must be after start';
            const mins = Math.round((this.endMs - this.startMs) / 60000);
            const d = Math.floor(mins / 1440), h = Math.floor(mins % 1440 / 60), m = mins % 60;
            return 'Runs ' + [d && d + 'd', h && h + 'h', m && m + 'm'].filter(Boolean).join(' ');
        },
        get clock() {
            const target = this.isUpcoming ? this.startMs : this.endMs;
            const left = Math.max(0, Math.floor(((target || 0) - this.now) / 1000));
            const parts = [{ l: 'h', v: pad(Math.floor(left / 3600) % 24) }, { l: 'm', v: pad(Math.floor(left % 3600 / 60)) }, { l: 's', v: pad(left % 60) }];
            const days = Math.floor(left / 86400);
            return days ? [{ l: 'd', v: days + 'd' }, ...parts] : parts;
        },
    };
}
</script>
<div x-data="flashSaleForm(@js($init))" class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_380px] gap-5 items-start">
    <div class="space-y-4">
        {{-- Basics --}}
        <div class="bg-white rounded-2xl shadow-sm p-5 space-y-4">
            <div>
                <h3 class="font-bold text-gray-800">Basics</h3>
                <p class="text-xs text-gray-500">What shoppers see on the home page, the sale page and product pages.</p>
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Sale name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" x-model="name" required maxlength="255" class="{{ $field }}" placeholder="e.g. 11.11 Mega Flash Sale">
                    @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Headline <span class="font-normal text-gray-400">(optional)</span></label>
                    <input type="text" name="banner_text" x-model="banner" maxlength="255" class="{{ $field }}" placeholder="e.g. Prices crash for 6 hours only!">
                </div>
            </div>
            <div class="grid sm:grid-cols-[minmax(0,1fr)_14rem] gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Short description <span class="font-normal text-gray-400">(sale page + Google)</span></label>
                    <input type="text" name="description" value="{{ old('description', $flashSale->description) }}" maxlength="255" class="{{ $field }}" placeholder="One line about the offer">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">URL slug</label>
                    <div class="flex items-center rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 pl-3 text-sm">
                        <span class="text-gray-400 text-xs whitespace-nowrap">/flash-sale/</span>
                        <input type="text" name="slug" value="{{ old('slug', $flashSale->slug) }}" placeholder="auto" class="w-full bg-transparent border-0 px-1 py-2 text-sm focus:ring-0 text-gray-900 dark:text-gray-100">
                    </div>
                    @error('slug')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        {{-- Schedule --}}
        <div class="bg-white rounded-2xl shadow-sm p-5 space-y-4">
            <div class="flex flex-wrap items-end justify-between gap-2">
                <div>
                    <h3 class="font-bold text-gray-800">Schedule</h3>
                    <p class="text-xs text-gray-500">Times are in {{ $tz }}. The sale switches on and off by itself.</p>
                </div>
                <span class="text-xs font-bold rounded-full px-2.5 py-1" :class="durationOk ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-600'" x-text="durationLabel"></span>
            </div>
            <div class="flex flex-wrap gap-1.5">
                <span class="text-xs font-semibold text-gray-500 mr-1 self-center">Start now, run for:</span>
                @foreach(['1 hour' => 1, '3 hours' => 3, '6 hours' => 6, '12 hours' => 12, '24 hours' => 24, '3 days' => 72, '7 days' => 168] as $label => $hours)
                    <button type="button" @click="preset({{ $hours }})" class="rounded-full border border-gray-200 dark:border-gray-700 px-3 py-1 text-xs font-semibold text-gray-600 hover:border-orange-400 hover:text-orange-600 transition">{{ $label }}</button>
                @endforeach
                <button type="button" @click="tonight()" class="rounded-full border border-gray-200 dark:border-gray-700 px-3 py-1 text-xs font-semibold text-gray-600 hover:border-orange-400 hover:text-orange-600 transition">Tonight 8–12 PM</button>
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Starts at <span class="text-red-500">*</span></label>
                    <input type="datetime-local" name="starts_at" x-model="start" required class="{{ $field }}">
                    @error('starts_at')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Ends at <span class="text-red-500">*</span></label>
                    <input type="datetime-local" name="ends_at" x-model="end" required class="{{ $field }}">
                    @error('ends_at')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        {{-- Look --}}
        <div class="bg-white rounded-2xl shadow-sm p-5 space-y-4">
            <div>
                <h3 class="font-bold text-gray-800">Look &amp; layout</h3>
                <p class="text-xs text-gray-500">Applies instantly on the storefront, no rebuild needed.</p>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-2">Theme</label>
                <input type="hidden" name="theme" :value="theme">
                <div class="grid grid-cols-3 sm:grid-cols-7 gap-2">
                    <template x-for="(t, key) in themes" :key="key">
                        <button type="button" @click="theme = key" class="group text-center">
                            <span class="block h-11 rounded-xl ring-2 ring-offset-2 transition" :class="theme === key ? 'ring-gray-900 dark:ring-white' : 'ring-transparent group-hover:ring-gray-300'"
                                  :style="'background:' + grad(key)"></span>
                            <span class="mt-1 block text-[11px] font-semibold text-gray-600" x-text="t.label"></span>
                        </button>
                    </template>
                </div>
                <div x-show="theme === 'custom'" x-transition class="mt-3 flex items-center gap-3">
                    <input type="color" name="banner_color" x-model="color" class="h-10 w-14 border border-gray-200 rounded-lg cursor-pointer">
                    <span class="text-xs text-gray-500">Pick your brand colour; the gradient and accents are derived from it.</span>
                </div>
            </div>
            <div class="grid sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Home page layout</label>
                    <select name="layout" x-model="layout" class="{{ $field }}">
                        @foreach(\App\Models\FlashSale::LAYOUTS as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Deals on home page</label>
                    <input type="number" name="home_limit" min="4" max="40" value="{{ old('home_limit', $flashSale->home_limit ?: 12) }}" class="{{ $field }}">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Priority <span class="font-normal text-gray-400">(0–100)</span></label>
                    <input type="number" name="priority" min="0" max="100" value="{{ old('priority', $flashSale->priority ?? 0) }}" class="{{ $field }}">
                    <p class="text-[11px] text-gray-400 mt-1">If two sales overlap, higher wins.</p>
                </div>
            </div>
        </div>

        {{-- Visibility --}}
        <div class="bg-white rounded-2xl shadow-sm p-5">
            <h3 class="font-bold text-gray-800 mb-3">Visibility</h3>
            <div class="grid sm:grid-cols-2 gap-3">
                @foreach([
                    'is_active' => ['Active', 'Runs automatically during the schedule. Off = paused.'],
                    'show_on_home' => ['Show on home page', 'The flash sale strip with countdown.'],
                    'show_upcoming' => ['Tease before it starts', 'Show a "Starts in" countdown and sneak-peek prices.'],
                    'show_stock_bar' => ['Show stock progress', '"Only 3 left" / "72% claimed" bars on deals with a stock limit.'],
                ] as $key => [$label, $hint])
                    <label class="flex items-start gap-3 rounded-xl border border-gray-100 dark:border-gray-800 p-3 cursor-pointer hover:bg-gray-50 transition">
                        <input type="hidden" name="{{ $key }}" value="0">
                        <input type="checkbox" name="{{ $key }}" value="1" @checked($flag($key)) class="mt-0.5 w-4 h-4 rounded text-orange-500 focus:ring-orange-400">
                        <span>
                            <span class="block text-sm font-semibold text-gray-800">{{ $label }}</span>
                            <span class="block text-xs text-gray-500">{{ $hint }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="rounded-xl px-6 py-2.5 text-sm font-bold text-white bg-gradient-to-r from-orange-500 to-rose-500 hover:opacity-95 shadow-sm">
                {{ $flashSale->exists ? 'Save settings' : 'Create & add products' }}
            </button>
            <a href="{{ route('admin.flash-sales.index') }}" class="rounded-xl px-6 py-2.5 text-sm font-medium bg-gray-100 text-gray-600 hover:bg-gray-200">Cancel</a>
        </div>
    </div>

    {{-- Live preview --}}
    <div class="xl:sticky xl:top-4 space-y-3">
        <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Live preview</p>
        <div class="rounded-2xl overflow-hidden shadow-sm ring-1 ring-gray-100 bg-white">
            <div class="relative px-4 py-3.5 text-white flex items-center justify-between gap-3" :style="'background:' + grad(theme)">
                <div class="flex items-center gap-2.5 min-w-0">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white/20 ring-1 ring-white/30">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M11.983 1.907a.75.75 0 00-1.292-.657l-8 9.5A.75.75 0 003.25 12H9v6.093a.75.75 0 001.292.657l8-9.5A.75.75 0 0018 8H12V1.907z" clip-rule="evenodd"/></svg>
                    </span>
                    <div class="min-w-0">
                        <p class="text-[9px] font-bold uppercase tracking-[.18em] opacity-90" x-text="isUpcoming ? 'Coming soon' : 'Flash Sale'"></p>
                        <p class="text-sm font-extrabold truncate" x-text="banner || name || 'Your sale name'"></p>
                    </div>
                </div>
                <div class="flex gap-1 text-[11px] font-extrabold tabular-nums">
                    <template x-for="n in clock" :key="n.l">
                        <span class="rounded-md bg-white text-gray-900 px-1.5 py-1" x-text="n.v"></span>
                    </template>
                </div>
            </div>
            <div class="p-3 grid grid-cols-3 gap-2">
                <template x-for="i in 3" :key="i">
                    <div class="rounded-xl ring-1 ring-gray-100 overflow-hidden">
                        <div class="relative aspect-square bg-gray-50">
                            <span class="absolute top-0 left-0 text-white text-[9px] font-extrabold px-1.5 py-0.5 rounded-br-lg" :style="'background:' + grad(theme)" x-text="'-' + (10 + i * 7) + '%'"></span>
                        </div>
                        <div class="p-1.5 space-y-1">
                            <div class="h-1.5 rounded bg-gray-200 w-4/5"></div>
                            <p class="text-[11px] font-extrabold text-gray-900" x-text="'৳' + (499 * i).toLocaleString()"></p>
                            <div class="h-1 rounded-full bg-gray-100 overflow-hidden"><div class="h-full rounded-full" :style="'width:' + (30 * i) + '%;background:' + grad(theme)"></div></div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
        <p class="text-[11px] text-gray-400" x-text="layout === 'grid' ? 'Grid: deals wrap into rows on the home page.' : 'Carousel: one swipeable row with arrows on desktop.'"></p>
    </div>
</div>
