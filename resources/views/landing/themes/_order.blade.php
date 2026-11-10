{{-- Order form for the themed landing pages — the same fields, names and checks as the classic
     page (LandingPageController@order), with quantity packs and a live total. Turbo sends the
     form without reloading the page; the button locks on the first tap so a slow connection
     can't place the order twice (the server de-duplicates as well). --}}
@php
    $unit = (float) ($price ?? 0);
    $zones = $lp->delivery_zones ?: [];
    $orderImg = $productImg;
@endphp
<section id="order-form" class="lt-sec lt-order"
    x-data="{
        qty: {{ max(1, min(5, (int) old('quantity', 1))) }},
        unit: {{ $unit }},
        zones: {{ Js::from($zones) }},
        zoneIndex: {{ $zones ? (int) old('delivery_zone', 0) : 'null' }},
        get zoneCharge() { return (this.zoneIndex !== null && this.zones[this.zoneIndex]) ? parseFloat(this.zones[this.zoneIndex].charge || 0) : 0; },
        get subtotal() { return this.unit * this.qty; },
        get total() { return this.subtotal + this.zoneCharge; },
        bn(n) { return String(n).replace(/[0-9]/g, d => '০১২৩৪৫৬৭৮৯'[d]); },
        money(n) { return {{ Js::from($currencySymbol) }} + Number(n).toLocaleString('en-US', { minimumFractionDigits: {{ $decimals }}, maximumFractionDigits: {{ $decimals }} }); },
        trackInitiateCheckout() {
            if (typeof fbq === 'function') {
                fbq('track', 'InitiateCheckout', { value: this.total, currency: {{ Js::from($currencyCode) }}, content_type: 'product', content_ids: [{{ Js::from($trackProductId) }}] });
            }
            if (typeof gtag === 'function') {
                gtag('event', 'begin_checkout', { currency: {{ Js::from($currencyCode) }}, value: this.total, items: [{ item_id: {{ Js::from($trackProductId) }}, item_name: {{ Js::from($lp->product?->name ?: $lp->title) }}, price: this.unit }] });
            }
        },
    }"
    x-init="new IntersectionObserver((entries, obs) => { if (entries[0].isIntersecting) { trackInitiateCheckout(); obs.disconnect(); } }, { threshold: 0.3 }).observe($el)">
    <div class="lt-wrap">
        <div class="lt-order-grid">
            <aside class="lt-sum reveal">
                @if($orderImg)
                    <img class="lt-sum-img" src="{{ image_thumb($orderImg, 400) }}" alt="{{ $lp->product?->name ?: $lp->title }}" width="240" height="240" loading="lazy" decoding="async">
                @endif
                <div style="min-width:0">
                    <p class="lt-sum-title">{{ $lp->product?->name ?: $lp->title }}</p>
                    @if($price)
                    <div class="lt-price">{{ format_currency($price) }}</div>
                    @if($was)<span class="lt-was" style="font-size:.95rem">{{ format_currency($was) }}</span>@endif
                    @endif
                    <div class="lt-sum-list">
                        @forelse(array_slice($badges, 0, 4) as $b)
                            <span>{!! $checkSvg !!}{{ $b['text'] }}</span>
                        @empty
                            <span>{!! $checkSvg !!}১০০% অরিজিনাল প্রোডাক্ট</span>
                            <span>{!! $checkSvg !!}ক্যাশ অন ডেলিভারি</span>
                            <span>{!! $checkSvg !!}সারা দেশে হোম ডেলিভারি</span>
                        @endforelse
                    </div>
                </div>
            </aside>

            <div class="lt-form reveal">
                <h2 class="lt-form-title">অর্ডার করতে নিচের তথ্য দিন</h2>
                <p class="lt-form-sub">মাত্র ৩০ সেকেন্ডে অর্ডার — আমরা কল করে নিশ্চিত করবো</p>

                @if($errors->any())
                <div class="lt-errors" role="alert">
                    <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
                <script>document.addEventListener('DOMContentLoaded', () => document.getElementById('order-form')?.scrollIntoView({ block: 'start' }));</script>
                @endif

                <form action="{{ route('landing.order', $lp) }}" method="POST"
                    x-data="{ sending: false }" @pageshow.window="sending = false" @submit="if (sending) { $event.preventDefault(); return; } sending = true">
                    @csrf

                    <div class="lt-field">
                        <label class="lt-label" for="lt-name">আপনার নাম <em>*</em></label>
                        <div class="lt-input-wrap">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            <input id="lt-name" class="lt-input" type="text" name="name" value="{{ old('name') }}" required autocomplete="name" placeholder="যেমন: রহিম আহমেদ">
                        </div>
                    </div>

                    <div class="lt-field">
                        <label class="lt-label" for="lt-phone">মোবাইল নম্বর <em>*</em></label>
                        <div class="lt-input-wrap">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            <input id="lt-phone" class="lt-input" type="tel" inputmode="tel" name="phone" value="{{ old('phone') }}" required autocomplete="tel" placeholder="01XXXXXXXXX">
                        </div>
                        @include('partials.phone-live-check', ['selector' => 'input[name=phone]', 'bn' => true])
                    </div>

                    @if($lp->collect_address)
                    <div class="lt-field">
                        <label class="lt-label" for="lt-address">সম্পূর্ণ ঠিকানা @if($lp->require_address)<em>*</em>@endif</label>
                        <textarea id="lt-address" class="lt-input" name="address" rows="2" autocomplete="street-address" placeholder="বাসা/রোড, এলাকা, থানা, জেলা" {{ $lp->require_address ? 'required' : '' }}>{{ old('address') }}</textarea>
                    </div>
                    @endif

                    @if($zones)
                    <div class="lt-field">
                        <span class="lt-label">ডেলিভারি এলাকা</span>
                        @foreach($zones as $i => $zone)
                        <label class="lt-zone" :class="{ 'is-on': zoneIndex === {{ $i }} }">
                            <span><input type="radio" x-model.number="zoneIndex" value="{{ $i }}">{{ $zone['label'] }}</span>
                            <b>{{ $currencySymbol }}{{ number_format((float) $zone['charge'], $decimals) }}</b>
                        </label>
                        @endforeach
                        <input type="hidden" name="delivery_zone" :value="zoneIndex">
                    </div>
                    @endif

                    <div class="lt-field">
                        <span class="lt-label">পরিমাণ বেছে নিন</span>
                        <div class="lt-packs" role="radiogroup" aria-label="পরিমাণ">
                            @foreach([1, 2, 3] as $n)
                            <button type="button" class="lt-pack" :class="{ 'is-on': qty === {{ $n }} }" @click="qty = {{ $n }}" role="radio" :aria-checked="qty === {{ $n }}">
                                @if($n === 2)<small>জনপ্রিয়</small>@endif
                                <b>{{ $bnDigits($n) }}টি</b>
                                @if($unit)<span x-text="money(unit * {{ $n }})">{{ format_currency($unit * $n) }}</span>@endif
                            </button>
                            @endforeach
                        </div>
                        <div class="lt-step">
                            <button type="button" @click="qty = Math.max(1, qty - 1)" :disabled="qty <= 1" aria-label="কমান">−</button>
                            <b x-text="bn(qty)">১</b>
                            <button type="button" @click="qty = Math.min(5, qty + 1)" :disabled="qty >= 5" aria-label="বাড়ান">+</button>
                            <span x-show="qty >= 5" x-cloak>প্রতি অর্ডারে সর্বোচ্চ ৫টি</span>
                        </div>
                        <input type="hidden" name="quantity" :value="qty" value="1">
                    </div>

                    @foreach($lp->order_form_fields ?? [] as $field)
                    <div class="lt-field">
                        <label class="lt-label" for="lt-f-{{ $field['key'] }}">{{ $field['label'] }} @if($field['required'])<em>*</em>@endif</label>
                        @if($field['type'] === 'textarea')
                            <textarea id="lt-f-{{ $field['key'] }}" class="lt-input" name="custom[{{ $field['key'] }}]" rows="2" {{ $field['required'] ? 'required' : '' }}>{{ old('custom.' . $field['key']) }}</textarea>
                        @elseif($field['type'] === 'select')
                            <select id="lt-f-{{ $field['key'] }}" class="lt-input" name="custom[{{ $field['key'] }}]" {{ $field['required'] ? 'required' : '' }}>
                                <option value="">নির্বাচন করুন…</option>
                                @foreach($field['options'] ?? [] as $opt)
                                    <option value="{{ $opt }}" {{ old('custom.' . $field['key']) === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                            </select>
                        @elseif($field['type'] === 'checkbox')
                            <label class="lt-zone" style="justify-content:flex-start">
                                <input id="lt-f-{{ $field['key'] }}" type="checkbox" name="custom[{{ $field['key'] }}]" value="1" {{ old('custom.' . $field['key']) ? 'checked' : '' }}>
                                <span>হ্যাঁ</span>
                            </label>
                        @else
                            <input id="lt-f-{{ $field['key'] }}" class="lt-input" type="{{ $field['type'] }}" name="custom[{{ $field['key'] }}]" value="{{ old('custom.' . $field['key']) }}" {{ $field['required'] ? 'required' : '' }}>
                        @endif
                    </div>
                    @endforeach

                    @if($unit)
                    <div class="lt-total" aria-live="polite">
                        <div><span>পণ্যের মূল্য (<span x-text="bn(qty)">১</span>টি)</span><span x-text="money(subtotal)">{{ format_currency($unit) }}</span></div>
                        @if($zones)
                        <div><span>ডেলিভারি চার্জ</span><span x-text="money(zoneCharge)">{{ format_currency((float) ($zones[0]['charge'] ?? 0)) }}</span></div>
                        @endif
                        <div class="is-grand"><span>সর্বমোট</span><span x-text="money(total)">{{ format_currency($unit + (float) ($zones[0]['charge'] ?? 0)) }}</span></div>
                    </div>
                    @endif

                    <button type="submit" class="lt-btn lt-btn--block" :disabled="sending" :aria-busy="sending">
                        <span x-show="!sending">{{ $lp->order_button_text }}</span>
                        <span x-show="sending" x-cloak style="display:inline-flex;align-items:center;gap:8px">
                            <svg class="lt-spin" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity=".25"/><path fill="currentColor" opacity=".8" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                            অর্ডার হচ্ছে…
                        </span>
                    </button>
                    <p class="lt-cod">🔒 আপনার তথ্য সুরক্ষিত · 💵 পণ্য হাতে পেয়ে টাকা দিন</p>
                </form>
            </div>
        </div>
    </div>
</section>
