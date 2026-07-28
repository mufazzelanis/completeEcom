@php $seo = $category ?? null; @endphp
<div class="border-t border-gray-100 pt-5 mt-2">
    <h3 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        SEO & Social Sharing
    </h3>
    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Meta Title <span class="text-xs text-gray-400 font-normal">(blank = category name)</span></label>
            <input type="text" name="meta_title" value="{{ old('meta_title', $seo?->meta_title ?? '') }}" maxlength="100"
                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Meta Description <span class="text-xs text-gray-400 font-normal">(blank = category description)</span></label>
            <textarea name="meta_description" rows="2" maxlength="300"
                class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none">{{ old('meta_description', $seo?->meta_description ?? '') }}</textarea>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Meta Keywords</label>
                <input type="text" name="meta_keywords" value="{{ old('meta_keywords', $seo?->meta_keywords ?? '') }}"
                    placeholder="e.g. electronics, gadgets, tech"
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Canonical URL <span class="text-xs text-gray-400 font-normal">(blank = auto)</span></label>
                <input type="url" name="canonical_url" value="{{ old('canonical_url', $seo?->canonical_url ?? '') }}" placeholder="https://..."
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Social Share (OG) Image <span class="text-xs text-gray-400 font-normal">1200×630 px, blank = category image</span></label>
            @if($seo?->og_image)
            <div class="flex items-center gap-3 mb-2">
                <img src="{{ \Illuminate\Support\Facades\Storage::url($seo->og_image) }}" class="h-12 w-20 object-cover rounded-lg border">
                <span class="text-xs text-green-600">Uploaded</span>
            </div>
            @endif
            <input type="file" name="og_image" accept="image/*"
                class="block w-full text-xs text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 border-t border-gray-100 pt-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Robots Meta</label>
                <select name="robots_meta" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @foreach(['index,follow' => 'Index, Follow (default)', 'noindex,follow' => 'No Index, Follow', 'index,nofollow' => 'Index, No Follow', 'noindex,nofollow' => 'No Index, No Follow'] as $val => $lbl)
                    <option value="{{ $val }}" {{ old('robots_meta', $seo?->robots_meta ?? 'index,follow') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">301 Redirect URL <span class="text-xs text-gray-400 font-normal">(blank = none)</span></label>
                <input type="url" name="redirect_url" value="{{ old('redirect_url', $seo?->redirect_url ?? '') }}"
                    placeholder="https://... (send visitors elsewhere)"
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
        </div>

        <div class="border-t border-gray-100 pt-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-3">Search Visibility</p>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                @foreach([
                    ['noindex', 'No Index'],
                    ['nofollow', 'No Follow'],
                    ['nosnippet', 'No Snippet'],
                    ['noimageindex', 'No Image Index'],
                ] as [$fname, $flabel])
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="{{ $fname }}" value="1" {{ old($fname, $seo?->{$fname} ?? false) ? 'checked' : '' }}
                        class="rounded text-indigo-600">
                    <span class="text-sm text-gray-700">{{ $flabel }}</span>
                </label>
                @endforeach
            </div>
        </div>
    </div>
</div>
