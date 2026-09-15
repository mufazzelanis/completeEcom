{{-- Site-wide animated "in transit" overlay — shown the instant a shopping action fires
     (Add to Cart / Buy Now / Place Order), for the brief moment a plain server-rendered
     <form> POST takes to redirect to the next real page. Nothing here fakes or delays that
     navigation; it just makes the moment feel deliberate and on-brand instead of a blank
     flash. Included once, near the top of <body>, so it's available to every page.

     Hidden via the `hidden` attribute by default — the only thing that ever un-hides it is
     the delegated submit listener below, so a visitor with JS disabled never sees it and
     every form still submits and works normally either way.

     The outer element carries ONLY `hidden` plus non-display utilities (fixed/inset/z/bg) —
     `flex` lives on the inner wrapper instead. Tailwind's own [hidden] reset is deliberately
     zero-specificity (`:where([hidden])`) so authors can override it on purpose; putting
     `flex` directly on this same element would do exactly that by accident and the overlay
     would stay visible (display:flex) even while `hidden` is set. --}}
<div id="delivery-loader-overlay" hidden
     class="fixed inset-0 z-[9999] bg-gray-900/40 backdrop-blur-sm">
    <div class="w-full h-full flex items-center justify-center px-4">
    <div class="bg-white rounded-3xl shadow-2xl px-10 py-9 flex flex-col items-center gap-5 max-w-xs w-full dl-pop-in">
        <div class="relative w-24 h-16 flex items-center justify-center">
            {{-- Road streaking by underneath, suggesting motion --}}
            <div class="absolute bottom-1 left-0 right-0 h-0.5 overflow-hidden rounded-full">
                <div class="dl-road"></div>
            </div>

            {{-- Default state: a package in transit --}}
            <svg data-loader-icon="package" class="w-12 h-12 text-orange-500 dl-bounce" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7L12 3 4 7m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>

            {{-- Success state — hidden by default; only the Thank You page's own script
                 ever swaps this in, morphing the package into a confirmation check. --}}
            <svg data-loader-icon="success" hidden class="w-12 h-12 text-green-500 dl-pop-in" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10" fill="currentColor" class="opacity-10" stroke="none"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12.5l2.5 2.5L16 9"/>
            </svg>
        </div>

        <div class="w-full">
            <div class="h-1.5 w-full bg-gray-100 rounded-full overflow-hidden">
                <div data-loader-bar class="h-full w-1/3 bg-gradient-to-r from-orange-400 via-orange-500 to-orange-400 rounded-full dl-sweep"></div>
            </div>
        </div>

        <p data-loader-message class="text-sm font-medium text-gray-700 text-center">Please wait&hellip;</p>
    </div>
    </div>
</div>

<style>
    @keyframes dl-bounce-kf { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-6px); } }
    .dl-bounce { animation: dl-bounce-kf 0.9s ease-in-out infinite; }

    @keyframes dl-sweep-kf { 0% { transform: translateX(-120%); } 100% { transform: translateX(340%); } }
    .dl-sweep { animation: dl-sweep-kf 1.1s ease-in-out infinite; }

    .dl-road {
        position: absolute; inset: 0;
        background-image: repeating-linear-gradient(90deg, #e5e7eb 0 10px, transparent 10px 24px);
        animation: dl-road-kf 0.6s linear infinite;
    }
    @keyframes dl-road-kf { 0% { transform: translateX(0); } 100% { transform: translateX(-24px); } }

    @keyframes dl-pop-in-kf { from { opacity: 0; transform: scale(0.85); } to { opacity: 1; transform: scale(1); } }
    .dl-pop-in { animation: dl-pop-in-kf 0.28s ease-out; }

    @media (prefers-reduced-motion: reduce) {
        .dl-bounce, .dl-sweep, .dl-road, .dl-pop-in { animation: none !important; }
    }
</style>

<script>
    function showDeliveryLoader(message) {
        const overlay = document.getElementById('delivery-loader-overlay');
        if (!overlay) return;
        const msgEl = overlay.querySelector('[data-loader-message]');
        if (msgEl) msgEl.textContent = message || 'Please wait…';
        overlay.hidden = false;
    }

    function hideDeliveryLoader() {
        const overlay = document.getElementById('delivery-loader-overlay');
        if (overlay) overlay.hidden = true;
    }

    // Any <form data-show-loader> shows this the instant it's submitted. The specific button
    // clicked (standard SubmitEvent.submitter) can carry its own data-loader-message to
    // override the form's default — e.g. one form with both an "Add to Cart" and a "Buy Now"
    // button shows the right wording for whichever was actually pressed.
    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-show-loader')) return;
        const submitter = e.submitter;
        const message = (submitter && submitter.dataset.loaderMessage) || form.dataset.loaderMessage;
        showDeliveryLoader(message);

        // Safety valve only — a normal submit navigates away long before this and the
        // overlay (and this whole page) is discarded with it. This just guards the rare
        // case something prevents that navigation from ever completing.
        setTimeout(hideDeliveryLoader, 20000);
    });
</script>
