@extends('layouts.admin')
@section('title', $popup->exists ? 'Edit popup' : 'New popup')

@section('content')
@php
    $tz = setting('timezone', 'Asia/Dhaka');
    $dt = fn ($key) => old($key, $popup->{$key}?->timezone($tz)->format('Y-m-d\TH:i'));
    $init = [
        'title' => old('title', $popup->title),
        'body' => old('body', $popup->body),
        'button' => old('button_text', $popup->button_text),
        'url' => old('link_url', $popup->link_url),
        'coupon' => old('coupon_code', $popup->coupon_code),
        'countdown' => $dt('countdown_to'),
        'accent' => old('accent_color', $popup->accent_color ?: '#f97316'),
        'image' => $popup->image ? image_thumb($popup->image, 900) : null,
        'pages' => old('pages', $popup->pages ?: ['all']),
        'trigger' => old('trigger', $popup->trigger),
        'triggerValue' => (int) old('trigger_value', $popup->trigger_value),
        'frequency' => old('frequency', $popup->frequency),
        'products' => $products->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'image' => image_thumb($p->image, 96)])->values(),
        'searchUrl' => route('admin.promo-popups.products'),
    ];
    $catIds = array_map('intval', old('category_ids', $popup->category_ids ?: []));
    $field = 'w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-gray-100';
