{{-- One card in a homepage product section — shared by home.blade.php and
     HomeController::sectionProducts (lazy-loaded sections), so both render it identically.
     $hideOnMobile: within the admin's product limit, so always shown on desktop, but past the
     section's mobile cap — on mobile it waits behind "More" alongside the overflow. --}}
@if($hideOnMobile ?? false)
    <div x-show="expanded" x-cloak class="sm:!block"
         x-transition:enter="transition ease-out duration-400" x-transition:enter-start="opacity-0 translate-y-3" x-transition:enter-end="opacity-100 translate-y-0">
        @include('partials.product-card', ['product' => $product])
    </div>
@else
    @include('partials.product-card', ['product' => $product])
@endif
