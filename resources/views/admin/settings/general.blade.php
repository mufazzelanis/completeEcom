@extends('admin.settings.layout')
@section('settings-title', 'General Settings')

@section('settings-content')
<form method="POST" action="{{ route('admin.settings.update', 'general') }}">
@csrf @method('PATCH')

{{-- Site Information --}}
<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Site Information</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Site Name</label>
            <input type="text" name="site_name" value="{{ setting('site_name', 'ShopVista') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Site Title</label>
            <input type="text" name="site_title" value="{{ setting('site_title', 'ShopVista – Online Store') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
        </div>
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Site Tagline</label>
            <input type="text" name="site_tagline" value="{{ setting('site_tagline', 'Your one-stop shop for everything you need.') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Website URL</label>
            <input type="url" name="website_url" value="{{ setting('website_url', config('app.url')) }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Admin Email</label>
            <input type="email" name="admin_email" value="{{ setting('admin_email', '') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
        </div>
    </div>
</div>

{{-- Company Information --}}
<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Company Information</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Company Name</label>
            <input type="text" name="company_name" value="{{ setting('company_name', '') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Company Email</label>
            <input type="email" name="company_email" value="{{ setting('company_email', '') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Company Phone</label>
            <input type="text" name="company_phone" value="{{ setting('company_phone', '') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500"
                   placeholder="+880 1700-000000">
            <p class="text-xs text-gray-400 mt-1">Printed in the invoice header when filled in. Left blank, the phone line is simply left out — no placeholder number is ever shown.</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Support Email</label>
            <input type="email" name="support_email" value="{{ setting('support_email', '') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
        </div>
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Company Address</label>
            <textarea name="company_address" rows="2"
                      class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500"
                      placeholder="Dhaka, Bangladesh">{{ setting('company_address', '') }}</textarea>
        </div>
    </div>
</div>

{{-- Contact Page Info --}}
<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Contact Page Info</h2>
    <p class="text-xs text-gray-400">Shown on the /contact page. If left empty, falls back to Company Information above.</p>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Contact Email</label>
            <input type="email" name="contact_email" value="{{ setting('contact_email', '') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500"
                   placeholder="e.g. support@shopvista.com">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Contact Phone</label>
            <input type="text" name="contact_phone" value="{{ setting('contact_phone', '') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500"
                   placeholder="e.g. +880 1700-000000">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Business Hours</label>
            <input type="text" name="contact_hours" value="{{ setting('contact_hours', '') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500"
                   placeholder="e.g. Mon–Fri 9am–6pm, Sat 10am–4pm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Contact Address</label>
            <input type="text" name="contact_address" value="{{ setting('contact_address', '') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500"
                   placeholder="e.g. Dhaka, Bangladesh">
        </div>
    </div>
</div>

{{-- Storefront Buttons --}}
<div class="bg-white rounded-xl shadow-sm border p-6 space-y-5"
     x-data="{
        from: '{{ setting('order_button_color_from', '#ec4899') }}',
        via: '{{ setting('order_button_color_via', '#d946ef') }}',
        to: '{{ setting('order_button_color_to', '#fb923c') }}',
        textColor: '{{ setting('order_button_text_color', '#ffffff') }}',
     }">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Storefront Buttons</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">"Buy Now" Button Text</label>
            <input type="text" name="buy_now_button_text" value="{{ setting('buy_now_button_text', 'Buy Now') }}" maxlength="40"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
            <p class="text-xs text-gray-400 mt-1">Shown on every product card and the product page — skips the cart and goes straight to checkout for that one item.</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Quick "Add to Cart" Button Style</label>
            <select name="add_to_cart_button_style" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
                <option value="icon" {{ setting('add_to_cart_button_style', 'icon') === 'icon' ? 'selected' : '' }}>Icon only</option>
                <option value="text" {{ setting('add_to_cart_button_style', 'icon') === 'text' ? 'selected' : '' }}>Icon + Text</option>
            </select>
            <p class="text-xs text-gray-400 mt-1">Small quick-add button shown on the top-right of each product card (adds 1 unit straight to cart, no page reload).</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Quick "Add to Cart" Button Text</label>
            <input type="text" name="add_to_cart_button_text" value="{{ setting('add_to_cart_button_text', 'Add to Cart') }}" maxlength="40"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
            <p class="text-xs text-gray-400 mt-1">Only shown when the style above is set to "Icon + Text".</p>
        </div>
    </div>

    <div class="border-t pt-4">
        <h3 class="text-sm font-semibold text-gray-800 mb-1">"Buy Now" / "Order Now" Button Color</h3>
        <p class="text-xs text-gray-400 mb-3">The pill-shaped gradient button on every product card (and "Select Options" for products with size/color variants). Pick any RGB color for each stop — leave them untouched and the button keeps today's exact pink-to-orange look.</p>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Gradient Start</label>
                <div class="flex items-center gap-1.5">
                    <input type="color" name="order_button_color_from" x-model="from" class="h-9 w-10 flex-shrink-0 rounded border cursor-pointer">
                    <input type="text" x-model="from" class="w-0 flex-1 border rounded px-2 py-1.5 text-xs text-gray-600 font-mono bg-gray-50">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Gradient Middle</label>
                <div class="flex items-center gap-1.5">
                    <input type="color" name="order_button_color_via" x-model="via" class="h-9 w-10 flex-shrink-0 rounded border cursor-pointer">
                    <input type="text" x-model="via" class="w-0 flex-1 border rounded px-2 py-1.5 text-xs text-gray-600 font-mono bg-gray-50">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Gradient End</label>
                <div class="flex items-center gap-1.5">
                    <input type="color" name="order_button_color_to" x-model="to" class="h-9 w-10 flex-shrink-0 rounded border cursor-pointer">
                    <input type="text" x-model="to" class="w-0 flex-1 border rounded px-2 py-1.5 text-xs text-gray-600 font-mono bg-gray-50">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Text Color</label>
                <div class="flex items-center gap-1.5">
                    <input type="color" name="order_button_text_color" x-model="textColor" class="h-9 w-10 flex-shrink-0 rounded border cursor-pointer">
                    <input type="text" x-model="textColor" class="w-0 flex-1 border rounded px-2 py-1.5 text-xs text-gray-600 font-mono bg-gray-50">
                </div>
            </div>
        </div>

        {{-- Live preview — exact same pill shape + hover shimmer as the real button
             (resources/views/partials/product-card.blade.php). --}}
        <div class="mt-4 bg-gray-50 border rounded-xl p-4 flex items-center gap-3 flex-wrap">
            <span class="text-xs text-gray-400">Preview:</span>
            <button type="button" tabindex="-1"
                class="inline-flex items-center gap-1 bg-[length:200%_auto] hover:bg-right text-[11px] font-bold pl-2 pr-3 py-1 rounded-full shadow-sm hover:shadow-md transition-all duration-500"
                :style="{ backgroundImage: `linear-gradient(to right, ${from}, ${via}, ${to})`, color: textColor }">
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z"/></svg>
                <span x-text="@js(setting('buy_now_button_text', 'Buy Now'))"></span>
            </button>
            <button type="button" @click="from = '#ec4899'; via = '#d946ef'; to = '#fb923c'; textColor = '#ffffff';"
                class="ml-auto text-xs text-gray-400 hover:text-orange-600 underline">
                Reset to default
            </button>
        </div>
    </div>
</div>

<div class="flex justify-end">
    <button type="submit" class="px-6 py-2 bg-orange-600 text-white rounded-lg text-sm font-semibold hover:bg-orange-700 transition">Save Settings</button>
</div>
</form>
@endsection
