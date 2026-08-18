@extends('layouts.landing')

@section('content')
@if(session('order_success'))
    {{-- Thank You state — same URL as the landing page itself (redirected back here after a
         successful order), rather than a separate route, so there's only ever one link to
         share/remember for this campaign. --}}
    <div class="max-w-lg mx-auto text-center py-20 px-4">
        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        </div>
        <h1 class="text-2xl md:text-3xl font-extrabold text-gray-900 mb-3">{{ $landingPage->thank_you_heading }}</h1>
        @if($landingPage->thank_you_message)
            <p class="text-gray-500 mb-4">{{ $landingPage->thank_you_message }}</p>
        @endif
        <p class="text-sm text-gray-400 font-mono">Order #{{ session('order_success') }}</p>
    </div>
@else
    {{-- Hero --}}
    <div class="text-white" style="background: linear-gradient(150deg, {{ setting('primary_color', '#ea580c') }}, {{ setting('accent_color', '#dc2626') }});">
        <div class="max-w-3xl mx-auto px-4 py-14 md:py-20 text-center">
            @if($landingPage->hero_image)
                <img src="{{ Storage::url($landingPage->hero_image) }}" alt="{{ $landingPage->title }}" class="max-h-72 mx-auto rounded-2xl shadow-2xl mb-8 object-cover w-full sm:w-auto">
            @endif
            @if($landingPage->hero_heading)
                <h1 class="text-3xl md:text-5xl font-extrabold mb-4 leading-tight">{{ $landingPage->hero_heading }}</h1>
            @endif
            @if($landingPage->hero_subheading)
                <p class="text-lg text-white/90 mb-8 max-w-xl mx-auto">{{ $landingPage->hero_subheading }}</p>
            @endif
            <a href="#order-form" class="inline-block bg-white text-gray-900 font-extrabold px-8 py-4 rounded-full text-lg shadow-xl hover:scale-105 active:scale-100 transition">
                {{ $landingPage->order_button_text }}
            </a>
        </div>
    </div>

    {{-- Admin's free-form content --}}
    @if($landingPage->content)
        <div class="max-w-3xl mx-auto px-4 py-12 prose prose-lg max-w-none prose-headings:font-extrabold prose-a:text-orange-600">
            {!! $landingPage->content !!}
        </div>
    @endif

    {{-- Order Form --}}
    <div id="order-form" class="bg-gray-50 py-14 px-4 scroll-mt-16">
        <div class="max-w-lg mx-auto bg-white rounded-2xl shadow-xl p-6 md:p-8">
            <div class="text-center mb-6">
                @if($landingPage->product?->image)
                    <img src="{{ Storage::url($landingPage->product->image) }}" alt="{{ $landingPage->title }}" class="w-20 h-20 object-cover rounded-2xl mx-auto mb-3 shadow-sm">
                @endif
                <h2 class="text-xl font-extrabold text-gray-900">{{ $landingPage->title }}</h2>
                @if($landingPage->effective_price)
                    <p class="text-3xl font-extrabold mt-1" style="color: {{ setting('primary_color', '#ea580c') }};">{{ format_currency($landingPage->effective_price) }}</p>
                @endif
            </div>

            @if($errors->any())
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl p-3">
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
            @endif

            <form action="{{ route('landing.order', $landingPage) }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                        class="w-full border border-gray-300 rounded-xl px-4 py-3 text-base focus:outline-none focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number <span class="text-red-500">*</span></label>
                    <input type="tel" inputmode="tel" name="phone" value="{{ old('phone') }}" required
                        class="w-full border border-gray-300 rounded-xl px-4 py-3 text-base focus:outline-none focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 transition">
                </div>

                @if($landingPage->collect_address)
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Delivery Address @if($landingPage->require_address)<span class="text-red-500">*</span>@endif
                    </label>
                    <textarea name="address" rows="2" {{ $landingPage->require_address ? 'required' : '' }}
                        class="w-full border border-gray-300 rounded-xl px-4 py-3 text-base focus:outline-none focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 transition">{{ old('address') }}</textarea>
                </div>
                @endif

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
                    <input type="number" name="quantity" value="{{ old('quantity', 1) }}" min="1" max="99"
                        class="w-full border border-gray-300 rounded-xl px-4 py-3 text-base focus:outline-none focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 transition">
                </div>

                @foreach($landingPage->order_form_fields ?? [] as $field)
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        {{ $field['label'] }} @if($field['required'])<span class="text-red-500">*</span>@endif
                    </label>
                    @if($field['type'] === 'textarea')
                        <textarea name="custom[{{ $field['key'] }}]" rows="2" {{ $field['required'] ? 'required' : '' }}
                            class="w-full border border-gray-300 rounded-xl px-4 py-3 text-base focus:outline-none focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 transition">{{ old('custom.' . $field['key']) }}</textarea>
                    @elseif($field['type'] === 'select')
                        <select name="custom[{{ $field['key'] }}]" {{ $field['required'] ? 'required' : '' }}
                            class="w-full border border-gray-300 rounded-xl px-4 py-3 text-base focus:outline-none focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 transition">
                            <option value="">Select…</option>
                            @foreach($field['options'] ?? [] as $opt)
                                <option value="{{ $opt }}" {{ old('custom.' . $field['key']) === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                    @elseif($field['type'] === 'checkbox')
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="custom[{{ $field['key'] }}]" value="1" {{ old('custom.' . $field['key']) ? 'checked' : '' }} class="rounded text-orange-600 w-5 h-5">
                            <span class="text-sm text-gray-600">Yes</span>
                        </label>
                    @else
                        <input type="{{ $field['type'] }}" name="custom[{{ $field['key'] }}]" value="{{ old('custom.' . $field['key']) }}" {{ $field['required'] ? 'required' : '' }}
                            class="w-full border border-gray-300 rounded-xl px-4 py-3 text-base focus:outline-none focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 transition">
                    @endif
                </div>
                @endforeach

                <button type="submit"
                    class="w-full text-white font-extrabold py-4 rounded-xl text-lg transition shadow-lg hover:opacity-90 active:scale-[0.99]"
                    style="background-color: {{ setting('primary_color', '#ea580c') }};">
                    {{ $landingPage->order_button_text }}
                </button>
                <p class="text-xs text-center text-gray-400">Cash on Delivery — pay when it arrives at your door.</p>
            </form>
        </div>
    </div>
@endif
@endsection
