@extends('layouts.account')
@section('title', 'Become a Seller')
@section('pageTitle', 'Become a Seller')

@section('content')
<div class="flex items-center gap-4 mb-5">
    <h1 class="text-xl font-bold text-gray-800">Become a Seller</h1>
</div>

@if(session('success'))<div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">{{ session('success') }}</div>@endif
@if(session('error'))<div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">{{ session('error') }}</div>@endif

<div class="bg-white rounded-2xl shadow-sm p-6 max-w-2xl">
    @if($vendor && $vendor->status !== 'needs_correction')
        <p class="text-sm text-gray-600 mb-3">You applied to become a seller as <strong>{{ $vendor->business_name }}</strong>.</p>
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $vendor->statusBadge() }}">{{ ucfirst($vendor->status) }}</span>
        @if($vendor->status === 'rejected' && $vendor->rejection_reason)
            <p class="text-sm text-red-600 mt-3">Reason: {{ $vendor->rejection_reason }}</p>
        @endif
        @if($vendor->status === 'suspended')
            @php
                $supportEmail = setting('contact_email') ?: setting('support_email') ?: setting('company_email');
                $supportPhone = setting('contact_phone') ?: setting('company_phone');
            @endphp
            @if($supportEmail || $supportPhone)
                <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 mt-4 text-sm text-red-700">
                    <p class="font-medium mb-1">Your seller account has been suspended.</p>
                    <p>For more information, please contact us:</p>
                    <ul class="mt-1 space-y-0.5">
                        @if($supportEmail)<li>Email: <a href="mailto:{{ $supportEmail }}" class="underline hover:text-red-800">{{ $supportEmail }}</a></li>@endif
                        @if($supportPhone)<li>Phone: <a href="tel:{{ $supportPhone }}" class="underline hover:text-red-800">{{ $supportPhone }}</a></li>@endif
                    </ul>
                </div>
            @endif
        @endif
    @else
        @if($vendor && $vendor->status === 'needs_correction')
        <div class="bg-orange-50 border border-orange-200 text-orange-800 rounded-xl px-4 py-3 text-sm mb-4">
            <strong>Admin requested a correction:</strong> {{ $vendor->correction_notes }}
        </div>
        @endif

        <form action="{{ route('vendor.apply.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4"
              x-data="{ docType: '{{ old('document_type', $vendor->document_type ?? 'nid') }}' }">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Business Name <span class="text-red-500">*</span></label>
                <input type="text" name="business_name" value="{{ old('business_name', $vendor->business_name ?? '') }}" required
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('business_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone', $vendor->phone ?? '') }}"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Business Email</label>
                    <input type="email" name="email" value="{{ old('email', $vendor->email ?? '') }}"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Website (if any)</label>
                <input type="url" name="website" value="{{ old('website', $vendor->website ?? '') }}" placeholder="https://yourshop.com"
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('website')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tell us about your business</label>
                <textarea name="description" rows="5" maxlength="2000"
                    placeholder="What do you sell? Where are your products made or sourced from?"
                    class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none">{{ old('description', $vendor->description ?? '') }}</textarea>
            </div>

            <div class="border-t border-gray-100 pt-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Identity Verification <span class="text-red-500">*</span></label>
                <p class="text-xs text-gray-400 mb-3">
                    @if($vendor && $vendor->status === 'needs_correction')
                        Only re-upload the file(s) admin flagged — leave the rest blank to keep what you already submitted.
                    @else
                        We need one of these to verify your identity before approving your seller account.
                    @endif
                </p>

                <div class="flex gap-2 mb-4">
                    <label class="flex-1 flex items-center justify-center gap-2 border rounded-xl px-4 py-2.5 text-sm cursor-pointer transition"
                           :class="docType === 'nid' ? 'border-indigo-500 bg-indigo-50 text-indigo-700 font-medium' : 'border-gray-200 text-gray-600'">
                        <input type="radio" name="document_type" value="nid" x-model="docType" class="hidden">
                        National ID (NID)
                    </label>
                    <label class="flex-1 flex items-center justify-center gap-2 border rounded-xl px-4 py-2.5 text-sm cursor-pointer transition"
                           :class="docType === 'birth_certificate' ? 'border-indigo-500 bg-indigo-50 text-indigo-700 font-medium' : 'border-gray-200 text-gray-600'">
                        <input type="radio" name="document_type" value="birth_certificate" x-model="docType" class="hidden">
                        Birth Certificate
                    </label>
                </div>
                @error('document_type')<p class="text-red-500 text-xs mb-3">{{ $message }}</p>@enderror

                <div x-show="docType === 'nid'" x-cloak class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">NID Number</label>
                        <input type="text" name="nid_number" value="{{ old('nid_number', $vendor->nid_number ?? '') }}"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @error('nid_number')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">NID Front Side</label>
                            @if($vendor && $vendor->nid_front_image)
                            <a href="{{ route('vendor.apply.document', 'nid_front_image') }}" target="_blank" class="text-xs text-indigo-600 hover:underline block mb-1">View current file</a>
                            @endif
                            <input type="file" name="nid_front_image" accept="image/*"
                                class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm">
                            @error('nid_front_image')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">NID Back Side</label>
                            @if($vendor && $vendor->nid_back_image)
                            <a href="{{ route('vendor.apply.document', 'nid_back_image') }}" target="_blank" class="text-xs text-indigo-600 hover:underline block mb-1">View current file</a>
                            @endif
                            <input type="file" name="nid_back_image" accept="image/*"
                                class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm">
                            @error('nid_back_image')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                <div x-show="docType === 'birth_certificate'" x-cloak>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Birth Certificate Image</label>
                    @if($vendor && $vendor->birth_certificate_image)
                    <a href="{{ route('vendor.apply.document', 'birth_certificate_image') }}" target="_blank" class="text-xs text-indigo-600 hover:underline block mb-1">View current file</a>
                    @endif
                    <input type="file" name="birth_certificate_image" accept="image/*"
                        class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm">
                    @error('birth_certificate_image')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2.5 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition">
                    {{ $vendor && $vendor->status === 'needs_correction' ? 'Resubmit Application' : 'Submit Application' }}
                </button>
            </div>
        </form>
    @endif
</div>
@endsection
