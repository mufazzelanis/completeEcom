@php
$seo = $product ?? null;
$pvd = $seo?->price_valid_until?->format('Y-m-d') ?? '';

// PHP-side audit checks (for Audit tab)
$mt  = old('meta_title',    $seo?->meta_title    ?? '');
$md  = old('meta_description', $seo?->meta_description ?? '');
$fk  = old('focus_keyword', $seo?->focus_keyword ?? '');
$ogi = old('og_image',      $seo?->og_image      ?? '');
$alt = old('image_alt',     $seo?->image_alt     ?? '');
$des = old('description',   $seo?->description   ?? '');
$savedScore = $seo?->seo_score ?? 0;

$auditChecks = [
    ['ok' => strlen($mt) >= 30 && strlen($mt) <= 70,  'msg' => strlen($mt) > 0 ? 'Meta title: '.strlen($mt).' chars (30–70 recommended)' : 'Missing meta title'],
    ['ok' => strlen($md) >= 100 && strlen($md) <= 160,'msg' => strlen($md) > 0 ? 'Meta description: '.strlen($md).' chars (100–160 recommended)' : 'Missing meta description'],
    ['ok' => $fk !== '',  'msg' => $fk !== '' ? 'Focus keyword set' : 'No focus keyword set'],
    ['ok' => $ogi !== '', 'msg' => $ogi !== '' ? 'Social sharing (OG) image set' : 'Missing OG / social sharing image'],
    ['ok' => $alt !== '', 'msg' => $alt !== '' ? 'Main image alt text set' : 'Missing image alt text'],
    ['ok' => strlen($des) > 200, 'msg' => strlen($des) > 200 ? 'Good product description (200+ chars)' : (strlen($des) > 0 ? 'Description could be longer (200+ chars recommended)' : 'Missing product description')],
    ['ok' => $fk !== '' && $mt !== '' && str_contains(strtolower($mt), strtolower($fk)), 'msg' => ($fk !== '' && $mt !== '' && str_contains(strtolower($mt), strtolower($fk))) ? 'Focus keyword found in meta title' : ($fk !== '' ? 'Focus keyword not in meta title' : 'Set a focus keyword first')],
];
@endphp

