{{-- Mobile Dynamic Search Bar — hidden until the customer scrolls past the first screen,
     then slides down and pins itself right under the header. Desktop already has its own
     always-visible search bar (header-search.blade.php); this exists purely so a mobile
     customer further down the page doesn't have to scroll back to the top or open the full
     menu drawer just to search. Visibility is driven by the global Alpine.store
     ('mobileSearchBar') (resources/js/app.js) — one window-level scroll listener shared
     across the whole Turbo session, not a fresh one per page (see that store's own comment
     for why a page-scoped listener would leak under Turbo).

     `top` is conditional, not a flat 0 — when the header above is itself sticky
     ($stickyHeader), this bar needs to pin BELOW it (var(--header-height), kept in sync by
     a ResizeObserver in app.js) rather than at the same top:0 offset: two sibling sticky
     elements both at top:0 don't auto-stack, they occupy the exact same spot, and <header>'s
     higher z-index (z-50 vs this bar's z-30) was winning there, hiding this bar completely
     regardless of its own visible/hidden state. When the header ISN'T sticky it scrolls
     away entirely, so this bar correctly becomes the topmost sticky element at top:0 once
     reached — no offset needed in that case. --}}
{{-- Not on a product page: there the screen belongs to the product and its buy buttons (the
     header's search icon still opens search) — the pinned bar took ~60px from the photo/price. --}}
@unless(request()->routeIs('products.show', 'cart.index', 'checkout.index'))
<div class="md:hidden sticky z-30" style="top: {{ $stickyHeader ? 'var(--header-height, 56px)' : '0px' }};">
    <div x-show="$store.mobileSearchBar.visible" x-cloak
         x-transition:enter="transition ease-out duration-250" x-transition:enter-start="opacity-0 -translate-y-3" x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-3"
         class="bg-white/95 dark:bg-gray-900/95 backdrop-blur-md shadow-md border-b border-gray-100 dark:border-gray-800 px-3 py-2">
        {{-- the same smart search as the desktop bar (partials/storefront/smart-search) --}}
        @include('partials.storefront.smart-search', ['variant' => 'bar'])
    </div>
</div>
@endunless
