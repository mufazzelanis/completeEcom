{{-- Shared coupon/promo apply box (cart + checkout) — applies/removes via fetch() for
     instant, animated feedback instead of the old full-page POST-redirect-reload, which
     looked and felt like a form error even on success. Falls back to a plain form submit
     if JS fails (CartController still accepts and redirects a non-JSON request). --}}
<div class="bg-white rounded-xl shadow-sm p-6 mb-6" x-data="couponBox(@js($coupon ?? $promoCode ?? null))">
    <h3 class="font-semibold text-gray-800 mb-3 text-sm flex items-center gap-1.5">
        <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v3a2 2 0 010 4v3a2 2 0 002 2h2m6-14h2a2 2 0 012 2v3a2 2 0 000 4v3a2 2 0 01-2 2h-2M9 9h6m-6 3h6m-6 3h3"/></svg>
        Coupon / Promo Code
    </h3>

    <div x-show="applied" x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100">
        <div class="flex items-center justify-between bg-green-50 border border-green-200 rounded-xl px-4 py-3">
            <div class="flex items-center gap-2.5">
                <svg class="w-8 h-8 text-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10" class="coupon-check-ring" fill="currentColor" fill-opacity="0.12"/>
                    <path class="coupon-check-mark" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" d="M7 12.5l3 3 7-7"/>
                </svg>
                <div>
                    <p class="text-sm font-semibold text-green-700" x-text="appliedCode"></p>
                    <p class="text-xs text-green-500">Discount applied!</p>
                </div>
            </div>
            <button type="button" @click="remove" :disabled="loading" class="text-red-400 hover:text-red-600 text-xs font-medium disabled:opacity-50 flex-shrink-0">
                <span x-show="!loading">Remove</span>
                <svg x-show="loading" x-cloak class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
            </button>
        </div>
    </div>

    <form x-show="!applied" @submit.prevent="apply" class="flex space-x-2">
        <input type="text" name="code" x-model="code" placeholder="Enter coupon or promo code"
            :class="error ? 'coupon-shake border-red-400 ring-2 ring-red-100' : 'border-gray-200'"
            class="flex-1 min-w-0 border rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 uppercase transition-colors"
            @animationend="$el.classList.remove('coupon-shake')">
        <button type="submit" :disabled="loading || !code.trim()"
            class="bg-orange-500 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-orange-600 transition flex-shrink-0 disabled:opacity-60 min-w-[76px] flex items-center justify-center">
            <svg x-show="loading" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
            <span x-show="!loading">Apply</span>
        </button>
    </form>
    <p x-show="fromOffer && !applied && !error" x-cloak class="text-xs mt-2 font-medium" style="color:#16a34a">🎁 Your offer code is filled in — tap <b>Apply</b> to save.</p>
    <p x-show="error" x-cloak x-text="error" x-transition.opacity class="text-red-500 text-xs mt-2"></p>
</div>

<style>
/* Scoped to this partial's own elements — a quick shake tells "wrong code" without a
   jarring page reload, and a drawn-on checkmark mirrors the same "something just
   succeeded" language the quick-add-to-cart buttons already use elsewhere on the site. */
@keyframes coupon-shake {
    10%, 90% { transform: translateX(-1px); }
    20%, 80% { transform: translateX(2px); }
    30%, 50%, 70% { transform: translateX(-4px); }
    40%, 60% { transform: translateX(4px); }
}
.coupon-shake { animation: coupon-shake 0.4s cubic-bezier(.36,.07,.19,.97) both; }

.coupon-check-mark {
    stroke-dasharray: 20;
    stroke-dashoffset: 20;
    animation: coupon-check-draw 0.35s ease-out 0.1s forwards;
}
.coupon-check-ring { transform-origin: center; animation: coupon-check-pop 0.35s ease-out; }
@keyframes coupon-check-draw { to { stroke-dashoffset: 0; } }
@keyframes coupon-check-pop { 0% { transform: scale(0); opacity: 0; } 60% { transform: scale(1.15); opacity: 1; } 100% { transform: scale(1); } }
</style>

<script>
function couponBox(initial) {
    return {
        applied: !!initial,
        appliedCode: initial || '',
        code: '',
        fromOffer: false,
        loading: false,
        error: '',
        // a code copied from the home page's offer ticket (layouts/app 'mv-coupon') is filled in, ready to Apply
        init() {
            if (this.applied) return;
            try { const c = localStorage.getItem('mv-coupon'); if (c) { this.code = c; this.fromOffer = true; } } catch (e) {}
        },
        async apply() {
            if (!this.code.trim() || this.loading) return;
            this.loading = true;
            this.error = '';
            try {
                const res = await fetch(@js(route('cart.coupon')), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ code: this.code }),
                });
                const data = await res.json();
                if (!res.ok || data.status === 'error') {
                    this.error = data.message || 'Invalid or expired code.';
                    this.loading = false;
                    return;
                }
                this.appliedCode = data.code || this.code.toUpperCase();
                this.applied = true; this.fromOffer = false;
                try { localStorage.removeItem('mv-coupon'); } catch (e) {}
                if (typeof showToast === 'function') showToast(data.message || 'Coupon applied successfully!');
                // Totals (discount, total, grand total) live server-side only — a short
                // reload after the success animation plays keeps this box and the rest of
                // the order summary from ever showing two different answers.
                setTimeout(() => window.location.reload(), 650);
            } catch (e) {
                this.error = 'Something went wrong — please try again.';
                this.loading = false;
            }
        },
        async remove() {
            if (this.loading) return;
            this.loading = true;
            try {
                const res = await fetch(@js(route('cart.coupon.remove')), {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                });
                const data = await res.json().catch(() => ({}));
                if (typeof showToast === 'function') showToast(data.message || 'Discount code removed.');
                setTimeout(() => window.location.reload(), 400);
            } catch (e) {
                if (typeof showToast === 'function') showToast('Something went wrong — please try again.', 'error');
                this.loading = false;
            }
        },
    };
}
</script>