<div class="mt-6"
    x-data="{
        seoTab: 'basic',
        aiFeatures: {{ Js::from(old('ai_key_features', $seo?->ai_key_features ?? [])) }},
        aiBenefits: {{ Js::from(old('ai_benefits',     $seo?->ai_benefits     ?? [])) }},
        aiUseCases: {{ Js::from(old('ai_use_cases',    $seo?->ai_use_cases    ?? [])) }},
        addFeature()    { this.aiFeatures.push(''); },
        removeFeature(i){ this.aiFeatures.splice(i,1); },
        addBenefit()    { this.aiBenefits.push(''); },
        removeBenefit(i){ this.aiBenefits.splice(i,1); },
        addUseCase()    { this.aiUseCases.push(''); },
        removeUseCase(i){ this.aiUseCases.splice(i,1); }
    }">
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">

        {{-- Header --}}
        <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-emerald-50 to-teal-50">
            <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <div class="flex-1">
                <h3 class="font-semibold text-gray-800">Product SEO</h3>
                <p class="text-xs text-gray-500">Optimize for search engines, social sharing, and AI overviews</p>
            </div>
            @if($seo)
            <div class="flex items-center gap-2 text-sm">
                <span class="text-gray-500">SEO Score:</span>
                <span class="font-bold px-2 py-0.5 rounded-lg text-sm
                    {{ $savedScore >= 70 ? 'bg-emerald-100 text-emerald-700' : ($savedScore >= 40 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-600') }}">
                    {{ $savedScore }}/100
                </span>
            </div>
            @endif
        </div>

        {{-- Sub-tab nav --}}
        <div class="flex border-b border-gray-100 overflow-x-auto bg-gray-50/50">
            @foreach([
                ['basic',      'Basic SEO',    'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z'],
                ['social',     'Social',       'M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z'],
                ['visibility', 'Visibility',   'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z'],
                ['schema',     'Schema',       'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4'],
                ['merchant',   'Merchant',     'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z'],
                ['ai',         'AI SEO',       'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z'],
                ['audit',      'SEO Audit',    'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
            ] as [$tab, $label, $icon])
            <button type="button" @click="seoTab='{{ $tab }}'"
                class="flex items-center gap-1.5 px-4 py-3 text-sm font-medium whitespace-nowrap border-b-2 transition-colors"
                :class="seoTab==='{{ $tab }}' ? 'border-emerald-500 text-emerald-700 bg-white' : 'border-transparent text-gray-500 hover:text-gray-700'">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}"/>
                </svg>
                {{ $label }}
            </button>
            @endforeach
        </div>

        <div class="p-6">

            {{-- ── Basic SEO ────────────────────────────────────────────── --}}
            <div x-show="seoTab==='basic'" x-cloak>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Meta Title
                            <span class="text-xs text-gray-400 font-normal ml-1">30–70 chars recommended</span>
                        </label>
                        <input type="text" name="meta_title" value="{{ old('meta_title', $seo?->meta_title ?? '') }}" maxlength="100"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Meta Description
                            <span class="text-xs text-gray-400 font-normal ml-1">100–160 chars recommended</span>
                        </label>
                        <textarea name="meta_description" rows="3" maxlength="300"
                            class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 resize-none">{{ old('meta_description', $seo?->meta_description ?? '') }}</textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Focus Keyword</label>
                        <input type="text" name="focus_keyword" value="{{ old('focus_keyword', $seo?->focus_keyword ?? '') }}"
                            placeholder="e.g. wireless headphones"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Canonical URL
                            <span class="text-xs text-gray-400 font-normal">(leave blank = auto)</span>
                        </label>
                        <input type="url" name="canonical_url" value="{{ old('canonical_url', $seo?->canonical_url ?? '') }}"
                            placeholder="https://..."
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Robots Meta</label>
                        <select name="robots_meta"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            @foreach(['index,follow' => 'Index, Follow (default)', 'noindex,follow' => 'No Index, Follow', 'index,nofollow' => 'Index, No Follow', 'noindex,nofollow' => 'No Index, No Follow'] as $val => $lbl)
                            <option value="{{ $val }}" {{ old('robots_meta', $seo?->robots_meta ?? 'index,follow') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Breadcrumb Title
                            <span class="text-xs text-gray-400 font-normal">(if different from product name)</span>
                        </label>
                        <input type="text" name="breadcrumb_title" value="{{ old('breadcrumb_title', $seo?->breadcrumb_title ?? '') }}"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sitemap Priority</label>
                        <select name="sitemap_priority"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            @foreach(['1.00'=>'1.0 — Highest','0.80'=>'0.8 — High','0.60'=>'0.6 — Medium-High','0.50'=>'0.5 — Medium (default)','0.30'=>'0.3 — Low','0.10'=>'0.1 — Very Low'] as $val => $lbl)
                            <option value="{{ $val }}" {{ number_format((float) old('sitemap_priority', $seo?->sitemap_priority ?? 0.5), 2) === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Change Frequency</label>
                        <select name="sitemap_changefreq"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            @foreach(['always'=>'Always','hourly'=>'Hourly','daily'=>'Daily','weekly'=>'Weekly','monthly'=>'Monthly','yearly'=>'Yearly','never'=>'Never'] as $val => $lbl)
                            <option value="{{ $val }}" {{ old('sitemap_changefreq', $seo?->sitemap_changefreq ?? 'weekly') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">301 Redirect URL</label>
                        <input type="url" name="redirect_url" value="{{ old('redirect_url', $seo?->redirect_url ?? '') }}"
                            placeholder="https://... (redirect visitors to this URL)"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                </div>
            </div>

            {{-- ── Social Sharing ───────────────────────────────────────── --}}
            <div x-show="seoTab==='social'" x-cloak>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="md:col-span-2">
                        <h4 class="text-sm font-semibold text-gray-700 flex items-center gap-2 mb-3">
                            <span class="w-2 h-2 bg-blue-500 rounded-full"></span> Open Graph (Facebook · LinkedIn · WhatsApp)
                        </h4>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            OG Title <span class="text-xs text-gray-400 font-normal">(blank = meta title)</span>
                        </label>
                        <input type="text" name="og_title" value="{{ old('og_title', $seo?->og_title ?? '') }}" maxlength="100"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">OG Image URL <span class="text-xs text-gray-400 font-normal">1200×630 px</span></label>
                        <input type="url" name="og_image" value="{{ old('og_image', $seo?->og_image ?? '') }}"
                            placeholder="https://..."
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">OG Description</label>
                        <textarea name="og_description" rows="2" maxlength="300"
                            class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 resize-none">{{ old('og_description', $seo?->og_description ?? '') }}</textarea>
                    </div>
                    <div class="md:col-span-2 border-t border-gray-100 pt-4 mt-1">
                        <h4 class="text-sm font-semibold text-gray-700 flex items-center gap-2 mb-3">
                            <span class="w-2 h-2 bg-sky-400 rounded-full"></span> Twitter / X Card
                        </h4>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Card Type</label>
                        <select name="twitter_card"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            @foreach(['summary'=>'Summary (small image)','summary_large_image'=>'Summary with Large Image'] as $val => $lbl)
                            <option value="{{ $val }}" {{ old('twitter_card', $seo?->twitter_card ?? 'summary_large_image') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Twitter Title <span class="text-xs text-gray-400 font-normal">(blank = meta title)</span></label>
                        <input type="text" name="twitter_title" value="{{ old('twitter_title', $seo?->twitter_title ?? '') }}" maxlength="70"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Twitter Image URL <span class="text-xs text-gray-400 font-normal">2:1 ratio</span></label>
                        <input type="url" name="twitter_image" value="{{ old('twitter_image', $seo?->twitter_image ?? '') }}"
                            placeholder="https://..."
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Twitter Description</label>
                        <textarea name="twitter_description" rows="2" maxlength="200"
                            class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 resize-none">{{ old('twitter_description', $seo?->twitter_description ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- ── Search Visibility ────────────────────────────────────── --}}
            <div x-show="seoTab==='visibility'" x-cloak>
                <div class="max-w-2xl space-y-4">
                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 text-sm text-amber-800">
                        By default all products are indexable. Only change these if you have a specific reason to exclude this product from search engine results.
                    </div>
                    @foreach([
                        ['noindex',     'No Index',       'Prevents search engines from indexing this product page.'],
                        ['nofollow',    'No Follow',      'Prevents search engines from following links on this page.'],
                        ['nosnippet',   'No Snippet',     'Prevents search engines from showing text snippets or cached versions.'],
                        ['noimageindex','No Image Index', 'Prevents search engines from indexing images on this page.'],
                    ] as [$fname, $flabel, $fdesc])
                    <div class="flex items-start gap-4 bg-gray-50 rounded-xl p-4">
                        <label class="relative inline-flex items-center cursor-pointer mt-0.5 flex-shrink-0">
                            <input type="checkbox" name="{{ $fname }}" value="1"
                                {{ old($fname, $seo?->{$fname} ?? false) ? 'checked' : '' }}
                                class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-red-500 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full"></div>
                        </label>
                        <div>
                            <p class="text-sm font-semibold text-gray-800">{{ $flabel }}</p>
                            <p class="text-xs text-gray-500 mt-0.5">{{ $fdesc }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- ── Schema Markup ────────────────────────────────────────── --}}
            <div x-show="seoTab==='schema'" x-cloak>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Schema Type</label>
                        <select name="schema_type"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            @foreach(['Product'=>'Product','ItemPage'=>'Item Page','SoftwareApplication'=>'Software','Book'=>'Book','MusicAlbum'=>'Music Album'] as $val => $lbl)
                            <option value="{{ $val }}" {{ old('schema_type', $seo?->schema_type ?? 'Product') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Condition</label>
                        <select name="schema_condition"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            @foreach(['NewCondition'=>'New','UsedCondition'=>'Used','RefurbishedCondition'=>'Refurbished','DamagedCondition'=>'Damaged'] as $val => $lbl)
                            <option value="{{ $val }}" {{ old('schema_condition', $seo?->schema_condition ?? 'NewCondition') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Availability</label>
                        <select name="schema_availability"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            @foreach(['InStock'=>'In Stock','OutOfStock'=>'Out of Stock','PreOrder'=>'Pre-order','BackOrder'=>'Back Order','Discontinued'=>'Discontinued','SoldOut'=>'Sold Out'] as $val => $lbl)
                            <option value="{{ $val }}" {{ old('schema_availability', $seo?->schema_availability ?? 'InStock') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Price Valid Until</label>
                        <input type="date" name="price_valid_until"
                            value="{{ old('price_valid_until', $pvd) }}"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">GTIN <span class="text-xs text-gray-400 font-normal">(EAN / UPC / barcode)</span></label>
                        <input type="text" name="gtin" value="{{ old('gtin', $seo?->gtin ?? '') }}"
                            placeholder="e.g. 012345678905"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">MPN <span class="text-xs text-gray-400 font-normal">(Manufacturer Part No.)</span></label>
                        <input type="text" name="mpn" value="{{ old('mpn', $seo?->mpn ?? '') }}"
                            placeholder="e.g. WH-1000XM5"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div class="md:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Country of Origin</label>
                        <input type="text" name="country_of_origin" value="{{ old('country_of_origin', $seo?->country_of_origin ?? '') }}"
                            placeholder="e.g. Japan"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                </div>
            </div>

            {{-- ── Merchant Center ──────────────────────────────────────── --}}
            <div x-show="seoTab==='merchant'" x-cloak>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Google Product Category <span class="text-xs text-gray-400 font-normal">(Google taxonomy path)</span></label>
                        <input type="text" name="google_category" value="{{ old('google_category', $seo?->google_category ?? '') }}"
                            placeholder="e.g. Electronics > Audio > Headphones"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Google Product Type <span class="text-xs text-gray-400 font-normal">(your own category taxonomy)</span></label>
                        <input type="text" name="google_product_type" value="{{ old('google_product_type', $seo?->google_product_type ?? '') }}"
                            placeholder="e.g. Wireless Headphones > Over-Ear"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Age Group</label>
                        <select name="age_group"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            <option value="">— Not specified —</option>
                            @foreach(['newborn'=>'Newborn','infant'=>'Infant','toddler'=>'Toddler','kids'=>'Kids','adult'=>'Adult'] as $val => $lbl)
                            <option value="{{ $val }}" {{ old('age_group', $seo?->age_group ?? '') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                        <select name="gender"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            <option value="">— Not specified —</option>
                            @foreach(['male'=>'Male','female'=>'Female','unisex'=>'Unisex'] as $val => $lbl)
                            <option value="{{ $val }}" {{ old('gender', $seo?->gender ?? '') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Color</label>
                        <input type="text" name="color_description" value="{{ old('color_description', $seo?->color_description ?? '') }}"
                            placeholder="e.g. Midnight Black"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Size</label>
                        <input type="text" name="size_description" value="{{ old('size_description', $seo?->size_description ?? '') }}"
                            placeholder="e.g. One Size / XL"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Material</label>
                        <input type="text" name="material" value="{{ old('material', $seo?->material ?? '') }}"
                            placeholder="e.g. 100% Cotton / Stainless Steel"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div class="md:col-span-2 border-t border-gray-100 pt-4 mt-1">
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">Image SEO</h4>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Main Image Alt Text</label>
                        <input type="text" name="image_alt" value="{{ old('image_alt', $seo?->image_alt ?? '') }}"
                            placeholder="Descriptive alt text for the main product image"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Main Image Title</label>
                        <input type="text" name="image_title" value="{{ old('image_title', $seo?->image_title ?? '') }}"
                            placeholder="Title attribute for the main image"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                </div>
            </div>

            {{-- ── AI SEO ───────────────────────────────────────────────── --}}
            <div x-show="seoTab==='ai'" x-cloak>
                <div class="space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Short Summary
                            <span class="text-xs text-gray-400 font-normal ml-1">For rich snippets & AI overviews — ~50–100 words</span>
                        </label>
                        <textarea name="ai_summary" rows="3"
                            class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">{{ old('ai_summary', $seo?->ai_summary ?? '') }}</textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            AI Overview Description
                            <span class="text-xs text-gray-400 font-normal ml-1">Detailed overview for AI-powered search results</span>
                        </label>
                        <textarea name="ai_overview" rows="4"
                            class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">{{ old('ai_overview', $seo?->ai_overview ?? '') }}</textarea>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-medium text-gray-700">Key Features</label>
                            <button type="button" @click="addFeature()"
                                class="text-xs font-medium text-emerald-600 hover:text-emerald-800 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Add Feature
                            </button>
                        </div>
                        <div class="space-y-2">
                            <template x-if="aiFeatures.length === 0">
                                <p class="text-sm text-gray-400 py-1">No key features yet. Click Add Feature.</p>
                            </template>
                            <template x-for="(feat, i) in aiFeatures" :key="i">
                                <div class="flex gap-2">
                                    <input type="text" :name="`ai_key_features[${i}]`" x-model="aiFeatures[i]"
                                        placeholder="e.g. 30-hour battery life with fast charging"
                                        class="flex-1 border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                    <button type="button" @click="removeFeature(i)"
                                        class="text-red-400 hover:text-red-600 flex-shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-medium text-gray-700">Product Benefits</label>
                            <button type="button" @click="addBenefit()"
                                class="text-xs font-medium text-emerald-600 hover:text-emerald-800 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Add Benefit
                            </button>
                        </div>
                        <div class="space-y-2">
                            <template x-if="aiBenefits.length === 0">
                                <p class="text-sm text-gray-400 py-1">No benefits yet.</p>
                            </template>
                            <template x-for="(benefit, i) in aiBenefits" :key="i">
                                <div class="flex gap-2">
                                    <input type="text" :name="`ai_benefits[${i}]`" x-model="aiBenefits[i]"
                                        placeholder="e.g. Reduces noise for crystal-clear audio"
                                        class="flex-1 border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                    <button type="button" @click="removeBenefit(i)" class="text-red-400 hover:text-red-600 flex-shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-medium text-gray-700">Use Cases</label>
                            <button type="button" @click="addUseCase()"
                                class="text-xs font-medium text-emerald-600 hover:text-emerald-800 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Add Use Case
                            </button>
                        </div>
                        <div class="space-y-2">
                            <template x-if="aiUseCases.length === 0">
                                <p class="text-sm text-gray-400 py-1">No use cases yet.</p>
                            </template>
                            <template x-for="(uc, i) in aiUseCases" :key="i">
                                <div class="flex gap-2">
                                    <input type="text" :name="`ai_use_cases[${i}]`" x-model="aiUseCases[i]"
                                        placeholder="e.g. Perfect for remote workers on video calls"
                                        class="flex-1 border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                    <button type="button" @click="removeUseCase(i)" class="text-red-400 hover:text-red-600 flex-shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Comparison Content
                            <span class="text-xs text-gray-400 font-normal ml-1">How this product compares to alternatives</span>
                        </label>
                        <textarea name="ai_comparison" rows="3"
                            class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">{{ old('ai_comparison', $seo?->ai_comparison ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- ── SEO Audit ────────────────────────────────────────────── --}}
            <div x-show="seoTab==='audit'" x-cloak>
                <div class="space-y-5">
                    {{-- Score ring --}}
                    <div class="flex items-center gap-5 bg-gray-50 rounded-xl p-5">
                        <div class="relative w-24 h-24 flex-shrink-0">
                            <svg class="w-24 h-24 -rotate-90" viewBox="0 0 36 36">
                                <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#e5e7eb" stroke-width="3"/>
                                <circle cx="18" cy="18" r="15.9155" fill="none"
                                    stroke="{{ $savedScore >= 70 ? '#10b981' : ($savedScore >= 40 ? '#f59e0b' : '#ef4444') }}"
                                    stroke-width="3"
                                    stroke-dasharray="{{ $savedScore }} {{ 100 - $savedScore }}"
                                    stroke-linecap="round"/>
                            </svg>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="text-2xl font-bold {{ $savedScore >= 70 ? 'text-emerald-600' : ($savedScore >= 40 ? 'text-amber-500' : 'text-red-500') }}">
                                    {{ $savedScore }}
                                </span>
                            </div>
                        </div>
                        <div>
                            <p class="text-lg font-semibold text-gray-800">
                                @if($savedScore >= 70) Good — well optimized!
                                @elseif($savedScore >= 40) Needs improvement
                                @else Poor — significant SEO issues found
                                @endif
                            </p>
                            <p class="text-sm text-gray-500 mt-1">Score updates when you save the product. Fill in the SEO fields above to improve your score.</p>
                            @if(!$seo)
                            <p class="text-xs text-amber-600 mt-1">Score will be calculated after the product is saved for the first time.</p>
                            @endif
                        </div>
                    </div>
                    {{-- Checks --}}
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">SEO Checklist</h4>
                        <div class="space-y-2">
                            @foreach($auditChecks as $check)
                            <div class="flex items-start gap-3 rounded-xl px-4 py-3 {{ $check['ok'] ? 'bg-emerald-50 border border-emerald-100' : 'bg-gray-50 border border-gray-100' }}">
                                <svg class="w-4 h-4 mt-0.5 flex-shrink-0 {{ $check['ok'] ? 'text-emerald-500' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $check['ok'] ? 'M5 13l4 4L19 7' : 'M12 12m0 0' }}"/>
                                    @if(!$check['ok'])
                                    <circle cx="12" cy="12" r="3" fill="currentColor"/>
                                    @endif
                                </svg>
                                <span class="text-sm {{ $check['ok'] ? 'text-emerald-700' : 'text-gray-600' }}">{{ $check['msg'] }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    {{-- Tips --}}
                    <div class="bg-blue-50 border border-blue-100 rounded-xl p-4">
                        <h4 class="text-sm font-semibold text-blue-800 mb-2">Quick Tips</h4>
                        <ul class="text-xs text-blue-700 space-y-1 list-disc list-inside">
                            <li>Include your focus keyword in the meta title and description</li>
                            <li>Add an OG image to improve social sharing click-through rates</li>
                            <li>Write a descriptive alt text for the main product image</li>
                            <li>Use the AI SEO tab to add structured content for AI search overviews</li>
                            <li>Fill in Schema markup fields for richer Google Shopping results</li>
                        </ul>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
