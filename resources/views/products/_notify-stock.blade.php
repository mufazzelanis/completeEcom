{{-- Out of stock → "Notify me when it's back": a phone number, sent without leaving the page
     (StockAlertController). Plus a WhatsApp link to ask about it directly. --}}
@php $nsWa = whatsapp_chat_url("Hi, when will this be back in stock?\n{$product->name}\n" . route('products.show', $product)); @endphp
<div class="mb-6 rounded-2xl p-4 sm:p-5" style="background: linear-gradient(135deg, rgba(99,102,241,.08), rgba(236,72,153,.06)); border: 1px solid rgba(99,102,241,.22);"
     x-data="{ phone: '', busy: false, done: false, msg: '', bad: false,
        async send() {
            if (this.busy) return;
            this.busy = true; this.msg = ''; this.bad = false;
            try {
                const r = await fetch(@js(route('stock-alerts.store', $product)), {
                    method: 'POST', credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify({ phone: this.phone }),
                });
                const d = await r.json().catch(() => ({}));
                this.msg = d.message || (r.status === 429 ? 'Too many tries — please wait a minute.' : 'Something went wrong.');
                this.done = r.ok; this.bad = !r.ok;
            } catch (e) { this.msg = 'Network problem — please try again.'; this.bad = true; }
            finally { this.busy = false; }
        } }">
    <p class="font-bold text-gray-900 dark:text-gray-100">🔔 Get notified when it's back</p>
    <p class="text-sm text-gray-600 dark:text-gray-300 mt-0.5">Leave your number — we'll let you know the moment it's in stock again.</p>

    <form x-show="!done" @submit.prevent="send()" class="mt-3 flex gap-2">
        <input type="tel" inputmode="tel" x-model="phone" required placeholder="01XXXXXXXXX" autocomplete="tel" aria-label="Mobile number"
               class="flex-1 min-w-0 border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 rounded-xl px-4 py-3 text-base focus:outline-none focus:ring-2 focus:ring-indigo-500">
        <button type="submit" :disabled="busy" class="flex-shrink-0 px-5 py-3 rounded-xl font-bold text-white transition hover:brightness-110 disabled:opacity-60" style="background: linear-gradient(135deg, #4f46e5, #7c3aed);">
            <span x-show="!busy">Notify me</span><span x-show="busy" x-cloak>…</span>
        </button>
    </form>
    <p x-show="msg" x-cloak class="mt-2 text-sm font-medium" :style="bad ? 'color:#dc2626' : 'color:#059669'" x-text="(done ? '✓ ' : '') + msg" aria-live="polite"></p>

    @if($nsWa)
        <a href="{{ $nsWa }}" target="_blank" rel="noopener nofollow" class="inline-flex items-center gap-1.5 mt-3 text-sm font-semibold hover:underline" style="color:#16a34a;">
            <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M17.47 14.38c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.16-.17.2-.35.22-.64.07-.3-.15-1.26-.46-2.4-1.48-.88-.79-1.48-1.76-1.65-2.06-.17-.3-.02-.46.13-.6.13-.14.3-.35.45-.52.15-.18.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.61-.92-2.2-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.48s1.07 2.88 1.21 3.07c.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.7.63.71.22 1.36.19 1.87.12.57-.09 1.76-.72 2-1.41.25-.7.25-1.29.18-1.41-.07-.13-.27-.2-.57-.35M12.05 21.79h-.01a9.87 9.87 0 01-5.03-1.38l-.36-.21-3.74.98 1-3.65-.24-.37a9.86 9.86 0 01-1.51-5.26c0-5.45 4.44-9.88 9.89-9.88 2.64 0 5.12 1.03 6.99 2.9a9.82 9.82 0 012.89 6.99c0 5.45-4.44 9.88-9.88 9.88M20.46 3.49A11.82 11.82 0 0012.05 0C5.5 0 .16 5.34.16 11.89c0 2.1.55 4.14 1.59 5.94L.06 24l6.3-1.65a11.88 11.88 0 005.68 1.45h.01c6.55 0 11.89-5.34 11.89-11.89 0-3.18-1.24-6.16-3.48-8.41"/></svg>
            Ask about it on WhatsApp
        </a>
    @endif
</div>
