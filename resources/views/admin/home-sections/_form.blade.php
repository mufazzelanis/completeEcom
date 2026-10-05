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
        categoryIds: {{ Js::from(old('category_ids', $s ? $s->getCategoryIdsList() : [])) }},
        allCategoryIds: {{ Js::from($categories->pluck('id')) }},
        categorySearch: '',
        categorySlugs: {{ Js::from($categoryMap) }},
        baseQuery: {{ Js::from($viewAllLabels) }},
        get viewAllPreview() {
            let q = this.baseQuery[this.sourceType] || 'sort=latest';
            let slugs = this.categoryIds.map(id => this.categorySlugs[id]).filter(Boolean);
            if (slugs.length) {
                q += '&category=' + slugs.join(',');
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
                Limit to Categories
                <span class="text-xs text-gray-400 font-normal" x-show="sourceType === 'category'">(required for "Specific Category")</span>
                <span class="text-xs text-gray-400 font-normal" x-show="sourceType !== 'category'">— optional filter, e.g. "Featured Products" scoped to a few categories</span>
            </label>
            <p class="text-xs text-gray-400 mb-2">Pick one or several — products from any of the checked categories will show together in this section.</p>

            <template x-for="id in categoryIds" :key="id">
                <input type="hidden" name="category_ids[]" :value="id">
            </template>

            <div class="border border-gray-200 rounded-xl overflow-hidden">
                <div class="flex items-center gap-2 px-3 py-2 border-b border-gray-100 bg-gray-50">
                    <input type="text" x-model="categorySearch" placeholder="Search categories…"
                        class="flex-1 text-sm border-0 bg-transparent focus:outline-none focus:ring-0 p-0 min-w-0">
                    <span class="text-xs text-gray-400 whitespace-nowrap" x-text="categoryIds.length + ' selected'"></span>
                    <button type="button" @click="categoryIds = allCategoryIds.slice()" class="text-xs text-orange-600 hover:underline whitespace-nowrap">Select all</button>
                    <button type="button" @click="categoryIds = []" class="text-xs text-gray-400 hover:text-gray-600 whitespace-nowrap">Clear</button>
                </div>
                <div class="max-h-56 overflow-y-auto p-2 grid grid-cols-1 sm:grid-cols-2 gap-1">
                    @foreach($categories as $cat)
                    <label class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-gray-50 cursor-pointer text-sm"
                        x-show="categorySearch === '' || '{{ addslashes(strtolower($cat->name)) }}'.includes(categorySearch.toLowerCase())">
                        <input type="checkbox" value="{{ $cat->id }}"
                            :checked="categoryIds.includes({{ $cat->id }})"
                            @change="$event.target.checked ? categoryIds.push({{ $cat->id }}) : categoryIds = categoryIds.filter(i => i !== {{ $cat->id }})"
                            class="rounded text-orange-600 flex-shrink-0">
                        <span class="truncate">{{ $cat->name }}</span>
                    </label>
                    @endforeach
                </div>
            </div>
            @error('category_ids')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Number of Products</label>
                <input type="number" name="product_limit" value="{{ old('product_limit', $s->product_limit ?? 8) }}" min="2" max="32"
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                <p class="text-xs text-gray-400 mt-1">Total products shown (across all rows).</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Products Per Row</label>
                <input type="number" name="columns" value="{{ old('columns', $s->columns ?? 4) }}" min="2" max="6"
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                <p class="text-xs text-gray-400 mt-1">Desktop columns (2-6). Mobile/tablet adjust automatically.</p>
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
            <label class="block text-sm font-medium text-gray-700 mb-1">Small "View All" Link Text <span class="text-xs text-gray-400 font-normal">(top-right of the section title)</span></label>
            <input type="text" name="view_all_label" value="{{ old('view_all_label', $s->view_all_label ?? '') }}" maxlength="40" placeholder="VIEW ALL"
                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
            <p class="text-xs text-gray-400 mt-1">Takes customers to the full /shop listing. Leave blank to keep the default "VIEW ALL".</p>
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

    {{-- "See More" button — the big pill below the product grid that reveals more of this
         section's products in place (home.blade.php), separate from the small "View All" link
         above which navigates away to /shop. Its own text field (so the two don't have to read
         identically) plus optional RGB colors, same enabled-toggle pattern as landing pages'
         Custom Brand Color — a <input type="color"> can't represent "unset", so unchecking this
         explicitly clears the saved colors rather than just hiding the pickers. --}}
    <div class="pt-5 border-t border-gray-100"
         x-data="{
            label: {{ Js::from(old('see_more_label', $s->see_more_label ?? '')) }},
            useCustomColor: {{ old('see_more_color_from', $s->see_more_color_from ?? null) ? 'true' : 'false' }},
            from: {{ Js::from(old('see_more_color_from', $s->see_more_color_from ?? '#f97316')) }},
            to: {{ Js::from(old('see_more_color_to', $s->see_more_color_to ?? '#ef4444')) }},
            text: {{ Js::from(old('see_more_text_color', $s->see_more_text_color ?? '#ffffff')) }},
         }">
        <p class="text-sm font-semibold text-gray-800 mb-1">"See More" Button <span class="text-xs text-gray-400 font-normal">(reveals more products on this page)</span></p>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Button Text</label>
            <input type="text" name="see_more_label" x-model="label" maxlength="40" placeholder="See More"
                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
            <p class="text-xs text-gray-400 mt-1">Leave blank to keep the default "See More".</p>
        </div>

        <label class="flex items-center gap-2 cursor-pointer mb-3">
            <input type="checkbox" x-model="useCustomColor" class="rounded text-orange-600">
            <span class="text-sm font-medium text-gray-700">Custom Button Color (RGB)</span>
        </label>
        <input type="hidden" name="see_more_color_enabled" :value="useCustomColor ? '1' : '0'">

        <div x-show="useCustomColor" x-cloak class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Gradient Start</label>
                <div class="flex items-center gap-1.5">
                    <input type="color" name="see_more_color_from" x-model="from" class="h-9 w-10 flex-shrink-0 rounded border cursor-pointer">
                    <input type="text" x-model="from" class="w-0 flex-1 border rounded px-2 py-1.5 text-xs text-gray-600 font-mono bg-gray-50">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Gradient End</label>
                <div class="flex items-center gap-1.5">
                    <input type="color" name="see_more_color_to" x-model="to" class="h-9 w-10 flex-shrink-0 rounded border cursor-pointer">
                    <input type="text" x-model="to" class="w-0 flex-1 border rounded px-2 py-1.5 text-xs text-gray-600 font-mono bg-gray-50">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Text Color</label>
                <div class="flex items-center gap-1.5">
                    <input type="color" name="see_more_text_color" x-model="text" class="h-9 w-10 flex-shrink-0 rounded border cursor-pointer">
                    <input type="text" x-model="text" class="w-0 flex-1 border rounded px-2 py-1.5 text-xs text-gray-600 font-mono bg-gray-50">
                </div>
            </div>
        </div>
        <p class="text-xs text-gray-400 mb-3" x-show="!useCustomColor">Uses this section's Visual Style above (Light = orange-to-red gradient, Sale = white) unless you set a custom color here.</p>

        <div class="bg-gray-50 border rounded-xl p-4 flex items-center gap-3">
            <span class="text-xs text-gray-400 flex-shrink-0">Preview:</span>
            <button type="button" tabindex="-1"
                class="inline-flex items-center gap-2 px-6 py-2 rounded-full font-bold text-xs shadow-sm"
                :style="useCustomColor ? { backgroundImage: `linear-gradient(to right, ${from}, ${to})`, color: text } : {}"
                :class="!useCustomColor && 'bg-gradient-to-r from-orange-500 to-red-500 text-white'">
                <span x-text="label || 'See More'"></span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
            </button>
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
