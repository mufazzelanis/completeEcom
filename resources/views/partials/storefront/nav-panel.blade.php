{{-- One desktop category dropdown (sub-categories + View All) — fetched on the first hover
     (layouts/app.blade.php, NavPanelController) instead of being in every page's HTML. --}}
<div class="h-1 bg-gradient-to-r from-orange-500 via-pink-500 to-orange-400"></div>
<div class="p-1.5 pt-2">
    @foreach($navCat->children as $child)
        <a href="{{ route('shop.category', $child->slug) }}"
           class="group flex items-center gap-3 px-2.5 py-2.5 rounded-lg hover:bg-orange-50 dark:hover:bg-orange-500/10 transition-colors duration-150">
            <span class="flex-shrink-0 w-9 h-9 rounded-lg bg-gradient-to-br from-orange-100 to-amber-50 dark:from-orange-500/20 dark:to-amber-500/10 flex items-center justify-center overflow-hidden group-hover:scale-105 transition-transform duration-200">
                @if($child->image)
                    <img src="{{ image_thumb($child->image, 96) }}" alt="" width="96" height="96" loading="lazy" decoding="async" class="w-full h-full object-cover">
                @else
                    <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z"/><circle cx="7" cy="7" r="1.4" fill="currentColor" stroke="none"/></svg>
                @endif
            </span>
            <span class="flex-1 min-w-0">
                <span class="block text-sm font-medium text-gray-700 dark:text-gray-200 group-hover:text-orange-700 dark:group-hover:text-orange-400 transition-colors truncate">{{ $child->name }}</span>
                @if($child->products_count > 0)
                    <span class="block text-[11px] text-gray-400 dark:text-gray-500">{{ $child->products_count }} products</span>
                @endif
            </span>
            <svg class="w-3.5 h-3.5 flex-shrink-0 text-gray-300 dark:text-gray-600 opacity-0 -translate-x-1 group-hover:opacity-100 group-hover:translate-x-0 group-hover:text-orange-500 transition-all duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
        </a>
    @endforeach
</div>
<div class="p-1.5 pt-0">
    <a href="{{ route('shop.category', $navCat->slug) }}"
       class="flex items-center justify-center gap-1.5 px-3 py-2.5 rounded-lg text-xs font-semibold text-orange-600 dark:text-orange-400 bg-orange-50/60 dark:bg-orange-500/10 hover:bg-orange-100 dark:hover:bg-orange-500/20 transition-colors">
        View All {{ $navCat->name }}
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
    </a>
</div>
