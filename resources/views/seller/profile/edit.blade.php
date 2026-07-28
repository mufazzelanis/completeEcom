@extends('layouts.seller')
@section('title', 'My Profile')
@section('pageTitle', 'My Profile')

@php
    // While a change is pending/rejected, the form should show what was PROPOSED
    // (so the seller sees exactly what they submitted), falling back to the live
    // value for anything not part of that proposal.
    $pending = $vendor->pending_changes ?? [];
    $val = fn (string $field, $default = '') => old($field, data_get($pending, $field, $vendor->$field) ?? $default);
    $payoutDetails = data_get($pending, 'payout_details', $vendor->payout_details) ?? [];
@endphp

@section('content')
<h1 class="text-xl font-bold text-gray-800 mb-6">My Profile</h1>

@if($vendor->profile_status === 'pending')
<div class="bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-xl px-4 py-3 text-sm mb-6">
    <strong>Your profile changes are pending admin review.</strong> The form below shows what you submitted — it will go live once approved. You can still edit and resubmit while it's pending.
</div>
@elseif($vendor->profile_status === 'rejected')
<div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm mb-6">
    <strong>Your last profile update was rejected.</strong>
    @if($vendor->profile_rejection_reason) Reason: {{ $vendor->profile_rejection_reason }} @endif
    Please revise and resubmit below.
</div>
@endif

<form action="{{ route('seller.profile.update') }}" method="POST" enctype="multipart/form-data">
    @csrf @method('PUT')

    <div class="bg-white rounded-2xl shadow-sm p-6 space-y-4 max-w-2xl">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Business Name <span class="text-red-500">*</span></label>
            <input type="text" name="business_name" value="{{ $val('business_name') }}" required
                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            @error('business_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                <input type="text" name="phone" value="{{ $val('phone') }}"
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Business Email</label>
                <input type="email" name="email" value="{{ $val('email') }}"
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Website</label>
            <input type="url" name="website" value="{{ $val('website') }}" placeholder="https://yourshop.com"
                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            @error('website')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Business Description</label>
            <textarea name="description" rows="4" maxlength="2000"
                class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ $val('description') }}</textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Shop Logo</label>
            @if($vendor->logo)
            <img src="{{ Storage::url($vendor->logo) }}" class="w-16 h-16 rounded-xl object-cover mb-2 border border-gray-100">
            @endif
            <input type="file" name="logo" accept="image/*" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm">
            @error('logo')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="border-t border-gray-100 pt-4">
            <p class="text-sm font-semibold text-gray-700 mb-3">Payout Details</p>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Method</label>
                    <input type="text" name="payout_method" value="{{ $val('payout_method') }}" placeholder="e.g. bKash, Bank Transfer"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Account Name</label>
                    <input type="text" name="payout_account_name" value="{{ old('payout_account_name', $payoutDetails['account_name'] ?? '') }}"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Account / Number</label>
                    <input type="text" name="payout_account_number" value="{{ old('payout_account_number', $payoutDetails['account_number'] ?? '') }}"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>
        </div>

        <div class="bg-indigo-50 text-indigo-700 text-xs rounded-xl px-4 py-3">
            Profile changes go live only after admin approval — your current public shop page won't change until then.
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="bg-indigo-600 text-white px-6 py-2.5 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition">
                Submit for Approval
            </button>
        </div>
    </div>
</form>
@endsection
