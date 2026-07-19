<form action="{{ route('shop.index') }}" method="GET" id="filter-form">

    <div class="mb-5">
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Search</label>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search products..."
            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
    </div>

    <div class="mb-5">
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Quick Filters</label>
        <div class="space-y-2">
            <label class="flex items-center gap-2 cursor-pointer select-none">
                <input type="checkbox" name="in_stock" value="1" {{ request('in_stock') ? 'checked' : '' }}
                    class="rounded text-orange-500 focus:ring-orange-500">
                <span class="text-sm text-gray-700">In Stock Only</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer select-none">
                <input type="checkbox" name="on_sale" value="1" {{ request('on_sale') ? 'checked' : '' }}
                    class="rounded text-orange-500 focus:ring-orange-500">
                <span class="text-sm text-gray-700">On Sale</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer select-none">
                <input type="checkbox" name="featured" value="1" {{ request('featured') ? 'checked' : '' }}
                    class="rounded text-orange-500 focus:ring-orange-500">
                <span class="text-sm text-gray-700">Featured</span>
            </label>
        </div>
    </div>

    <div class="mb-5" x-data="{ expanded: true }">
        <button type="button" @click="expanded = !expanded"
            class="flex items-center justify-between w-full text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
            <span>Category</span>
            <svg class="w-3.5 h-3.5 transition-transform" :class="expanded ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div x-show="expanded" class="space-y-1.5 max-h-56 overflow-y-auto pr-1">
            <label class="flex items-center gap-2 cursor-pointer py-0.5">
                <input type="radio" name="category" value="" {{ !request()->query('category') ? 'checked' : '' }} class="text-orange-500">
                <span class="text-sm text-gray-600">All Categories</span>
            </label>
            @foreach($categories as $cat)
                <label class="flex items-center gap-2 cursor-pointer py-0.5">
                    <input type="radio" name="category" value="{{ $cat->slug }}"
                        {{ request()->query('category') === $cat->slug ? 'checked' : '' }} class="text-orange-500">
                    <span class="text-sm text-gray-600">{{ $cat->name }} <span class="text-gray-400 text-xs">({{ $cat->products_count }})</span></span>
                </label>
                @foreach($cat->children as $child)
                    <label class="flex items-center gap-2 cursor-pointer py-0.5 pl-4">
                        <input type="radio" name="category" value="{{ $child->slug }}"
                            {{ request()->query('category') === $child->slug ? 'checked' : '' }} class="text-orange-500">
                        <span class="text-sm text-gray-500">{{ $child->name }} <span class="text-gray-400 text-xs">({{ $child->products_count }})</span></span>
                    </label>
                @endforeach
            @endforeach
        </div>
    </div>

    @if($brands->count() > 0)
    <div class="mb-5" x-data="{ expanded: true }">
        <button type="button" @click="expanded = !expanded"
            class="flex items-center justify-between w-full text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
            <span>Brand</span>
            <svg class="w-3.5 h-3.5 transition-transform" :class="expanded ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div x-show="expanded" class="space-y-1.5 max-h-40 overflow-y-auto pr-1">
            <label class="flex items-center gap-2 cursor-pointer py-0.5">
                <input type="radio" name="brand" value="" {{ !request('brand') ? 'checked' : '' }} class="text-orange-500">
                <span class="text-sm text-gray-600">All Brands</span>
            </label>
            @foreach($brands as $brand)
                <label class="flex items-center gap-2 cursor-pointer py-0.5">
                    <input type="radio" name="brand" value="{{ $brand->slug }}"
                        {{ request('brand') === $brand->slug ? 'checked' : '' }} class="text-orange-500">
                    <span class="text-sm text-gray-600">{{ $brand->name }}</span>
                </label>
            @endforeach
        </div>
    </div>
    @endif

    <div class="mb-5">
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Price Range (৳)</label>
        <div class="flex items-center gap-2">
            <input type="number" name="min_price" value="{{ request('min_price') }}" placeholder="Min"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
            <span class="text-gray-400 text-sm">–</span>
            <input type="number" name="max_price" value="{{ request('max_price') }}" placeholder="Max"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
        </div>
    </div>

    @if($tags->count() > 0)
    <div class="mb-5" x-data="{ expanded: false }">
        <button type="button" @click="expanded = !expanded"
            class="flex items-center justify-between w-full text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
            <span>Tags</span>
            <svg class="w-3.5 h-3.5 transition-transform" :class="expanded ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div x-show="expanded" class="flex flex-wrap gap-1.5">
            @foreach($tags->take(20) as $tag)
                <label class="cursor-pointer">
                    <input type="radio" name="tag" value="{{ $tag->slug }}"
                        {{ request('tag') === $tag->slug ? 'checked' : '' }} class="sr-only peer">
                    <span class="inline-block px-2.5 py-1 rounded-full text-xs border border-gray-200 text-gray-600 peer-checked:bg-orange-500 peer-checked:text-white peer-checked:border-orange-500 hover:border-orange-300 transition cursor-pointer">{{ $tag->name }}</span>
                </label>
            @endforeach
            <label class="cursor-pointer">
                <input type="radio" name="tag" value="" {{ !request('tag') ? 'checked' : '' }} class="sr-only peer">
                <span class="inline-block px-2.5 py-1 rounded-full text-xs border border-gray-200 text-gray-500 peer-checked:bg-gray-700 peer-checked:text-white peer-checked:border-gray-700 hover:border-gray-400 transition cursor-pointer">All</span>
            </label>
        </div>
    </div>
    @endif

    <div class="mb-5">
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Sort By</label>
        <select name="sort" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
            <option value="latest"     {{ request('sort', 'latest') === 'latest'     ? 'selected' : '' }}>Latest</option>
            <option value="popular"    {{ request('sort') === 'popular'    ? 'selected' : '' }}>Most Popular</option>
            <option value="price_low"  {{ request('sort') === 'price_low'  ? 'selected' : '' }}>Price: Low to High</option>
            <option value="price_high" {{ request('sort') === 'price_high' ? 'selected' : '' }}>Price: High to Low</option>
            <option value="name"       {{ request('sort') === 'name'       ? 'selected' : '' }}>Name A–Z</option>
        </select>
    </div>

    <button type="submit" class="w-full bg-orange-500 text-white py-2.5 rounded-lg text-sm font-medium hover:bg-orange-600 transition">
        Apply Filters
    </button>
    @if($activeFilters > 0)
        <a href="{{ route('shop.index') }}" class="block text-center text-sm text-gray-500 mt-2 hover:text-gray-700">Clear All Filters</a>
    @endif
</form>
