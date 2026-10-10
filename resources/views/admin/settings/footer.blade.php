@extends('admin.settings.layout')
@section('settings-title', 'Footer Settings')

@section('settings-content')
<form method="POST" action="{{ route('admin.settings.update', 'footer') }}" enctype="multipart/form-data">
@csrf @method('PATCH')

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Footer Content</h2>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Footer Description</label>
        <textarea name="footer_description" rows="3"
                  class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">{{ setting('footer_description', 'Your one-stop shop for everything you need.') }}</textarea>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Copyright Text</label>
        <input type="text" name="copyright_text" value="{{ setting('copyright_text', '© {year} ShopVista. All rights reserved.') }}"
               class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
        <p class="text-xs text-gray-400 mt-1">Use <code class="bg-gray-100 px-1 rounded">{year}</code> for the current year.</p>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Newsletter Section</h2>
    <label class="flex items-center gap-2 cursor-pointer">
        <input type="hidden" name="newsletter_enabled" value="0">
        <input type="checkbox" name="newsletter_enabled" value="1" class="rounded text-orange-600"
               @checked(setting('newsletter_enabled','1') == '1')>
        <span class="text-sm text-gray-700">Show Newsletter Signup in Footer</span>
    </label>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Newsletter Heading</label>
        <input type="text" name="newsletter_heading" value="{{ setting('newsletter_heading', 'Subscribe to our newsletter') }}"
               class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Newsletter Sub-text</label>
        <input type="text" name="newsletter_subtext" value="{{ setting('newsletter_subtext', 'Get the latest deals and updates.') }}"
               class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
    </div>

    <div class="border-t pt-4"
         x-data="{
            from: '{{ setting('newsletter_button_color', '#f97316') }}',
            to: '{{ setting('newsletter_button_hover_color', '#ef4444') }}',
            textColor: '{{ setting('newsletter_button_text_color', '#ffffff') }}',
         }">
        <h3 class="text-sm font-semibold text-gray-800 mb-1">"Subscribe" Button Color</h3>
        <p class="text-xs text-gray-400 mb-3">The pill-shaped button next to the email field above, in the footer. Leave untouched and it keeps today's orange-to-red look.</p>
        <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Gradient Start</label>
                <div class="flex items-center gap-1.5">
                    <input type="color" name="newsletter_button_color" x-model="from" class="h-9 w-10 flex-shrink-0 rounded border cursor-pointer">
                    <input type="text" x-model="from" class="w-0 flex-1 border rounded px-2 py-1.5 text-xs text-gray-600 font-mono bg-gray-50">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Gradient End</label>
                <div class="flex items-center gap-1.5">
                    <input type="color" name="newsletter_button_hover_color" x-model="to" class="h-9 w-10 flex-shrink-0 rounded border cursor-pointer">
                    <input type="text" x-model="to" class="w-0 flex-1 border rounded px-2 py-1.5 text-xs text-gray-600 font-mono bg-gray-50">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Text Color</label>
                <div class="flex items-center gap-1.5">
                    <input type="color" name="newsletter_button_text_color" x-model="textColor" class="h-9 w-10 flex-shrink-0 rounded border cursor-pointer">
                    <input type="text" x-model="textColor" class="w-0 flex-1 border rounded px-2 py-1.5 text-xs text-gray-600 font-mono bg-gray-50">
                </div>
            </div>
        </div>

        {{-- Live preview — the footer is dark, so the preview card is too, otherwise a
             pale gradient pick would look fine here and invisible-on-dark on the real page. --}}
        <div class="mt-4 bg-gray-900 border border-gray-700 rounded-xl p-4 flex items-center gap-3 flex-wrap">
            <span class="text-xs text-gray-400">Preview:</span>
            <button type="button" tabindex="-1"
                class="bg-[length:200%_auto] hover:bg-right px-6 py-2.5 rounded-full font-semibold text-sm shadow-lg transition-all duration-500"
                :style="{ backgroundImage: `linear-gradient(to right, ${from}, ${to})`, color: textColor }">
                Subscribe
            </button>
            <button type="button" @click="from = '#f97316'; to = '#ef4444'; textColor = '#ffffff';"
                class="ml-auto text-xs text-gray-400 hover:text-orange-500 underline">
                Reset to default
            </button>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Footer Column Titles</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Column 2 Title</label>
            <input type="text" name="footer_col2_title" value="{{ setting('footer_col2_title', 'Quick Links') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Column 3 Title</label>
            <input type="text" name="footer_col3_title" value="{{ setting('footer_col3_title', 'Customer Service') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Column 4 Title</label>
            <input type="text" name="footer_col4_title" value="{{ setting('footer_col4_title', 'Contact') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Payment Icons ("We Accept")</h2>
    <p class="text-xs text-gray-400">Upload small payment gateway logos (Visa, bKash, Nagad, etc.) shown in the footer "We Accept" section. Recommended: PNG with transparent background, ~80×30px.</p>

    @php $iconSlots = [
        'payment_icon_1' => 'Icon 1 (e.g. Visa)',
        'payment_icon_2' => 'Icon 2 (e.g. Mastercard)',
        'payment_icon_3' => 'Icon 3 (e.g. bKash)',
        'payment_icon_4' => 'Icon 4 (e.g. Nagad)',
        'payment_icon_5' => 'Icon 5 (e.g. Rocket)',
        'payment_icon_6' => 'Icon 6 (e.g. COD)',
        'payment_icon_7' => 'Icon 7',
        'payment_icon_8' => 'Icon 8',
    ]; @endphp

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        @foreach($iconSlots as $key => $label)
            @php $current = setting($key); @endphp
            <div class="flex items-center gap-3 border rounded-lg px-3 py-2 bg-gray-50/50">
                <div class="w-14 h-8 flex items-center justify-center bg-white border rounded overflow-hidden flex-shrink-0">
                    @if($current)
                        <img src="{{ setting_file_url($key) }}" class="max-w-full max-h-full object-contain">
                    @else
                        <span class="text-[10px] text-gray-300">No image</span>
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs text-gray-500 mb-1">{{ $label }}</p>
                    <div class="flex items-center gap-2">
                        <input type="file" name="{{ $key }}" accept="image/*"
                               class="text-xs border rounded px-2 py-1 w-full">
                        @if($current)
                            <label class="flex items-center gap-1 text-xs text-red-500 cursor-pointer whitespace-nowrap">
                                <input type="checkbox" name="delete_{{ $key }}" value="1" class="rounded text-red-500">
                                Remove
                            </label>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

<div class="flex justify-end">
    <button type="submit" class="px-6 py-2 bg-orange-600 text-white rounded-lg text-sm font-semibold hover:bg-orange-700 transition">Save Footer</button>
</div>
</form>
@endsection
