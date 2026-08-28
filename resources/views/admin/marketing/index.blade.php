@extends('layouts.admin')
@section('title', 'Marketing & Promotions')

@section('content')
<div class="mb-6">
    <h1 class="text-xl font-bold text-gray-800">Marketing & Promotions</h1>
    <p class="text-sm text-gray-500 mt-0.5">Manage all promotional tools from one place</p>
</div>

<div class="grid grid-cols-2 lg:grid-cols-3 gap-4">

    {{-- Coupons --}}
    <a href="{{ route('admin.coupons.index') }}" class="bg-white rounded-2xl shadow-sm p-6 hover:shadow-md transition group">
        <div class="flex items-start justify-between mb-4">
            <div class="w-12 h-12 bg-indigo-100 rounded-2xl flex items-center justify-center group-hover:bg-indigo-200 transition">
                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
            </div>
            <span class="text-2xl font-bold text-gray-800">{{ \App\Models\Coupon::where('is_active',true)->count() }}</span>
        </div>
        <h3 class="font-semibold text-gray-800">Coupons</h3>
        <p class="text-sm text-gray-500 mt-1">Shared discount codes for all customers</p>
    </a>

    {{-- Promo Codes --}}
    <a href="{{ route('admin.promo-codes.index') }}" class="bg-white rounded-2xl shadow-sm p-6 hover:shadow-md transition group">
        <div class="flex items-start justify-between mb-4">
            <div class="w-12 h-12 bg-purple-100 rounded-2xl flex items-center justify-center group-hover:bg-purple-200 transition">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
            </div>
            <span class="text-2xl font-bold text-gray-800">{{ \App\Models\PromoCodeBatch::count() }}</span>
        </div>
        <h3 class="font-semibold text-gray-800">Promo Codes</h3>
        <p class="text-sm text-gray-500 mt-1">Bulk unique single-use codes per campaign</p>
    </a>

    {{-- Flash Sales --}}
    <a href="{{ route('admin.flash-sales.index') }}" class="bg-white rounded-2xl shadow-sm p-6 hover:shadow-md transition group">
        <div class="flex items-start justify-between mb-4">
            <div class="w-12 h-12 bg-red-100 rounded-2xl flex items-center justify-center group-hover:bg-red-200 transition">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            </div>
            @php $live = \App\Models\FlashSale::where('is_active',true)->where('starts_at','<=',now())->where('ends_at','>=',now())->count(); @endphp
            <span class="text-2xl font-bold {{ $live > 0 ? 'text-red-600' : 'text-gray-800' }}">{{ $live > 0 ? 'LIVE' : \App\Models\FlashSale::count() }}</span>
        </div>
        <h3 class="font-semibold text-gray-800">Flash Sales</h3>
        <p class="text-sm text-gray-500 mt-1">Time-limited deals with countdown timers</p>
        @if($live > 0)<span class="inline-flex items-center gap-1 text-xs text-red-600 font-medium mt-1"><span class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></span> {{ $live }} sale(s) live now</span>@endif
    </a>

    {{-- Bundle Offers --}}
    <a href="{{ route('admin.bundles.index') }}" class="bg-white rounded-2xl shadow-sm p-6 hover:shadow-md transition group">
        <div class="flex items-start justify-between mb-4">
            <div class="w-12 h-12 bg-blue-100 rounded-2xl flex items-center justify-center group-hover:bg-blue-200 transition">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </div>
            <span class="text-2xl font-bold text-gray-800">{{ \App\Models\Product::where('type','bundle')->count() }}</span>
        </div>
        <h3 class="font-semibold text-gray-800">Bundle Offers</h3>
        <p class="text-sm text-gray-500 mt-1">Product bundles with group discounts</p>
    </a>

    {{-- Cross-Sell / Upsell --}}
    <a href="{{ route('admin.cross-sell.index') }}" class="bg-white rounded-2xl shadow-sm p-6 hover:shadow-md transition group">
        <div class="flex items-start justify-between mb-4">
            <div class="w-12 h-12 bg-green-100 rounded-2xl flex items-center justify-center group-hover:bg-green-200 transition">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
            </div>
            <span class="text-2xl font-bold text-gray-800">{{ \App\Models\ProductRecommendation::count() }}</span>
        </div>
        <h3 class="font-semibold text-gray-800">Cross-Sell / Upsell</h3>
        <p class="text-sm text-gray-500 mt-1">Suggest related or higher-value products</p>
    </a>

    {{-- Referral Program --}}
    <a href="{{ route('admin.referrals.index') }}" class="bg-white rounded-2xl shadow-sm p-6 hover:shadow-md transition group">
        <div class="flex items-start justify-between mb-4">
            <div class="w-12 h-12 bg-orange-100 rounded-2xl flex items-center justify-center group-hover:bg-orange-200 transition">
                <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            @php $pending = \App\Models\ReferralReward::where('status','pending')->count(); @endphp
            <span class="text-2xl font-bold {{ $pending > 0 ? 'text-orange-600' : 'text-gray-800' }}">{{ \App\Models\ReferralCode::count() }}</span>
        </div>
        <h3 class="font-semibold text-gray-800">Referral Program</h3>
        <p class="text-sm text-gray-500 mt-1">Customer referral codes & reward management</p>
        @if($pending > 0)<span class="text-xs text-orange-600 font-medium mt-1 block">{{ $pending }} reward(s) pending approval</span>@endif
    </a>

    {{-- Email Marketing --}}
    <a href="{{ route('admin.email-campaigns.index') }}" class="bg-white rounded-2xl shadow-sm p-6 hover:shadow-md transition group lg:col-span-3 col-span-2">
        <div class="flex items-center gap-6">
            <div class="w-12 h-12 bg-teal-100 rounded-2xl flex items-center justify-center group-hover:bg-teal-200 transition flex-shrink-0">
                <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </div>
            <div class="flex-1">
                <h3 class="font-semibold text-gray-800">Email Marketing</h3>
                <p class="text-sm text-gray-500 mt-1">Compose and send email campaigns to your customers</p>
            </div>
            <div class="flex gap-6 text-center flex-shrink-0">
                <div><p class="text-2xl font-bold text-gray-800">{{ \App\Models\EmailCampaign::count() }}</p><p class="text-xs text-gray-500">Total</p></div>
                <div><p class="text-2xl font-bold text-green-600">{{ \App\Models\EmailCampaign::where('status','sent')->count() }}</p><p class="text-xs text-gray-500">Sent</p></div>
                <div><p class="text-2xl font-bold text-purple-600">{{ \App\Models\EmailCampaign::where('status','scheduled')->count() }}</p><p class="text-xs text-gray-500">Scheduled</p></div>
            </div>
        </div>
    </a>

    {{-- Newsletter Subscribers --}}
    <a href="{{ route('admin.newsletter.index') }}" class="bg-white rounded-2xl shadow-sm p-6 hover:shadow-md transition group">
        <div class="flex items-start justify-between mb-4">
            <div class="w-12 h-12 bg-pink-100 rounded-2xl flex items-center justify-center group-hover:bg-pink-200 transition">
                <svg class="w-6 h-6 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </div>
            <span class="text-2xl font-bold text-gray-800">{{ \App\Models\NewsletterSubscriber::where('is_active', true)->count() }}</span>
        </div>
        <h3 class="font-semibold text-gray-800">Newsletter Subscribers</h3>
        <p class="text-sm text-gray-500 mt-1">Emails collected from the storefront footer signup</p>
    </a>
</div>
@endsection