@endphp
<style>
    .pf-grid { display: grid; grid-template-columns: minmax(0, 1fr) 400px; gap: 1.25rem; align-items: start; }
    .pf-card { background: #fff; border: 1px solid #eef2f7; border-radius: 1.1rem; padding: 1.1rem 1.15rem; margin-bottom: 1rem; }
    .pf-h { font-size: .95rem; font-weight: 800; color: #111827; margin-bottom: .2rem; }
    .pf-sub { font-size: .78rem; color: #6b7280; margin-bottom: .85rem; }
    .pf-label { display: block; font-size: .78rem; font-weight: 700; color: #374151; margin-bottom: .3rem; }
    .pf-opt { font-weight: 500; color: #9ca3af; }
    .pf-row { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .75rem; }
    .pf-chips { display: flex; flex-wrap: wrap; gap: .4rem; }
    .pf-chip { cursor: pointer; }
    .pf-chip input { position: absolute; opacity: 0; pointer-events: none; }
    .pf-chip span { display: inline-flex; align-items: center; gap: .3rem; padding: .4rem .75rem; border-radius: 999px; border: 1px solid #e5e7eb; font-size: .8rem; font-weight: 600; color: #4b5563; background: #fff; transition: all .15s; }
    .pf-chip input:checked + span { background: #fff7ed; border-color: #fb923c; color: #c2410c; }
    .pf-chip input:focus-visible + span { box-shadow: 0 0 0 3px rgba(249,115,22,.25); }
    .pf-drop { position: relative; display: flex; align-items: center; justify-content: center; flex-direction: column; gap: .25rem; min-height: 9rem; border: 2px dashed #e5e7eb; border-radius: 1rem; background: #f9fafb; text-align: center; cursor: pointer; overflow: hidden; transition: border-color .15s; }
    .pf-drop:hover, .pf-drop.is-over { border-color: #fb923c; }
    .pf-drop img { max-height: 14rem; width: auto; max-width: 100%; }
    .pf-swatch { width: 1.9rem; height: 1.9rem; border-radius: 999px; border: 3px solid #fff; box-shadow: 0 0 0 1px #e5e7eb; }
    .pf-swatch.is-on { box-shadow: 0 0 0 2px #111827; }
    .pf-cats { max-height: 13rem; overflow-y: auto; border: 1px solid #eef2f7; border-radius: .8rem; padding: .4rem; }
    .pf-cats label { display: flex; align-items: center; gap: .5rem; padding: .3rem .4rem; border-radius: .5rem; font-size: .82rem; color: #374151; }
    .pf-cats label:hover { background: #f9fafb; }
    .pf-prod { display: inline-flex; align-items: center; gap: .35rem; padding: .2rem .35rem .2rem .2rem; border-radius: .6rem; background: #eef2ff; color: #3730a3; font-size: .78rem; font-weight: 600; max-width: 100%; }
    .pf-prod img { width: 1.6rem; height: 1.6rem; border-radius: .4rem; object-fit: cover; }
    .pf-results { position: absolute; left: 0; right: 0; top: 100%; z-index: 20; margin-top: .25rem; background: #fff; border: 1px solid #e5e7eb; border-radius: .8rem; box-shadow: 0 12px 30px rgba(0,0,0,.12); max-height: 16rem; overflow-y: auto; }
    .pf-results button { display: flex; align-items: center; gap: .5rem; width: 100%; text-align: left; padding: .45rem .6rem; font-size: .82rem; }
    .pf-results button:hover { background: #f9fafb; }
    .pf-results img { width: 1.8rem; height: 1.8rem; border-radius: .4rem; object-fit: cover; background: #f3f4f6; }
    .pf-preview { position: sticky; top: 0; }
    .pf-tabs { display: inline-flex; padding: .2rem; border-radius: .8rem; background: #f3f4f6; margin-bottom: .75rem; }
    .pf-tabs button { padding: .35rem .8rem; border-radius: .6rem; font-size: .78rem; font-weight: 700; color: #6b7280; }
    .pf-tabs button.is-on { background: #fff; color: #111827; box-shadow: 0 1px 3px rgba(0,0,0,.1); }
    .pf-phone { position: relative; width: 300px; height: 590px; margin: 0 auto; border-radius: 2.4rem; border: 10px solid #111827; background: linear-gradient(#e5e7eb, #f3f4f6); overflow: hidden; display: flex; align-items: flex-end; }
    .pf-desk { position: relative; width: 100%; height: 420px; border-radius: 1rem; border: 8px solid #111827; background: linear-gradient(#e5e7eb, #f3f4f6); overflow: hidden; display: flex; align-items: center; justify-content: center; }
    .pf-fake { position: absolute; inset: 0; padding: 1rem; display: flex; flex-direction: column; gap: .6rem; }
    .pf-fake i { display: block; border-radius: .6rem; background: rgba(255,255,255,.75); }
    .pf-dim { position: absolute; inset: 0; background: rgba(15,23,42,.5); }
    .pf-desk .pp-card { transform: scale(.72); transform-origin: center; }
    .pf-phone .pp-card { max-width: none; border-radius: 1.4rem 1.4rem 0 0; font-size: 92%; }
    .pf-phone .pp-handle { display: block; position: absolute; top: .45rem; left: 50%; z-index: 3; width: 2.6rem; height: .3rem; margin-left: -1.3rem; border-radius: 999px; background: rgba(255,255,255,.75); }
    .pf-phone .pp-img { max-height: 290px; }
    .pf-save { position: sticky; bottom: 0; z-index: 10; display: flex; align-items: center; justify-content: flex-end; gap: .6rem; padding: .75rem 1rem; margin: 0 -.25rem; background: rgba(255,255,255,.92); backdrop-filter: blur(6px); border-top: 1px solid #eef2f7; border-radius: 1rem 1rem 0 0; }
    .pf-btn { padding: .65rem 1.2rem; border-radius: .8rem; font-size: .875rem; font-weight: 800; color: #fff; background: linear-gradient(135deg, #f97316, #c2410c); }
    .pf-switch { position: relative; width: 2.75rem; height: 1.55rem; border-radius: 999px; background: #d1d5db; transition: background-color .2s; flex-shrink: 0; }
    .pf-switch::after { content: ''; position: absolute; top: .2rem; left: .2rem; width: 1.15rem; height: 1.15rem; border-radius: 999px; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,.25); transition: transform .2s; }
    input:checked + .pf-switch { background: #10b981; }
    input:checked + .pf-switch::after { transform: translateX(1.2rem); }
    .dark .pf-card, .dark .pf-save, .dark .pf-results, .dark .pf-chip span { background: #111827; border-color: #1f2937; }
    .dark .pf-h, .dark .pf-label { color: #f3f4f6; }
    .dark .pf-chip span { color: #d1d5db; }
    .dark .pf-drop { background: #0b1220; border-color: #374151; }
    .dark .pf-cats { border-color: #1f2937; } .dark .pf-cats label { color: #d1d5db; } .dark .pf-cats label:hover, .dark .pf-results button:hover { background: #1f2937; }
    .dark .pf-tabs { background: #1f2937; } .dark .pf-tabs button.is-on { background: #374151; color: #fff; }
    @media (max-width: 1100px) { .pf-grid { grid-template-columns: minmax(0, 1fr); } .pf-preview { position: static; order: -1; } }
    @media (max-width: 640px) { .pf-row { grid-template-columns: minmax(0, 1fr); } .pf-phone { width: 270px; height: 520px; } }
</style>

<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <div>
        <a href="{{ route('admin.promo-popups.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Promo Popups</a>
        <h1 class="text-xl font-bold text-gray-900 dark:text-white mt-1">{{ $popup->exists ? 'Edit popup' : 'New popup' }}</h1>
    </div>
    @if($popup->exists)
        <a href="{{ url('/') }}?promo_preview={{ $popup->id }}" target="_blank" rel="noopener" data-turbo="false" class="text-sm font-semibold text-orange-600 hover:underline">👁 Preview on the store ↗</a>
    @endif
</div>

@if($errors->any())
    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 dark:bg-red-500/10 dark:border-red-900 px-4 py-3 text-sm text-red-700 dark:text-red-300">
        @foreach($errors->all() as $e)<p>• {{ $e }}</p>@endforeach
    </div>
@endif

<form method="POST" enctype="multipart/form-data" action="{{ $popup->exists ? route('admin.promo-popups.update', $popup) : route('admin.promo-popups.store') }}"
      x-data="popupForm(@js($init))">
    @csrf
    @if($popup->exists) @method('PUT') @endif

    <div class="pf-grid">
        <div>
            {{-- 1. Content --}}
            <div class="pf-card">
                <p class="pf-h">1 · What it shows</p>
                <p class="pf-sub">Use a picture, text, or both — everything below is optional except one of them.</p>

                <label class="pf-label">Picture <span class="pf-opt">(optional — banner, offer image…)</span></label>
                <label class="pf-drop" :class="over && 'is-over'" @dragover.prevent="over = true" @dragleave="over = false" @drop.prevent="over = false; pick($event.dataTransfer.files[0])">
                    <input type="file" name="image" accept="image/*" class="sr-only" x-ref="file" @change="pick($event.target.files[0])">
                    <template x-if="image"><img :src="image" alt=""></template>
                    <template x-if="!image">
                        <div class="py-4">
                            <p style="font-size:1.6rem">🖼</p>
                            <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">Click or drop a picture here</p>
                            <p class="text-xs text-gray-400">JPG / PNG / WebP, up to 4 MB · square or portrait looks best on phones</p>
                        </div>
                    </template>
                </label>
                <input type="hidden" name="remove_image" :value="removed ? 1 : 0">
                <button type="button" x-show="image" @click="image = null; removed = true; $refs.file.value = ''" class="mt-2 text-xs font-semibold text-red-600">Remove picture</button>

                <div class="mt-4">
                    <label class="pf-label">Title <span class="pf-opt">(optional)</span></label>
                    <input type="text" name="title" x-model="title" maxlength="160" class="{{ $field }}" placeholder="e.g. Eid Offer — 20% off everything">
                </div>
                <div class="mt-3">
                    <label class="pf-label">Text <span class="pf-opt">(optional)</span></label>
                    <textarea name="body" x-model="body" rows="3" maxlength="600" class="{{ $field }}" placeholder="e.g. Free delivery all over Bangladesh — today only."></textarea>
                </div>
                <div class="pf-row mt-3">
                    <div>
                        <label class="pf-label">Link when tapped <span class="pf-opt">(optional)</span></label>
                        <input type="text" name="link_url" x-model="url" class="{{ $field }}" placeholder="/shop/category/vitamins or https://…">
                        <label class="inline-flex items-center gap-2 mt-1.5 text-xs text-gray-500"><input type="checkbox" name="new_tab" value="1" class="rounded" @checked(old('new_tab', $popup->new_tab))> Open in a new tab</label>
                    </div>
                    <div>
                        <label class="pf-label">Button text <span class="pf-opt">(shown when there's a link)</span></label>
                        <input type="text" name="button_text" x-model="button" maxlength="40" class="{{ $field }}" placeholder="Shop now">
                    </div>
                </div>
                <div class="pf-row mt-3">
                    <div>
                        <label class="pf-label">Coupon code <span class="pf-opt">(optional — tap to copy)</span></label>
                        <input type="text" name="coupon_code" x-model="coupon" maxlength="60" class="{{ $field }} uppercase" placeholder="EID20">
                    </div>
                    <div>
                        <label class="pf-label">Countdown to <span class="pf-opt">(optional)</span></label>
                        <input type="datetime-local" name="countdown_to" x-model="countdown" class="{{ $field }}">
                    </div>
                </div>
                <div class="mt-3">
                    <label class="pf-label">Colour</label>
                    <div class="flex flex-wrap items-center gap-2">
                        <template x-for="c in ['#f97316', '#dc2626', '#db2777', '#7c3aed', '#2563eb', '#0891b2', '#059669', '#111827']" :key="c">
                            <button type="button" class="pf-swatch" :class="accent === c && 'is-on'" :style="'background:' + c" @click="accent = c" :aria-label="c"></button>
                        </template>
                        <input type="color" x-model="accent" class="w-9 h-9 rounded-lg border-0 bg-transparent cursor-pointer" aria-label="Custom colour">
                        <input type="hidden" name="accent_color" :value="accent">
                    </div>
                </div>
            </div>

            {{-- 2. Where --}}
            <div class="pf-card">
                <p class="pf-h">2 · Where it shows</p>
                <p class="pf-sub">Never on cart, checkout or account pages.</p>
                <div class="pf-chips">
                    @foreach(\App\Models\PromoPopup::PAGES as $k => $l)
                        <label class="pf-chip"><input type="checkbox" name="pages[]" value="{{ $k }}" x-model="pages" @change="pageChanged('{{ $k }}')"><span>{{ $l }}</span></label>
                    @endforeach
                </div>

                <div x-show="narrowable" x-cloak class="mt-4" x-data="{ q: '' }">
                    <p class="pf-label">Only for these categories / products <span class="pf-opt">(optional — leave empty for all)</span></p>
                    <div class="pf-row">
                        <div>
                            <input type="search" x-model="q" placeholder="Filter categories…" class="{{ $field }} mb-1.5">
                            <div class="pf-cats">
                                @foreach($categories as $c)
                                    <label x-show="!q || @js(mb_strtolower($c->name)).includes(q.toLowerCase())">
                                        <input type="checkbox" name="category_ids[]" value="{{ $c->id }}" class="rounded" @checked(in_array($c->id, $catIds, true))>
                                        <span>{{ $c->parent_id ? '— ' : '' }}{{ $c->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <div class="relative" @click.outside="results = []">
                                <input type="search" x-model="pq" @input.debounce.300ms="search()" @focus="search()" placeholder="Search products…" class="{{ $field }}">
                                <div class="pf-results" x-show="results.length" x-cloak>
                                    <template x-for="r in results" :key="r.id">
                                        <button type="button" @click="addProduct(r)"><img :src="r.image || ''" alt=""><span x-text="r.name"></span></button>
                                    </template>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-1.5 mt-2">
                                <template x-for="pr in products" :key="pr.id">
                                    <span class="pf-prod"><img :src="pr.image || ''" alt=""><span class="truncate" style="max-width: 11rem" x-text="pr.name"></span>
                                        <button type="button" @click="products = products.filter(x => x.id !== pr.id)" aria-label="Remove">✕</button>
                                        <input type="hidden" name="product_ids[]" :value="pr.id"></span>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 3. Who --}}
            <div class="pf-card">
                <p class="pf-h">3 · Who sees it</p>
                <div class="pf-row" style="grid-template-columns: repeat(3, minmax(0, 1fr));">
                    @foreach(['device' => [\App\Models\PromoPopup::DEVICES, 'Device'], 'audience' => [\App\Models\PromoPopup::AUDIENCES, 'Visitor'], 'source' => [\App\Models\PromoPopup::SOURCES, 'Came from']] as $name => [$opts, $label])
                        <div>
                            <label class="pf-label">{{ $label }}</label>
                            <select name="{{ $name }}" class="{{ $field }}">
                                @foreach($opts as $k => $l)<option value="{{ $k }}" @selected(old($name, $popup->{$name}) === $k)>{{ $l }}</option>@endforeach
                            </select>
                        </div>
                    @endforeach
                </div>
                <p class="text-xs text-gray-400 mt-2">“Came from Facebook” = visitors who arrived from a Facebook / Instagram ad or post in this visit.</p>
            </div>

            {{-- 4. When --}}
            <div class="pf-card">
                <p class="pf-h">4 · When &amp; how often</p>
                <div class="pf-row">
                    <div>
                        <label class="pf-label">Show it</label>
                        <select name="trigger" x-model="trigger" class="{{ $field }}">
                            @foreach(\App\Models\PromoPopup::TRIGGERS as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach
                        </select>
                    </div>
                    <div x-show="trigger !== 'exit'">
                        <label class="pf-label" x-text="{ delay: 'Seconds on the page', scroll: 'Scrolled down (%)', views: 'Products viewed in this visit' }[trigger]"></label>
                        <input type="number" name="trigger_value" x-model.number="triggerValue" min="0" max="600" class="{{ $field }}">
                    </div>
                    <p x-show="trigger === 'exit'" class="text-xs text-gray-500 self-end">Desktop: when the mouse heads for the close / back button. Phones: after 15 seconds.</p>
                </div>
                <div class="pf-row mt-3">
                    <div>
                        <label class="pf-label">How often per visitor</label>
                        <select name="frequency" x-model="frequency" class="{{ $field }}">
                            @foreach(\App\Models\PromoPopup::FREQUENCIES as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach
                        </select>
                    </div>
                    <div x-show="frequency === 'days'">
                        <label class="pf-label">Every … days</label>
                        <input type="number" name="frequency_days" value="{{ old('frequency_days', $popup->frequency_days) }}" min="1" max="365" class="{{ $field }}">
                    </div>
                </div>
                <div class="pf-row mt-3">
                    <div>
                        <label class="pf-label">Start <span class="pf-opt">(optional — empty = now)</span></label>
                        <input type="datetime-local" name="starts_at" value="{{ $dt('starts_at') }}" class="{{ $field }}">
                    </div>
                    <div>
                        <label class="pf-label">End <span class="pf-opt">(optional — empty = no end)</span></label>
                        <input type="datetime-local" name="ends_at" value="{{ $dt('ends_at') }}" class="{{ $field }}">
                    </div>
                </div>
            </div>

            {{-- 5. Name + priority --}}
            <div class="pf-card">
                <p class="pf-h">5 · Name</p>
                <div class="pf-row">
                    <div>
                        <label class="pf-label">Name <span class="pf-opt">(only you see this)</span></label>
                        <input type="text" name="name" value="{{ old('name', $popup->name) }}" required maxlength="120" class="{{ $field }}" placeholder="Eid offer — product pages">
                    </div>
                    <div>
                        <label class="pf-label">Priority <span class="pf-opt">(higher wins when two fit)</span></label>
                        <input type="number" name="priority" value="{{ old('priority', $popup->priority ?? 0) }}" min="0" max="999" class="{{ $field }}">
                    </div>
                </div>
            </div>

            <div class="pf-save">
                <label class="inline-flex items-center gap-2 mr-auto cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" class="sr-only" @checked(old('is_active', $popup->is_active))>
                    <span class="pf-switch"></span>
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">Live on the store</span>
                </label>
                <a href="{{ route('admin.promo-popups.index') }}" class="text-sm text-gray-500 px-2">Cancel</a>
                <button type="submit" class="pf-btn">{{ $popup->exists ? 'Save changes' : 'Create popup' }}</button>
            </div>
        </div>

        {{-- live preview --}}
        <div class="pf-preview">
            <div class="pf-card">
                <div class="flex items-center justify-between gap-2">
                    <p class="pf-h mb-0">Live preview</p>
                    <div class="pf-tabs" style="margin-bottom:0">
                        <button type="button" :class="view === 'mobile' && 'is-on'" @click="view = 'mobile'">📱 Phone</button>
                        <button type="button" :class="view === 'desktop' && 'is-on'" @click="view = 'desktop'">💻 Desktop</button>
                    </div>
                </div>
                <div class="mt-3">
                    <template x-if="!hasContent"><p class="text-sm text-gray-400 text-center py-16">Add a picture, a title or some text to see it here.</p></template>
                    <template x-if="hasContent">
                        <div :class="view === 'mobile' ? 'pf-phone pp-mobile' : 'pf-desk'">
                            <div class="pf-fake"><i style="height:2rem"></i><i style="height:8rem"></i><i style="height:1rem;width:70%"></i><i style="height:1rem;width:50%"></i><i style="height:6rem"></i></div>
                            <div class="pf-dim"></div>
                            <div style="position: relative; width: 100%; display: flex; justify-content: center;">
                                @include('partials.promo-popup-card')
                            </div>
                        </div>
                    </template>
                </div>
                <p class="text-xs text-gray-400 mt-3 text-center">Phones get a sheet that slides up from the bottom; desktop a centred card.</p>
            </div>
        </div>
    </div>
</form>

<script>
    window.popupForm = function (init) {
        return {
            ...init, view: 'mobile', over: false, removed: false, copied: false, cd: '', pq: '', results: [], timer: null,
            init() {
                const run = () => {
                    const t = this.countdown ? new Date(this.countdown).getTime() : 0, s = Math.floor((t - Date.now()) / 1000);
                    if (!t || s <= 0) { this.cd = ''; return; }
                    const d = Math.floor(s / 86400), h = Math.floor(s % 86400 / 3600), m = Math.floor(s % 3600 / 60), pad = (n) => String(n).padStart(2, '0');
                    this.cd = (d ? d + 'd ' : '') + pad(h) + ':' + pad(m) + ':' + pad(s % 60);
                };
                run(); this.timer = setInterval(run, 1000);
            },
            destroy() { clearInterval(this.timer); },
            get hasContent() { return !!(this.image || (this.title || '').trim() || (this.body || '').trim()); },
            get narrowable() { return this.pages.includes('product') || this.pages.includes('category'); },
            // what the shared card partial draws (PromoPopup::toClient shape)
            get p() {
                const text = [this.title, this.body, this.coupon, this.url && this.button].some((v) => (v || '').trim());
                return { id: 0, layout: this.image ? (text ? 'both' : 'image') : 'text', image: this.image, title: this.title, body: this.body,
                         button: this.button, url: this.url, coupon: (this.coupon || '').toUpperCase(), accent: this.accent };
            },
            pick(file) {
                if (!file || !file.type.startsWith('image/')) return;
                if (file !== this.$refs.file.files[0]) { const dt = new DataTransfer(); dt.items.add(file); this.$refs.file.files = dt.files; }
                this.image = URL.createObjectURL(file); this.removed = false;
            },
            pageChanged(k) {
                // "Every page" and the specific pages are either/or
                if (k === 'all' && this.pages.includes('all')) this.pages = ['all'];
                else if (k !== 'all') this.pages = this.pages.filter((x) => x !== 'all');
                if (!this.pages.length) this.pages = ['all'];
            },
            async search() {
                const r = await fetch(this.searchUrl + '?q=' + encodeURIComponent(this.pq), { headers: { Accept: 'application/json' } }).catch(() => null);
                const ids = this.products.map((x) => x.id);
                this.results = r && r.ok ? (await r.json()).filter((x) => !ids.includes(x.id)) : [];
            },
            addProduct(r) { this.products.push(r); this.results = []; this.pq = ''; },
            close() {}, go() {}, copy() { this.copied = true; setTimeout(() => (this.copied = false), 1500); },
        };
    };
</script>
@endsection
