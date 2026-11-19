@extends('layouts.admin')
@section('title', 'New Promo Campaign')

@section('content')
<div class="flex items-center gap-4 mb-6">
    <a href="{{ route('admin.promo-codes.index') }}" class="text-indigo-600 hover:text-indigo-700 text-sm flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg> Back
    </a>
    <h1 class="text-xl font-bold text-gray-800">New Promo Code Campaign</h1>
</div>

<div x-data="{
        mode: @js(old('generate_count', 100) == 1 ? 'single' : 'bulk'),
        name: @js(old('name', '')),
        prefix: @js(old('prefix', '')),
        discountType: @js(old('discount_type', 'percentage')),
        discountValue: @js(old('discount_value', '')),
        generateCount: @js((string) old('generate_count', 100)),
        minOrder: @js((string) old('min_order_amount', 0)),
        expiresAt: @js(old('expires_at', '')),
        isActive: {{ old('is_active', 1) ? 'true' : 'false' }},
        submitting: false,
        setMode(m) {
            this.mode = m;
            if (m === 'single') { this.generateCount = '1'; }
            else if (this.generateCount === '1') { this.generateCount = '100'; }
        },
        get previewPrefix() { return (this.prefix || '').toUpperCase().replace(/[^A-Z0-9]/g, ''); },
        get previewCode() {
            if (this.mode === 'single') return this.previewPrefix || 'YOURCODE';
            return (this.previewPrefix ? this.previewPrefix + '-' : '') + 'X7K2-M9QP';
        },
        get discountLabel() {
            if (!this.discountValue) return '—';
            return this.discountType === 'percentage' ? this.discountValue + '% off' : '৳' + this.discountValue + ' off';
        },
     }"
     class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

    {{-- Form --}}
    <form action="{{ route('admin.promo-codes.store') }}" method="POST" @submit="submitting = true"
          class="lg:col-span-2 bg-white rounded-2xl shadow-sm p-6 space-y-7">
        @csrf

        {{-- Campaign details --}}
        <div>
            <h2 class="text-sm font-semibold text-gray-800 flex items-center gap-2 mb-4">
                <span class="w-6 h-6 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </span>
                Campaign Details
            </h2>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Code Type</label>
                    <div class="grid grid-cols-2 gap-3">
                        <button type="button" @click="setMode('single')"
                            class="text-left rounded-xl border-2 px-4 py-3 transition"
                            :class="mode === 'single' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-gray-300'">
                            <p class="text-sm font-medium" :class="mode === 'single' ? 'text-indigo-700' : 'text-gray-700'">My own code</p>
                            <p class="text-xs text-gray-400 mt-0.5">Type one exact code, e.g. "BF26"</p>
                        </button>
                        <button type="button" @click="setMode('bulk')"
                            class="text-left rounded-xl border-2 px-4 py-3 transition"
                            :class="mode === 'bulk' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-gray-300'">
                            <p class="text-sm font-medium" :class="mode === 'bulk' ? 'text-indigo-700' : 'text-gray-700'">Bulk generate</p>
                            <p class="text-xs text-gray-400 mt-0.5">Many unique random codes</p>
                        </button>
                    </div>
                    <p x-show="mode === 'single'" x-cloak class="text-xs text-amber-600 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2 mt-2.5">
                        This code works for the <b>first customer</b> who uses it, then it's used up — like every code here, it's single-use.
                        Want one code many different customers can each use once (e.g. a sitewide "BF26")?
                        Use <a href="{{ route('admin.coupons.create') }}" class="underline font-medium">Coupons</a> instead.
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Campaign Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" x-model="name" required placeholder="e.g. Black Friday 2026"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                    @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <template x-if="mode === 'single'">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Coupon Code <span class="text-red-500">*</span></label>
                        <input type="text" name="prefix" x-model="prefix" required placeholder="e.g. BF26" maxlength="10"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm font-mono uppercase focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                        <p class="text-xs text-gray-400 mt-1">Customers will type exactly: <span class="font-mono text-gray-500" x-text="previewCode"></span></p>
                        @error('prefix')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        <input type="hidden" name="generate_count" value="1">
                    </div>
                </template>

                <template x-if="mode === 'bulk'">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Code Prefix <span class="text-gray-400 font-normal">(optional)</span></label>
                            <input type="text" name="prefix" x-model="prefix" placeholder="BF26" maxlength="10"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm font-mono uppercase focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                            <p class="text-xs text-gray-400 mt-1">Codes look like: <span class="font-mono text-gray-500" x-text="previewCode"></span></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Number of Codes <span class="text-red-500">*</span></label>
                            <input type="number" name="generate_count" x-model="generateCount" required min="2" max="10000"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                            <p class="text-xs text-gray-400 mt-1">Max 10,000 per batch</p>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div class="border-t border-gray-100"></div>

        {{-- Discount rules --}}
        <div>
            <h2 class="text-sm font-semibold text-gray-800 flex items-center gap-2 mb-4">
                <span class="w-6 h-6 rounded-lg bg-purple-100 text-purple-600 flex items-center justify-center">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </span>
                Discount Rules
            </h2>

            <label class="block text-sm font-medium text-gray-700 mb-2">Discount Type <span class="text-red-500">*</span></label>
            <input type="hidden" name="discount_type" x-model="discountType">
            <div class="grid grid-cols-2 gap-3 mb-4">
                <button type="button" @click="discountType = 'percentage'"
                    class="flex items-center justify-center gap-2 rounded-xl border-2 px-4 py-3 text-sm font-medium transition"
                    :class="discountType === 'percentage' ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-gray-200 text-gray-500 hover:border-gray-300'">
                    <span class="text-base font-bold">%</span> Percentage
                </button>
                <button type="button" @click="discountType = 'fixed'"
                    class="flex items-center justify-center gap-2 rounded-xl border-2 px-4 py-3 text-sm font-medium transition"
                    :class="discountType === 'fixed' ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-gray-200 text-gray-500 hover:border-gray-300'">
                    <span class="text-base font-bold">৳</span> Fixed Amount
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Discount Value <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm font-semibold" x-text="discountType === 'percentage' ? '%' : '৳'"></span>
                        <input type="number" name="discount_value" x-model="discountValue" required step="0.01" min="0.01"
                            class="w-full border border-gray-200 rounded-xl pl-9 pr-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                    </div>
                    @error('discount_value')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Minimum Order Amount</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm font-semibold">৳</span>
                        <input type="number" name="min_order_amount" x-model="minOrder" step="0.01" min="0"
                            class="w-full border border-gray-200 rounded-xl pl-9 pr-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                    </div>
                </div>
            </div>
        </div>

        <div class="border-t border-gray-100"></div>

        {{-- Limits & status --}}
        <div>
            <h2 class="text-sm font-semibold text-gray-800 flex items-center gap-2 mb-4">
                <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
                Expiry &amp; Status
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Expiry Date <span class="text-gray-400 font-normal">(optional)</span></label>
                    <input type="date" name="expires_at" x-model="expiresAt"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                </div>
            </div>

            <div class="flex items-center justify-between bg-gray-50 rounded-xl px-4 py-3">
                <div>
                    <p class="text-sm font-medium text-gray-700">Activate immediately</p>
                    <p class="text-xs text-gray-400">Codes are usable as soon as they're generated.</p>
                </div>
                <input type="hidden" name="is_active" :value="isActive ? '1' : '0'">
                <button type="button" @click="isActive = !isActive" role="switch" :aria-checked="isActive"
                    class="relative inline-flex h-6 w-11 flex-shrink-0 items-center rounded-full transition-colors duration-200"
                    :class="isActive ? 'bg-green-500' : 'bg-gray-300'">
                    <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform duration-200"
                          :class="isActive ? 'translate-x-[22px]' : 'translate-x-[2px]'"></span>
                </button>
            </div>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" :disabled="submitting"
                class="bg-indigo-600 text-white px-6 py-2.5 rounded-xl text-sm font-medium hover:bg-indigo-700 transition disabled:opacity-60 flex items-center gap-2 min-w-[150px] justify-center">
                <svg x-show="submitting" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                <span x-text="submitting ? (mode === 'single' ? 'Creating…' : 'Generating…') : (mode === 'single' ? 'Create Code' : 'Generate Codes')"></span>
            </button>
            <a href="{{ route('admin.promo-codes.index') }}" class="bg-gray-100 text-gray-600 px-6 py-2.5 rounded-xl text-sm font-medium hover:bg-gray-200 transition">Cancel</a>
        </div>
    </form>

    {{-- Live preview --}}
    <div class="lg:sticky lg:top-6 space-y-4">
        <div class="bg-gradient-to-br from-indigo-600 to-purple-600 rounded-2xl shadow-sm p-6 text-white overflow-hidden relative">
            <div class="absolute -right-6 -top-6 w-28 h-28 rounded-full bg-white/10"></div>
            <div class="absolute -right-2 -bottom-8 w-20 h-20 rounded-full bg-white/10"></div>
            <p class="text-xs font-medium text-indigo-200 uppercase tracking-wider mb-1">Live Preview</p>
            <p class="text-lg font-bold mb-4 truncate" x-text="name || 'Untitled Campaign'"></p>

            <div class="bg-white/15 backdrop-blur rounded-xl px-4 py-3 mb-4 border border-white/20">
                <p class="text-[11px] text-indigo-200 uppercase tracking-wide mb-1">Sample code</p>
                <p class="font-mono text-base font-semibold tracking-wide" x-text="previewCode"></p>
            </div>

            <div class="space-y-2.5 text-sm">
                <div class="flex items-center justify-between">
                    <span class="text-indigo-200">Discount</span>
                    <span class="font-semibold" x-text="discountLabel"></span>
                </div>
                <div class="flex items-center justify-between" x-show="mode === 'bulk'">
                    <span class="text-indigo-200">Codes generated</span>
                    <span class="font-semibold" x-text="generateCount || 0"></span>
                </div>
                <div class="flex items-center justify-between" x-show="minOrder > 0">
                    <span class="text-indigo-200">Min. order</span>
                    <span class="font-semibold">৳<span x-text="minOrder"></span></span>
                </div>
                <div class="flex items-center justify-between" x-show="expiresAt">
                    <span class="text-indigo-200">Expires</span>
                    <span class="font-semibold" x-text="expiresAt"></span>
                </div>
                <div class="flex items-center justify-between pt-2 border-t border-white/20">
                    <span class="text-indigo-200">Status</span>
                    <span class="inline-flex items-center gap-1.5 font-semibold" :class="isActive ? 'text-emerald-300' : 'text-gray-300'">
                        <span class="w-1.5 h-1.5 rounded-full" :class="isActive ? 'bg-emerald-300' : 'bg-gray-300'"></span>
                        <span x-text="isActive ? 'Active' : 'Inactive'"></span>
                    </span>
                </div>
            </div>
        </div>

        <div class="bg-amber-50 border border-amber-100 rounded-2xl p-4 text-xs text-amber-700 flex gap-2.5">
            <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p>Each code is single-use and unique. Customers enter any code from this batch at checkout — once used, it can't be reused.</p>
        </div>
    </div>
</div>
@endsection
