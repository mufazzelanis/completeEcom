@extends('admin.settings.layout')
@section('settings-title', 'Business Profile')

@section('settings-content')
@php
    $input = 'w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500';
    $label = 'block text-sm font-medium text-gray-700 mb-1';
    $hint = 'text-xs text-gray-400 mt-1';
    $titles = ['name' => 'Shop name', 'phone' => 'Phone', 'email' => 'Email', 'address' => 'Address'];
    $logoUrl = setting_file_url('site_logo') ?: setting_file_url('invoice_logo');
@endphp

<div class="bg-orange-50 border border-orange-200 rounded-xl p-4 text-sm text-orange-900">
    <b>Change it once, updated everywhere.</b> Your website header and footer, Contact page, top bar, invoices, emails, SMS and the details Google reads all use what you save here.
</div>

@if ($differences)
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-900 space-y-2">
        <p class="font-semibold">These are different in different places right now. Saving this page makes them the same.</p>
        @foreach ($differences as $field => $places)
            <div>
                <span class="font-medium">{{ $titles[$field] }}:</span>
                @foreach ($places as $place => $value)
                    <span class="block sm:inline sm:mr-3 text-xs"><span class="text-amber-700">{{ $place }}:</span> {{ $value }}</span>
                @endforeach
            </div>
        @endforeach
    </div>
@endif

@if ($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm">
        <ul class="list-disc list-inside">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<form method="POST" action="{{ route('admin.business.update') }}" enctype="multipart/form-data" data-turbo="false" class="space-y-5">
    @csrf

    <div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
        <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Your shop</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="{{ $label }}">Shop name <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $profile['name']) }}" required maxlength="120" class="{{ $input }}">
                <p class="{{ $hint }}">If it changes, the old name in your SEO title and copyright line changes too.</p>
            </div>
            <div>
                <label class="{{ $label }}">Tagline</label>
                <input type="text" name="tagline" value="{{ old('tagline', $profile['tagline']) }}" maxlength="200" class="{{ $input }}">
            </div>
            <div class="md:col-span-2">
                <label class="{{ $label }}">Logo</label>
                <div class="flex items-center gap-4">
                    @if ($logoUrl)<img src="{{ $logoUrl }}" alt="Current logo" class="h-12 max-w-[160px] object-contain rounded border p-1 bg-gray-50">@endif
                    <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" class="block w-full text-xs text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100">
                </div>
                <p class="{{ $hint }}">A new file replaces the logo on the website and on invoices. The photo watermark has its own logo in <a href="{{ route('admin.watermark.index') }}" class="underline">Settings → Watermark</a>.</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
        <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">How customers reach you</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="{{ $label }}">Phone</label>
                <input type="text" name="phone" value="{{ old('phone', $profile['phone']) }}" maxlength="40" placeholder="+880 1700-000000" class="{{ $input }}">
            </div>
            <div>
                <label class="{{ $label }}">Email</label>
                <input type="email" name="email" value="{{ old('email', $profile['email']) }}" maxlength="255" placeholder="support@yourshop.com" class="{{ $input }}">
                <p class="{{ $hint }}">The address customers see. The address emails are sent from stays in Email &amp; SMTP.</p>
            </div>
            <div>
                <label class="{{ $label }}">WhatsApp</label>
                <input type="text" name="whatsapp" value="{{ old('whatsapp', $profile['whatsapp']) }}" maxlength="255" placeholder="01700000000 or https://wa.me/8801700000000" class="{{ $input }}">
            </div>
            <div>
                <label class="{{ $label }}">Business hours</label>
                <input type="text" name="hours" value="{{ old('hours', $profile['hours']) }}" maxlength="120" placeholder="Sat–Thu, 10am–8pm" class="{{ $input }}">
            </div>
            <div class="md:col-span-2">
                <label class="{{ $label }}">Address</label>
                <textarea name="address" rows="2" maxlength="500" placeholder="House, road, area, city" class="{{ $input }}">{{ old('address', $profile['address']) }}</textarea>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
        <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">On invoices</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="{{ $label }}">Registered company name</label>
                <input type="text" name="legal_name" value="{{ old('legal_name', $profile['legal_name']) }}" maxlength="150" class="{{ $input }}">
                <p class="{{ $hint }}">Only if it differs from the shop name. Left empty, invoices use the shop name.</p>
            </div>
            <div>
                <label class="{{ $label }}">BIN / Tax ID</label>
                <input type="text" name="tax" value="{{ old('tax', $profile['tax']) }}" maxlength="100" placeholder="BIN 000000000-0000" class="{{ $input }}">
            </div>
        </div>
        <p class="text-xs text-gray-400">Invoice colours, terms and signature are in <a href="{{ route('admin.settings.show', 'invoice') }}" class="underline">Settings → Invoice</a>.</p>
    </div>

    <div class="flex justify-end">
        <button type="submit" class="px-6 py-2 bg-orange-600 text-white rounded-lg text-sm font-semibold hover:bg-orange-700 transition">Save everywhere</button>
    </div>
</form>
@endsection
