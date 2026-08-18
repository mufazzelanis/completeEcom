@php
    $lp = $landingPage ?? null;
    $existingFields = old('field_label') ? null : ($lp->order_form_fields ?? []);
@endphp

<div class="grid grid-cols-3 gap-6">
    <div class="col-span-2 space-y-5">
        {{-- Basic Info --}}
        <div class="bg-white rounded-2xl shadow-sm p-6 space-y-5">
            <h3 class="font-medium text-gray-800">Basic Info</h3>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Title <span class="text-red-500">*</span></label>
                <input type="text" name="title" value="{{ old('title', $lp->title ?? '') }}" required
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    URL Slug
                    <span class="text-gray-400 font-normal">— {{ request()->getHost() }}/<span class="text-gray-600">your-slug</span></span>
                </label>
                <input type="text" name="slug" value="{{ old('slug', $lp->slug ?? '') }}" placeholder="{{ $lp ? $lp->slug : 'auto-generated from title if left blank' }}"
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @if($lp)
                <p class="text-xs text-amber-600 mt-1">Changing this moves the live URL — anything already pointing at the old one (ads, printed materials) will break.</p>
                @endif
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Link to Product <span class="text-gray-400 font-normal">(optional)</span></label>
                <select name="product_id" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">— None —</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}" {{ (int) old('product_id', $lp->product_id ?? 0) === $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1">Pulls price/stock from this product unless you set a price override below.</p>
            </div>
        </div>

        {{-- Hero --}}
        <div class="bg-white rounded-2xl shadow-sm p-6 space-y-5">
            <h3 class="font-medium text-gray-800">Hero</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Heading</label>
                    <input type="text" name="hero_heading" value="{{ old('hero_heading', $lp->hero_heading ?? '') }}" placeholder="A headline that grabs attention"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Subheading</label>
                    <input type="text" name="hero_subheading" value="{{ old('hero_subheading', $lp->hero_subheading ?? '') }}"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Hero Image</label>
                @if($lp?->hero_image)
                    <img src="{{ Storage::url($lp->hero_image) }}" class="h-20 rounded-lg object-cover mb-2 border border-gray-100">
                @endif
                <input type="file" name="hero_image" accept="image/*" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm">
            </div>
        </div>

        {{-- Content --}}
        <div class="bg-white rounded-2xl shadow-sm p-6 space-y-2">
            <h3 class="font-medium text-gray-800 mb-3">Page Content</h3>
            <p class="text-xs text-gray-400 -mt-2 mb-3">Design the rest of the page freely — description, features, images, testimonials, whatever sells this product. This is the main body shown between the hero and the order form.</p>
            @include('partials.rich-editor', ['name' => 'content', 'value' => old('content', $lp->content ?? ''), 'id' => 'lpcontent', 'placeholder' => 'Write/design the landing page content…'])
        </div>

        {{-- Order Form Builder --}}
        <div class="bg-white rounded-2xl shadow-sm p-6 space-y-4" x-data="{
                fields: {{ Js::from(old('field_label') ? collect(old('field_label'))->map(fn($l, $i) => [
                    'label' => $l,
                    'type' => old('field_type')[$i] ?? 'text',
                    'required' => (old('field_required')[$i] ?? '0') === '1',
                    'options' => old('field_options')[$i] ?? '',
                ])->values()->all() : collect($existingFields ?? [])->map(fn($f) => [
                    'label' => $f['label'], 'type' => $f['type'],
                    'required' => (bool) ($f['required'] ?? false),
                    'options' => implode(', ', $f['options'] ?? []),
                ])->values()->all()) }},
                addField() { this.fields.push({ label: '', type: 'text', required: false, options: '' }); },
                removeField(i) { this.fields.splice(i, 1); },
            }">
            <div>
                <h3 class="font-medium text-gray-800">Order Form</h3>
                <p class="text-xs text-gray-400 mt-0.5">Name and phone are always collected — required for fulfilling any order. Add extra fields below for anything else you need (size, color, delivery notes, etc.).</p>
            </div>

            <div class="flex flex-wrap gap-6">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="collect_address" value="1" {{ old('collect_address', $lp->collect_address ?? true) ? 'checked' : '' }} class="rounded text-indigo-600">
                    <span class="text-sm text-gray-700">Collect shipping address</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="require_address" value="1" {{ old('require_address', $lp->require_address ?? false) ? 'checked' : '' }} class="rounded text-indigo-600">
                    <span class="text-sm text-gray-700">Make address required</span>
                </label>
            </div>

            <div class="space-y-2">
                <template x-for="(field, i) in fields" :key="i">
                    <div class="flex items-start gap-2 border border-gray-100 rounded-xl p-3">
                        <div class="flex-1 grid grid-cols-2 gap-2">
                            <input type="text" name="field_label[]" x-model="field.label" placeholder="Field label, e.g. Size"
                                class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <select name="field_type[]" x-model="field.type"
                                class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="text">Text</option>
                                <option value="textarea">Textarea</option>
                                <option value="select">Dropdown</option>
                                <option value="checkbox">Checkbox</option>
                                <option value="tel">Phone</option>
                                <option value="email">Email</option>
                                <option value="number">Number</option>
                            </select>
                            <input type="hidden" name="field_required[]" :value="field.required ? '1' : '0'">
                            <label class="flex items-center gap-1.5 text-xs text-gray-600 cursor-pointer">
                                <input type="checkbox" x-model="field.required" class="rounded text-indigo-600">
                                Required
                            </label>
                            <input type="text" name="field_options[]" x-model="field.options" x-show="field.type === 'select'"
                                placeholder="Options, comma separated: Small, Medium, Large"
                                class="col-span-2 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <button type="button" @click="removeField(i)" class="text-red-400 hover:text-red-600 p-2" aria-label="Remove field">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </template>
            </div>
            <button type="button" @click="addField()" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add Field
            </button>
        </div>

        {{-- Thank You Page --}}
        <div class="bg-white rounded-2xl shadow-sm p-6 space-y-4">
            <h3 class="font-medium text-gray-800">Thank You Page</h3>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Heading</label>
                <input type="text" name="thank_you_heading" value="{{ old('thank_you_heading', $lp->thank_you_heading ?? 'Thank You!') }}"
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                <textarea name="thank_you_message" rows="2" placeholder="We've received your order and will contact you shortly to confirm."
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('thank_you_message', $lp->thank_you_message ?? '') }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Redirect URL <span class="text-gray-400 font-normal">(optional — send them somewhere instead, e.g. a Facebook group)</span></label>
                <input type="url" name="thank_you_redirect_url" value="{{ old('thank_you_redirect_url', $lp->thank_you_redirect_url ?? '') }}" placeholder="https://…"
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
        </div>

        {{-- SEO --}}
        <div class="bg-white rounded-2xl shadow-sm p-6 space-y-4">
            <h3 class="font-medium text-gray-800">SEO</h3>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Meta Title</label>
                <input type="text" name="meta_title" value="{{ old('meta_title', $lp->meta_title ?? '') }}"
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Meta Description</label>
                <textarea name="meta_description" rows="2" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('meta_description', $lp->meta_description ?? '') }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Social Share Image (OG Image)</label>
                @if($lp?->og_image)
                    <img src="{{ Storage::url($lp->og_image) }}" class="h-16 rounded-lg object-cover mb-2 border border-gray-100">
                @endif
                <input type="file" name="og_image" accept="image/*" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm">
            </div>
        </div>
    </div>

    <div class="space-y-5">
        <div class="bg-white rounded-2xl shadow-sm p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="draft" {{ old('status', $lp->status ?? 'draft') === 'draft' ? 'selected' : '' }}>Draft (not public)</option>
                    <option value="published" {{ old('status', $lp->status ?? '') === 'published' ? 'selected' : '' }}>Published (live)</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Price Override <span class="text-gray-400 font-normal">(optional)</span></label>
                <input type="number" step="0.01" min="0" name="price_override" value="{{ old('price_override', $lp->price_override ?? '') }}" placeholder="Leave blank to use the linked product's price"
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Order Button Text</label>
                <input type="text" name="order_button_text" value="{{ old('order_button_text', $lp->order_button_text ?? 'Order Now') }}"
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <button type="submit" class="w-full bg-indigo-600 text-white px-4 py-2.5 rounded-xl text-sm font-medium hover:bg-indigo-700 transition">
                {{ $lp ? 'Save Changes' : 'Create Landing Page' }}
            </button>
            @if($lp)
            <a href="{{ url($lp->slug) }}" target="_blank" class="block text-center text-sm text-indigo-600 hover:text-indigo-800 font-medium">View Live Page ↗</a>
            @endif
        </div>

        <div class="bg-white rounded-2xl shadow-sm p-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Header Logo <span class="text-gray-400 font-normal block text-xs mt-0.5">Optional — falls back to your site logo</span></label>
            @if($lp?->header_logo)
                <img src="{{ Storage::url($lp->header_logo) }}" class="h-10 object-contain mb-2 border border-gray-100 rounded-lg p-1">
            @endif
            <input type="file" name="header_logo" accept="image/*" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm">
        </div>
    </div>
</div>
