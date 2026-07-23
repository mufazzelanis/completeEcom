@php $s = $section ?? null; @endphp
<div class="space-y-5">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
        <input type="text" name="title" value="{{ old('title', $s->title ?? '') }}" required
            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 @error('title') border-red-400 @enderror">
        @error('title')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Subtitle <span class="text-xs text-gray-400 font-normal">(optional, shown under the title)</span></label>
        <input type="text" name="subtitle" value="{{ old('subtitle', $s->subtitle ?? '') }}"
            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
    </div>

    @php
        $categoryMap = $categories->mapWithKeys(fn ($c) => [$c->id => $c->slug])->all();
        $viewAllLabels = [
            'featured'     => 'featured=1',
            'top_selling'  => 'sort=popular',
            'new_arrivals' => 'sort=latest',
            'on_sale'      => 'on_sale=1',
            'category'     => 'sort=latest',
        ];
    @endphp
    <div x-data="{
        sourceType: '{{ old('source_type', $s->source_type ?? 'featured') }}',
        categoryId: '{{ old('category_id', $s->category_id ?? '') }}',
        categorySlugs: {{ Js::from($categoryMap) }},
        baseQuery: {{ Js::from($viewAllLabels) }},
        get viewAllPreview() {
            let q = this.baseQuery[this.sourceType] || 'sort=latest';
            if (this.categoryId && this.categorySlugs[this.categoryId]) {
                q += '&category=' + this.categorySlugs[this.categoryId];
            }
            return '/shop?' + q;
        }
    }">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Product Source *</label>
            <select name="source_type" x-model="sourceType"
                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                @foreach([
                    'featured'     => 'Featured Products (admin-marked featured items)',
                    'top_selling'  => 'Top Selling (most viewed)',
                    'new_arrivals' => 'New Arrivals (newest first)',
                    'on_sale'      => 'On Sale (has a sale price)',
                    'category'     => 'Specific Category (latest from one category)',
                ] as $val => $label)
                <option value="{{ $val }}" {{ old('source_type', $s->source_type ?? 'featured') === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="mt-5">
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Limit to Category
                <span class="text-xs text-gray-400 font-normal" x-show="sourceType === 'category'">(required for "Specific Category")</span>
                <span class="text-xs text-gray-400 font-normal" x-show="sourceType !== 'category'">— optional filter, e.g. "Featured Products" scoped to just Electronics</span>
            </label>
            <select name="category_id" x-model="categoryId" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                <option value="">— Any category —</option>
                @foreach($categories as $cat)
                <option value="{{ $cat->id }}" {{ (string) old('category_id', $s->category_id ?? '') === (string) $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>
            @error('category_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Number of Products</label>
                <input type="number" name="product_limit" value="{{ old('product_limit', $s->product_limit ?? 8) }}" min="2" max="32"
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Visual Style</label>
                <select name="theme" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                    <option value="light" {{ old('theme', $s->theme ?? 'light') === 'light' ? 'selected' : '' }}>Light (white background)</option>
                    <option value="sale" {{ old('theme', $s->theme ?? 'light') === 'sale' ? 'selected' : '' }}>Sale (orange/red banner)</option>
                </select>
            </div>
        </div>

        <div class="mt-5">
            <label class="block text-sm font-medium text-gray-700 mb-1">"View All" Button Text</label>
            <input type="text" name="view_all_label" value="{{ old('view_all_label', $s->view_all_label ?? '') }}" maxlength="40" placeholder="VIEW ALL"
                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
            <p class="text-xs text-gray-400 mt-1">e.g. "See More", "Shop Now" — leave blank to keep the default "VIEW ALL".</p>
        </div>

        {{-- "View All" link is computed automatically from Product Source + Category above —
             no need to type a URL. The field below is only for the rare case of wanting
             something different. --}}
        <div class="mt-5 bg-gray-50 rounded-xl p-3 text-xs text-gray-500">
            "View All" link will go to: <span class="font-mono text-gray-700" x-text="viewAllPreview"></span>
        </div>
        <div class="mt-3" x-data="{ open: {{ old('view_all_query', $s->view_all_query ?? '') ? 'true' : 'false' }} }">
            <button type="button" @click="open = !open" class="text-xs text-orange-600 hover:underline">
                <span x-show="!open">Advanced: use a custom link instead</span>
                <span x-show="open">Hide advanced option</span>
            </button>
            <div x-show="open" x-cloak class="mt-2">
                <input type="text" name="view_all_query" value="{{ old('view_all_query', $s->view_all_query ?? '') }}" placeholder="e.g. brand=nike or search=headphones"
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                <p class="text-xs text-gray-400 mt-1">Only fill this in if you want the link to go somewhere other than the automatic preview above. Leave empty otherwise.</p>
            </div>
        </div>
    </div>

    <div class="flex items-center space-x-3">
        <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $s->is_active ?? true) ? 'checked' : '' }} class="sr-only peer">
            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-600"></div>
        </label>
        <span class="text-sm font-medium text-gray-700">Show on homepage</span>
    </div>
</div>
