@extends('layouts.app')
@section('title', 'Order Placed!')

@section('content')
<div id="confetti-container" class="pointer-events-none fixed inset-0 overflow-hidden z-[9998]" aria-hidden="true"></div>

@php
    // Thank-you page. Colours come from the brand (Settings → Theme) through --ty-brand, with
    // the success green fixed; everything is page-local CSS (ty-*) so it doesn't depend on which
    // Tailwind classes happen to be in the compiled build, and has its own dark-mode rules.
    $brand = setting('primary_color', '#ea580c');
    $firstName = \Illuminate\Support\Str::of($order->shipping_name ?: auth()->user()->name)->trim()->explode(' ')->first();
    $money = fn ($v) => '৳' . number_format((float) $v, fmod((float) $v, 1.0) == 0.0 ? 0 : 2);
    $isCod = in_array(strtolower((string) $order->payment_method), ['cod', 'cash_on_delivery'], true);
    $paymentLabel = \App\Services\Notifications\OrderEmail::paymentLabel($order->payment_method);
    $itemCount = (int) $order->items->sum('quantity');

    // estimated delivery window from Settings → Shipping → delivery days (e.g. "3-7");
    // a pre-order waits for new stock, so it uses Settings → Orders → Pre-orders → delivery time
    $isPre = (bool) $order->is_preorder;
    $preDue = $isPre ? max(0, (float) $order->total - (float) $order->advance_amount) : 0;
    $preVerified = $isPre && ($order->payment?->status === 'completed' || $order->payment_status === 'paid');
    preg_match_all('/\d+/', $isPre ? setting('preorder_days', '7-15') : setting('delivery_days', '3-7'), $dayNums);
    $dayNums = array_map('intval', $dayNums[0]) ?: [3, 7];
    $placedAt = $order->created_at->copy()->timezone(setting('timezone', 'Asia/Dhaka'));
    $etaFrom = $placedAt->copy()->addDays(min($dayNums));
    $etaTo = $placedAt->copy()->addDays(max($dayNums));
    $eta = $etaFrom->isSameDay($etaTo) ? $etaFrom->format('D, d M') : $etaFrom->format('D, d M') . ' – ' . $etaTo->format('D, d M');

    $helpWa = whatsapp_chat_url("Hi, I have a question about my order {$order->order_number}.");
    $addressLine = collect([$order->shipping_address, $order->shipping_city, $order->shipping_state])->map(fn ($p) => trim((string) $p, " ,"))->filter()->unique()->implode(', ');
@endphp

<style>
    .ty-wrap { --ty-brand: {{ $brand }}; --ty-ok: #10b981; --ty-card: #ffffff; --ty-soft: #f6f7fb; --ty-line: #eceef3; --ty-text: #111827; --ty-muted: #6b7280; max-width: 64rem; margin: 0 auto; padding: 2rem 1rem 4rem; }
    .dark .ty-wrap { --ty-card: #111827; --ty-soft: #1a2233; --ty-line: #263044; --ty-text: #f3f4f6; --ty-muted: #9ca3af; }
    .ty-card { background: var(--ty-card); border: 1px solid var(--ty-line); border-radius: 1.25rem; box-shadow: 0 10px 30px -18px rgba(15, 23, 42, .35); }
    .ty-hero { position: relative; overflow: hidden; text-align: center; padding: 2.5rem 1.5rem 2rem; }
    .ty-hero::before { content: ''; position: absolute; inset: 0 0 auto 0; height: 9rem; background: radial-gradient(60% 120% at 50% 0%, color-mix(in srgb, var(--ty-ok) 22%, transparent), transparent 70%); pointer-events: none; }
    .ty-check { position: relative; width: 5.5rem; height: 5.5rem; margin: 0 auto 1.25rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: var(--ty-ok); color: #fff; box-shadow: 0 12px 30px -8px color-mix(in srgb, var(--ty-ok) 70%, transparent); }
    .ty-check::after { content: ''; position: absolute; inset: -10px; border-radius: 50%; border: 3px solid color-mix(in srgb, var(--ty-ok) 35%, transparent); animation: ty-ring 2.2s ease-out infinite; }
    .ty-check path { stroke-dasharray: 30; stroke-dashoffset: 30; animation: ty-draw .6s .35s ease-out forwards; }
    @keyframes ty-draw { to { stroke-dashoffset: 0; } }
    @keyframes ty-ring { 0% { transform: scale(.85); opacity: 1; } 100% { transform: scale(1.25); opacity: 0; } }
    .ty-title { font-size: clamp(1.5rem, 4vw, 2rem); font-weight: 800; color: var(--ty-text); letter-spacing: -.02em; line-height: 1.2; }
    .ty-sub { color: var(--ty-muted); margin-top: .5rem; font-size: .975rem; line-height: 1.6; }
    .ty-sub strong { color: var(--ty-text); }
    .ty-pill { display: inline-flex; align-items: center; gap: .5rem; margin-top: 1.25rem; padding: .5rem .6rem .5rem 1rem; border-radius: 999px; background: var(--ty-soft); border: 1px dashed color-mix(in srgb, var(--ty-brand) 45%, transparent); }
    .ty-pill b { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; color: var(--ty-brand); letter-spacing: .02em; font-size: .95rem; }
    .ty-copy { border-radius: 999px; padding: .3rem .75rem; font-size: .75rem; font-weight: 700; background: var(--ty-brand); color: #fff; transition: transform .15s; }
    .ty-copy:active { transform: scale(.95); }
    .ty-meta { display: flex; flex-wrap: wrap; justify-content: center; gap: .5rem 1.25rem; margin-top: 1rem; font-size: .8rem; color: var(--ty-muted); }
    .ty-grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: 1.25rem; margin-top: 1.25rem; }
    @media (min-width: 1024px) { .ty-grid { grid-template-columns: minmax(0, 1.55fr) minmax(0, 1fr); } .ty-side { position: sticky; top: 6rem; } }
    .ty-sec { padding: 1.4rem 1.5rem; }
    .ty-h { font-size: .78rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: var(--ty-muted); margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center; }
    .ty-steps { position: relative; }
    .ty-step { position: relative; display: flex; gap: .9rem; padding-bottom: 1.15rem; }
    .ty-step:last-child { padding-bottom: 0; }
    .ty-step:not(:last-child)::before { content: ''; position: absolute; left: 1rem; top: 2.1rem; bottom: .15rem; width: 2px; transform: translateX(-1px); background: var(--ty-line); }
    .ty-step.is-done:not(:last-child)::before { background: var(--ty-ok); }
    .ty-dot { flex-shrink: 0; width: 2rem; height: 2rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: .95rem; background: var(--ty-soft); border: 2px solid var(--ty-line); color: var(--ty-muted); }
    .ty-step.is-done .ty-dot { background: var(--ty-ok); border-color: var(--ty-ok); color: #fff; }
    .ty-step.is-now .ty-dot { border-color: var(--ty-brand); color: var(--ty-brand); background: color-mix(in srgb, var(--ty-brand) 10%, var(--ty-card)); animation: ty-pulse 1.8s ease-in-out infinite; }
    @keyframes ty-pulse { 0%, 100% { box-shadow: 0 0 0 0 color-mix(in srgb, var(--ty-brand) 40%, transparent); } 50% { box-shadow: 0 0 0 7px transparent; } }
    .ty-step p { margin: 0; }
    .ty-step .t { font-weight: 700; color: var(--ty-text); font-size: .95rem; line-height: 2rem; }
    .ty-step .d { color: var(--ty-muted); font-size: .83rem; line-height: 1.5; margin-top: -.2rem; }
    .ty-item { display: flex; gap: .9rem; align-items: center; padding: .8rem 0; border-top: 1px solid var(--ty-line); }
    .ty-item:first-child { border-top: 0; padding-top: 0; }
    .ty-thumb { position: relative; flex-shrink: 0; width: 3.75rem; height: 3.75rem; border-radius: .85rem; background: var(--ty-soft); border: 1px solid var(--ty-line); }
    .ty-thumb img { width: 100%; height: 100%; object-fit: contain; border-radius: .85rem; padding: .2rem; }
    .ty-qty { position: absolute; top: -.45rem; right: -.45rem; min-width: 1.35rem; height: 1.35rem; padding: 0 .3rem; border-radius: 999px; background: var(--ty-text); color: var(--ty-card); font-size: .7rem; font-weight: 800; display: flex; align-items: center; justify-content: center; }
    .ty-row { display: flex; justify-content: space-between; gap: 1rem; font-size: .9rem; color: var(--ty-muted); padding: .2rem 0; }
    .ty-row b { color: var(--ty-text); font-weight: 600; }
    .ty-total { display: flex; justify-content: space-between; align-items: baseline; margin-top: .6rem; padding-top: .8rem; border-top: 1px dashed var(--ty-line); }
    .ty-total span { font-weight: 700; color: var(--ty-text); }
    .ty-total b { font-size: 1.6rem; font-weight: 800; color: var(--ty-brand); letter-spacing: -.02em; }
    .ty-info { display: flex; gap: .8rem; padding: .9rem 0; border-top: 1px solid var(--ty-line); }
    .ty-info .ic { flex-shrink: 0; width: 2.4rem; height: 2.4rem; border-radius: .8rem; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; background: var(--ty-soft); }
    .ty-info .k { font-size: .72rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--ty-muted); }
    .ty-info .v { color: var(--ty-text); font-weight: 600; font-size: .92rem; line-height: 1.45; margin-top: .1rem; }
    .ty-info .n { color: var(--ty-muted); font-size: .82rem; line-height: 1.45; }
    .ty-cod { margin-top: .5rem; padding: .6rem .8rem; border-radius: .8rem; font-size: .82rem; line-height: 1.45; background: color-mix(in srgb, #f59e0b 13%, var(--ty-card)); color: var(--ty-text); border: 1px solid color-mix(in srgb, #f59e0b 35%, transparent); }
    .ty-btn { display: flex; align-items: center; justify-content: center; gap: .5rem; width: 100%; padding: .85rem 1rem; border-radius: .9rem; font-weight: 700; font-size: .95rem; transition: transform .15s, box-shadow .15s, background-color .15s; }
    .ty-btn:hover { transform: translateY(-1px); }
    .ty-btn-main { background: var(--ty-brand); color: #fff; box-shadow: 0 10px 22px -10px var(--ty-brand); }
    .ty-btn-ghost { background: var(--ty-soft); color: var(--ty-text); border: 1px solid var(--ty-line); }
    .ty-help { display: flex; align-items: center; gap: .75rem; padding: 1rem 1.25rem; font-size: .85rem; color: var(--ty-muted); }
    .ty-help a { color: var(--ty-brand); font-weight: 700; }
    .ty-acct > :last-child { margin-bottom: 0 !important; }
    @media (prefers-reduced-motion: reduce) { .ty-check::after, .ty-step.is-now .ty-dot { animation: none; } .ty-check path { animation: none; stroke-dashoffset: 0; } }
</style>

<div class="ty-wrap">
    <div id="thankyou-card" class="dl-reveal">
        {{-- ── hero ─────────────────────────────────────────────────────── --}}
        <div class="ty-card ty-hero">
            <div class="ty-check" aria-hidden="true">
                <svg width="44" height="44" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.6" d="M5 13l4 4L19 7"/></svg>
            </div>
            <h1 class="ty-title">Thank you, {{ $firstName }}! 🎉</h1>
            <p class="ty-sub">
                @if($isPre)
                    Your pre-order is booked. We'll verify your advance, and ship it as soon as the new stock arrives — we'll call <strong>{{ $order->shipping_phone }}</strong> before sending it.
                @else
                    Your order has been placed successfully.
                    @if($isCod)We'll call <strong>{{ $order->shipping_phone }}</strong> shortly to confirm it.@else We'll start preparing it right away.@endif
                @endif
            </p>

            <div class="ty-pill" x-data="{ copied: false }">
                <span class="text-xs" style="color: var(--ty-muted)">Order</span>
                <b>{{ $order->order_number }}</b>
                <button type="button" class="ty-copy"
                        @click="navigator.clipboard?.writeText(@js($order->order_number)); copied = true; setTimeout(() => copied = false, 1600)"
                        x-text="copied ? '✓ Copied' : 'Copy'">Copy</button>
            </div>
            <div class="ty-meta">
                <span>🗓️ {{ $placedAt->format('d M Y, g:i A') }}</span>
                <span>📦 {{ $itemCount }} {{ \Illuminate\Support\Str::plural('item', $itemCount) }}</span>
                <span>🚚 {{ $isPre ? 'Expected' : 'Arrives' }} {{ $eta }}</span>
            </div>
            @if($order->is_preorder)
                <div class="mt-4 mx-auto max-w-lg rounded-xl p-3 text-sm text-left" style="background: rgba(124,58,237,.1); border: 1px solid rgba(124,58,237,.3);">
                    <p class="font-bold" style="color:#7c3aed;">📦 Pre-order booked</p>
                    <p style="color: var(--ty-text)">Advance <strong>৳{{ number_format($order->advance_amount) }}</strong> {{ $preVerified ? 'received' : 'sent' }} by {{ $paymentLabel }}{{ $preVerified ? '' : ' (we\'ll verify it shortly)' }} · pay <strong>৳{{ number_format($order->total - $order->advance_amount) }}</strong> on delivery.</p>
                    <p class="text-xs mt-1" style="color: var(--ty-muted)">{{ setting('preorder_note', 'We ship as soon as the new stock arrives — usually within 7–15 days. We\'ll call you to confirm.') }}</p>
                </div>
            @endif
        </div>

        @php $showAccountBox = ($accountCreated ?? false) || session('password_set') || session('can_set_password') === auth()->id(); @endphp
        <div class="max-w-2xl mx-auto ty-acct" @if($showAccountBox) style="margin-top:1.25rem" @endif>
        @if($accountCreated ?? false)
            <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-6 text-left">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <div>
                        <p class="text-sm font-semibold text-green-700">Account Created!</p>
                        <p class="text-sm text-green-600">Your account has been created with phone number <strong>{{ auth()->user()->phone }}</strong>.</p>
                        <p class="text-xs text-green-600 mt-1">Set a password now to log in later with your phone number — to track this order and rate the products once they arrive.</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- First password for the account just created (it's random otherwise, with no email to
             reset through) — offered for the whole session, so it survives a page refresh. --}}
        @if(session('password_set'))
            <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-6 text-left">
                <p class="text-sm font-semibold text-green-700">✓ Password saved — log in any time with {{ auth()->user()->phone }} and your password.</p>
            </div>
        @elseif(session('can_set_password') === auth()->id())
            {{-- Same <x-auth-field> as the login page (eye button to show/hide each field), plus a
                 live strength meter, match check and Caps Lock warning; Save stays disabled until
                 the password is valid and both fields match. Meter colors are inline hex, not
                 Tailwind classes — the orange/red palettes follow the admin's brand colors here,
                 which would turn a "weak" bar into brand blue. --}}
            <form method="POST" action="{{ route('account.set-password') }}" class="bg-white border border-gray-200 rounded-xl p-4 mb-6 text-left space-y-4"
                  x-data="{
                      pw: '', pw2: '', caps: false, saving: false,
                      get score() {
                          const p = this.pw; if (p.length < 6) return 0;
                          let s = 1;
                          if (p.length >= 10) s++;
                          if (/[a-zA-Z]/.test(p) && /\d/.test(p)) s++;
                          if (/[A-Z]/.test(p) && /[a-z]/.test(p) || /[^a-zA-Z0-9]/.test(p)) s++;
                          return Math.min(s, 4);
                      },
                      get level() { return [['Too short', '#9ca3af'], ['Weak', '#f43f5e'], ['Fair', '#f59e0b'], ['Good', '#14b8a6'], ['Strong', '#10b981']][this.score]; },
                      get matches() { return this.pw2.length > 0 && this.pw === this.pw2; },
                      get valid() { return this.pw.length >= 6 && this.matches; },
                      checkCaps(e) { this.caps = !!(e.getModifierState && e.getModifierState('CapsLock')); },
                  }"
                  @submit="if (!valid) { $event.preventDefault(); return; } saving = true">
                @csrf
                <div>
                    <p class="text-sm font-semibold text-gray-800">Set your password</p>
                    <p class="text-xs text-gray-500 mt-0.5">You'll log in with <strong>{{ auth()->user()->phone }}</strong> and this password.</p>
                </div>

                <div>
                    <x-auth-field id="new-password" name="password" type="password" icon="lock" label="Password"
                                  required minlength="6" autocomplete="new-password" placeholder="At least 6 characters"
                                  x-model="pw" x-on:keyup="checkCaps($event)" x-on:keydown="checkCaps($event)" />
                    {{-- strength meter: 4 segments filled up to the current score --}}
                    <div class="mt-2" x-show="pw.length" x-cloak>
                        <div class="flex gap-1" aria-hidden="true">
                            <template x-for="i in 4" :key="i">
                                <div class="h-1.5 flex-1 rounded-full bg-gray-200 dark:bg-gray-700 transition-colors"
                                     :style="i <= score ? `background-color: ${level[1]}` : ''"></div>
                            </template>
                        </div>
                        <p class="text-xs mt-1 font-medium" :style="`color: ${level[1]}`" aria-live="polite">
                            <span x-text="level[0]"></span><span x-show="score > 0 && score < 3" class="text-gray-500 font-normal"> — longer, with numbers and symbols, is safer</span>
                        </p>
                    </div>
                </div>

                <div>
                    <x-auth-field id="new-password-confirm" name="password_confirmation" type="password" icon="lock" label="Confirm password"
                                  required minlength="6" autocomplete="new-password" placeholder="Type it again"
                                  x-model="pw2" x-on:keyup="checkCaps($event)" x-on:keydown="checkCaps($event)" />
                    <p class="text-xs mt-1 font-medium" x-show="pw2.length" x-cloak aria-live="polite"
                       :style="`color: ${matches ? '#10b981' : '#f59e0b'}`"
                       x-text="matches ? '✓ Passwords match' : '✗ Passwords don\'t match yet'"></p>
                </div>

                <p class="text-xs font-medium text-amber-700 dark:text-yellow-400" x-show="caps" x-cloak>⚠ Caps Lock is on</p>

                <button type="submit" :disabled="!valid || saving" :class="(!valid || saving) && 'opacity-60 cursor-not-allowed'"
                        class="w-full bg-orange-700 text-white font-semibold py-2.5 rounded-lg text-sm hover:brightness-110 transition">
                    <span x-show="!saving">Save password</span>
                    <span x-show="saving" x-cloak>Saving…</span>
                </button>
            </form>
        @endif
        </div>

        <div class="ty-grid">
            <div class="space-y-5 min-w-0">
                {{-- ── what happens next ────────────────────────────────── --}}
                <div class="ty-card ty-sec">
                    <div class="ty-h"><span>What happens next</span></div>
                    <div class="ty-steps">
                        @if($isPre)
                        {{-- a pre-order's real path: advance check → stock arrives → ship → deliver --}}
                        <div class="ty-step is-done">
                            <span class="ty-dot">✓</span>
                            <div><p class="t">Pre-order booked</p><p class="d">{{ $placedAt->format('d M, g:i A') }} — your item is reserved from the next stock.</p></div>
                        </div>
                        <div class="ty-step {{ $preVerified ? 'is-done' : 'is-now' }}">
                            <span class="ty-dot">{{ $preVerified ? '✓' : '💳' }}</span>
                            <div><p class="t">{{ $preVerified ? 'Advance verified' : 'Advance verification' }}</p>
                                <p class="d">{{ $preVerified ? 'We\'ve received your ' . $money($order->advance_amount) . ' advance — thank you!' : 'We\'re checking your ' . $money($order->advance_amount) . ' ' . $paymentLabel . ' payment (TrxID). Usually within a few hours.' }}</p></div>
                        </div>
                        <div class="ty-step {{ $preVerified ? 'is-now' : '' }}">
                            <span class="ty-dot">📦</span>
                            <div><p class="t">New stock arrives — we call you</p><p class="d">As soon as it's in, we pack yours first and call {{ $order->shipping_phone }} to confirm.</p></div>
                        </div>
                        <div class="ty-step">
                            <span class="ty-dot">🏠</span>
                            <div><p class="t">Delivered to your door</p><p class="d">Expected <strong style="color: var(--ty-text)">{{ $eta }}</strong>{{ $preDue > 0 ? ' · pay ' . $money($preDue) . ' to the rider' : '' }}.</p></div>
                        </div>
                        @else
                        <div class="ty-step is-done">
                            <span class="ty-dot">✓</span>
                            <div><p class="t">Order placed</p><p class="d">{{ $placedAt->format('d M, g:i A') }} — we've got it and sent you a confirmation.</p></div>
                        </div>
                        <div class="ty-step is-now">
                            <span class="ty-dot">📞</span>
                            <div><p class="t">{{ $isCod ? 'Confirmation call' : 'Order confirmation' }}</p>
                                <p class="d">{{ $isCod ? 'Our team will call you to confirm the order — please keep your phone close.' : 'Our team is reviewing your order and payment.' }}</p></div>
                        </div>
                        <div class="ty-step">
                            <span class="ty-dot">📦</span>
                            <div><p class="t">Packed &amp; handed to courier</p><p class="d">Carefully packed and sent out — you'll get an update when it ships.</p></div>
                        </div>
                        <div class="ty-step">
                            <span class="ty-dot">🏠</span>
                            <div><p class="t">Delivered to your door</p><p class="d">Estimated <strong style="color: var(--ty-text)">{{ $eta }}</strong>{{ $isCod ? ' · pay the rider on delivery' : '' }}.</p></div>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- ── items ────────────────────────────────────────────── --}}
                <div class="ty-card ty-sec">
                    <div class="ty-h"><span>Your order</span><span style="text-transform:none; letter-spacing:0; font-weight:600">{{ $itemCount }} {{ \Illuminate\Support\Str::plural('item', $itemCount) }}</span></div>
                    @foreach($order->items as $item)
                        @php $img = $item->product?->image ? image_thumb($item->product->image, 200) : null; @endphp
                        <div class="ty-item">
                            <div class="ty-thumb">
                                @if($img)<img src="{{ $img }}" alt="" loading="lazy">@endif
                                <span class="ty-qty">{{ $item->quantity }}</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                @if($item->product)
                                    <a href="{{ route('products.show', $item->product) }}" class="block font-semibold text-sm leading-snug hover:underline" style="color: var(--ty-text)">{{ $item->product_name }}</a>
                                @else
                                    <p class="font-semibold text-sm leading-snug" style="color: var(--ty-text)">{{ $item->product_name }}</p>
                                @endif
                                <p class="text-xs mt-0.5" style="color: var(--ty-muted)">{{ $item->variant_label ? $item->variant_label . ' · ' : '' }}{{ $item->quantity }} × {{ $money($item->price) }}</p>
                            </div>
                            <p class="font-bold text-sm whitespace-nowrap" style="color: var(--ty-text)">{{ $money($item->subtotal) }}</p>
                        </div>
                    @endforeach
                    <div style="margin-top: .9rem; padding-top: .7rem; border-top: 1px solid var(--ty-line);">
                        <div class="ty-row"><span>Subtotal</span><b>{{ $money($order->subtotal) }}</b></div>
                        @if($order->discount > 0)
                            <div class="ty-row" style="color:#10b981"><span>Discount{{ $order->coupon_code ? ' (' . $order->coupon_code . ')' : '' }}</span><b style="color:#10b981">−{{ $money($order->discount) }}</b></div>
                        @endif
                        <div class="ty-row"><span>Delivery charge</span><b>{{ $order->shipping > 0 ? $money($order->shipping) : 'Free' }}</b></div>
                        @if($order->tax > 0)
                            <div class="ty-row"><span>Tax</span><b>{{ $money($order->tax) }}</b></div>
                        @endif
                        <div class="ty-total"><span>Total</span><b>{{ $money($order->total) }}</b></div>
                        @if($isPre)
                            <div class="ty-row" style="color:#7c3aed"><span>Advance {{ $preVerified ? 'paid' : 'sent' }} ({{ $paymentLabel }})</span><b style="color:#7c3aed">−{{ $money($order->advance_amount) }}</b></div>
                            <div class="ty-row"><span><strong style="color: var(--ty-text)">Pay on delivery</strong></span><b>{{ $money($preDue) }}</b></div>
                        @endif
                    </div>
                </div>

        @php $digitalItems = $order->items->filter(fn ($item) => $item->product?->isDigital()); @endphp
        @if($digitalItems->isNotEmpty())
        <div class="bg-indigo-50 border border-indigo-100 rounded-2xl p-6 text-left">
            <h3 class="font-semibold text-indigo-800 mb-3 text-sm">Your digital downloads are ready</h3>
            <div class="space-y-2">
                @foreach($digitalItems as $item)
                    <a href="{{ route('orders.download', [$order, $item]) }}" data-turbo="false" class="flex items-center justify-between bg-white rounded-lg px-4 py-3 hover:bg-indigo-50/50 transition">
                        <span class="text-sm text-gray-700 dark:text-gray-700">{{ $item->product_name }}</span>
                        <span class="inline-flex items-center gap-1 text-indigo-600 text-xs font-semibold">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-8-3V4m0 12l-4-4m4 4l4-4"/></svg>
                            Download
                        </span>
                    </a>
                @endforeach
            </div>
            <p class="text-xs text-indigo-400 mt-3">If your payment needs manual verification, downloads unlock once it's confirmed.</p>
        </div>
        @endif
            </div>

            {{-- ── side: delivery, payment, actions ─────────────────────── --}}
            <div class="ty-side space-y-5 min-w-0">
                <div class="ty-card ty-sec">
                    <div class="ty-h" style="margin-bottom:.25rem"><span>Delivery &amp; payment</span></div>
                    <div class="ty-info" style="border-top:0">
                        <span class="ic">📍</span>
                        <div class="min-w-0">
                            <p class="k">Delivering to</p>
                            <p class="v">{{ $order->shipping_name }}</p>
                            <p class="n">{{ $addressLine }}</p>
                            <p class="n">{{ $order->shipping_phone }}</p>
                        </div>
                    </div>
                    <div class="ty-info">
                        <span class="ic">{{ $isCod ? '💵' : '💳' }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="k">Payment</p>
                            <p class="v">{{ $paymentLabel }}</p>
                            @if($isPre)
                                <p class="n" style="{{ $preVerified ? 'color:#10b981;' : 'color:#d97706;' }} font-weight:700">
                                    {{ $preVerified ? '✓ Advance ' . $money($order->advance_amount) . ' received' : '⏳ Advance ' . $money($order->advance_amount) . ' — verifying' }}
                                </p>
                                @if($preDue > 0)<div class="ty-cod">Pay the remaining <strong>{{ $money($preDue) }}</strong> to the rider on delivery.</div>@endif
                            @elseif($order->payment_status === 'paid')
                                <p class="n" style="color:#10b981; font-weight:700">✓ Paid</p>
                            @elseif($isCod)
                                <div class="ty-cod">Please keep <strong>{{ $money($order->total) }}</strong> ready for the rider.</div>
                            @endif
                        </div>
                    </div>
                    <div class="ty-info">
                        <span class="ic">🚚</span>
                        <div>
                            <p class="k">{{ $isPre ? 'Expected delivery (pre-order)' : 'Estimated delivery' }}</p>
                            <p class="v">{{ $eta }}</p>
                            @if($isPre)<p class="n">Ships when the new stock arrives — we'll call you to confirm the exact day.</p>@endif
                        </div>
                    </div>

                    <div class="space-y-2.5" style="margin-top: .75rem">
                        <a href="{{ route('orders.show', $order->id) }}" class="ty-btn ty-btn-main">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                            Track my order
                        </a>
                        <a href="{{ route('shop.index') }}" class="ty-btn ty-btn-ghost">Continue shopping →</a>
                    </div>
                </div>

                @if($helpWa)
                    <a href="{{ $helpWa }}" target="_blank" rel="noopener nofollow" class="ty-card ty-help" style="transition: transform .15s;" onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform=''">
                        <span style="flex-shrink:0; width:2.4rem; height:2.4rem; border-radius:50%; background:#25d366; color:#fff; display:flex; align-items:center; justify-content:center;">
                            <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M17.47 14.38c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.16-.17.2-.35.22-.64.07-.3-.15-1.26-.46-2.4-1.48-.88-.79-1.48-1.76-1.65-2.06-.17-.3-.02-.46.13-.6.13-.14.3-.35.45-.52.15-.18.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.61-.92-2.2-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.48s1.07 2.88 1.21 3.07c.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.7.63.71.22 1.36.19 1.87.12.57-.09 1.76-.72 2-1.41.25-.7.25-1.29.18-1.41-.07-.13-.27-.2-.57-.35M12.05 21.79h-.01a9.87 9.87 0 01-5.03-1.38l-.36-.21-3.74.98 1-3.65-.24-.37a9.86 9.86 0 01-1.51-5.26c0-5.45 4.44-9.88 9.89-9.88 2.64 0 5.12 1.03 6.99 2.9a9.82 9.82 0 012.89 6.99c0 5.45-4.44 9.88-9.88 9.88M20.46 3.49A11.82 11.82 0 0012.05 0C5.5 0 .16 5.34.16 11.89c0 2.1.55 4.14 1.59 5.94L.06 24l6.3-1.65a11.88 11.88 0 005.68 1.45h.01c6.55 0 11.89-5.34 11.89-11.89 0-3.18-1.24-6.16-3.48-8.41"/></svg>
                        </span>
                        <div>Questions about your order?<br><strong style="color:#16a34a;">Chat with us on WhatsApp →</strong></div>
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
    /* Real content is always in the DOM and visible-by-default (no-JS safety) — the overlay
       from partials/delivery-loader.blade.php sits on top of it the whole time regardless,
       so there's nothing to actually hide here. This just adds a nice fade+rise the instant
       that overlay clears, via the .dl-revealed toggle the script below adds. */
    #thankyou-card.dl-reveal { opacity: 0; transform: translateY(10px); }
    #thankyou-card.dl-reveal.dl-revealed { opacity: 1; transform: translateY(0); transition: opacity 0.45s ease, transform 0.45s ease; }
    @media (prefers-reduced-motion: reduce) {
        #thankyou-card.dl-reveal { opacity: 1; transform: none; }
    }

    /* Confetti burst — a two-layer trick: the outer piece falls straight down while
       spinning (confetti-fall), and the inner piece independently sways side to side
       (confetti-sway) so the whole thing flutters instead of dropping like a rock. */
    .confetti-piece {
        position: absolute;
        top: -5vh;
        will-change: transform, opacity;
        animation: confetti-fall linear forwards;
    }
    .confetti-piece-inner {
        display: block;
        animation: confetti-sway ease-in-out infinite alternate;
    }
    @keyframes confetti-fall {
        0%   { transform: translateY(0) rotate(0deg); opacity: 1; }
        85%  { opacity: 1; }
        100% { transform: translateY(115vh) rotate(var(--confetti-spin, 540deg)); opacity: 0; }
    }
    @keyframes confetti-sway {
        0%   { transform: translateX(-12px); }
        100% { transform: translateX(12px); }
    }
</style>
<script>
    // Reuses the exact same overlay every Add to Cart / Buy Now / Place Order click already
    // showed on the way here — so the whole journey (cart -> checkout -> this page) reads as
    // one continuous, branded "your order is on its way" moment instead of three unrelated
    // loading states. The package icon morphs into a confirmation check, then clears to
    // reveal the real card underneath (which was fully rendered the entire time).
    // A confetti burst timed to the exact moment the card is revealed, so the page feels
    // like it's celebrating with the customer rather than just finishing a load. Built with
    // plain DOM+CSS (no library) so it costs nothing extra to ship; skipped entirely for
    // anyone who has asked their OS/browser for reduced motion.
    function launchConfetti() {
        if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        const container = document.getElementById('confetti-container');
        if (!container) return;

        const colors = [@json(setting('primary_color', '#ea580c')), @json(setting('secondary_color', '#ec4899')), @json(hex_shade(setting('primary_color', '#ea580c'), 0.35)), '#22c55e', '#eab308', '#06b6d4']; // brand first
        const total = window.innerWidth < 640 ? 60 : 110;

        for (let i = 0; i < total; i++) {
            const piece = document.createElement('div');
            piece.className = 'confetti-piece';
            const fallDuration = 2.6 + Math.random() * 2;
            const spin = (Math.random() < 0.5 ? -1 : 1) * (360 + Math.random() * 360);
            piece.style.left = (Math.random() * 100) + '%';
            piece.style.animationDuration = fallDuration + 's';
            piece.style.animationDelay = (Math.random() * 0.6) + 's';
            piece.style.setProperty('--confetti-spin', spin + 'deg');

            const inner = document.createElement('span');
            inner.className = 'confetti-piece-inner';
            const size = 6 + Math.random() * 7;
            const isCircle = Math.random() < 0.4;
            inner.style.width = size + 'px';
            inner.style.height = (isCircle ? size : size * 2.2) + 'px';
            inner.style.background = colors[Math.floor(Math.random() * colors.length)];
            inner.style.borderRadius = isCircle ? '50%' : '2px';
            inner.style.animationDuration = (0.5 + Math.random() * 0.5) + 's';

            piece.appendChild(inner);
            piece.addEventListener('animationend', function () { piece.remove(); });
            container.appendChild(piece);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        showDeliveryLoader('Confirming your order…');
        const overlay = document.getElementById('delivery-loader-overlay');
        const card = document.getElementById('thankyou-card');
        if (!overlay) { if (card) card.classList.add('dl-revealed'); launchConfetti(); return; }

        const packageIcon = overlay.querySelector('[data-loader-icon="package"]');
        const successIcon = overlay.querySelector('[data-loader-icon="success"]');
        const bar = overlay.querySelector('[data-loader-bar]');
        const message = overlay.querySelector('[data-loader-message]');

        setTimeout(function () {
            if (packageIcon) packageIcon.hidden = true;
            if (successIcon) successIcon.hidden = false;
            if (bar) bar.style.width = '100%';
            if (message) message.textContent = 'Order confirmed!';
        }, 650);

        setTimeout(function () {
            hideDeliveryLoader();
            if (card) card.classList.add('dl-revealed');
            launchConfetti();
            // Reset for the next time this same overlay is used elsewhere on the site.
            if (packageIcon) packageIcon.hidden = false;
            if (successIcon) successIcon.hidden = true;
            if (bar) bar.style.width = '';
        }, 1150);
    });
</script>

@if($shouldTrackPurchase ?? false)
@php
    $currencyCode = setting('currency_code', 'BDT');
    // Product ID (not SKU) — the same item_id/content_id as view_item, add_to_cart, the
    // Merchant feed and Meta's catalog; mixing in SKUs here split each product in two.
    $trackedItems = $order->items->map(fn ($item) => [
        'id'       => (string) $item->product_id,
        'name'     => $item->product_name,
        'price'    => (float) $item->price,
        'quantity' => $item->quantity,
    ])->values();
    $order->items->pluck('product')->filter()->each->loadMissing('brand', 'category');
    $gaItems = $order->items->map(fn ($item) => tracking_item($item->product, [
        'item_id' => (string) $item->product_id, // still set when the product was since deleted
        'item_name' => $item->product_name,
        'price' => (float) $item->price,
        'quantity' => (int) $item->quantity,
        'item_variant' => $item->variant_label,
    ]))->values();

    // Re-derived here rather than reused from layouts/app.blade.php — @extends
    // evaluates this @section before the parent layout's own @php block runs,
    // so those variables aren't in scope here. setting() is request-cached
    // (see Setting::allFresh()), so this costs nothing extra.
    $pixelId = setting('facebook_pixel_id', '');
    $pixelOn = $pixelId && setting('facebook_pixel_enabled', $pixelId ? '1' : '0') == '1';
    $fbAdvancedMatchingOn = setting('facebook_advanced_matching_enabled', '0') == '1';
    $googleEnhancedOn = setting('google_enhanced_conversions_enabled', '0') == '1';
    $adsConversionId = setting('google_ads_conversion_id', '');
    $adsPurchaseLabel = setting('google_ads_purchase_label', '');

    // The order's own shipping details — richer than the general logged-in-user
    // profile the layout defaults to, and the only source at all for a guest
    // checkout (whose email/phone only became known by placing this order).
    $orderTrackingData = pixel_advanced_matching_data(
        $order->shipping_name, $order->user?->email, $order->shipping_phone,
        $order->shipping_city, $order->shipping_state, $order->shipping_zip, $order->shipping_country
    );
@endphp
<script>
// Fires once per order (guarded server-side) so GA/GTM/Meta each get exactly one
// Purchase conversion — duplicate fires would inflate revenue and skew ad optimization.
(function () {
    var items = @json($trackedItems);
    var orderId = @json($order->order_number);
    var value = {{ (float) $order->total }};
    var currency = @json($currencyCode);

    @if($googleEnhancedOn && $orderTrackingData['google'])
    if (typeof gtag === 'function') {
        gtag('set', 'user_data', {!! Js::from($orderTrackingData['google']) !!});
    }
    @endif

    // GA4 + GTM via the layout's shared tracker. value = revenue incl. shipping, as GA4
    // expects; shipping/tax/coupon broken out so reports can separate them.
    window.mvTrack('purchase', {
        transaction_id: orderId,
        value: value,
        shipping: {{ (float) $order->shipping }},
        tax: {{ (float) $order->tax }},
        @if($order->coupon_code)coupon: @json($order->coupon_code),@endif
        @if((float) $order->discount > 0)discount: {{ (float) $order->discount }},@endif
        payment_type: @json($order->payment_method),
        items: @json($gaItems),
    });
    if (typeof gtag === 'function') {
        @if($adsConversionId && $adsPurchaseLabel)
        gtag('event', 'conversion', {
            send_to: {!! Js::from($adsConversionId . '/' . $adsPurchaseLabel) !!},
            value: value,
            currency: currency,
            transaction_id: orderId,
        });
        @endif
    }


    @if($pixelOn)
    if (typeof fbq === 'function') {
        @if($fbAdvancedMatchingOn && $orderTrackingData['fb'])
        // Refresh Advanced Matching with this order's shipping details right
        // before the highest-value event fires — see the @php block above.
        fbq('init', {!! Js::from($pixelId) !!}, {!! Js::from($orderTrackingData['fb']) !!});
        @endif
        // eventID matches the server-side Conversions API call CheckoutController@success
        // already fired for this order (guarded there the same way, by $shouldTrackPurchase)
        // so Meta merges the pair into one event instead of double-counting this sale.
        fbq('track', 'Purchase', {
            value: value,
            currency: currency,
            content_type: 'product',
            content_ids: items.map(function (i) { return i.id; })
        }, { eventID: {!! Js::from($fbPurchaseEventId) !!} });
    }
    @endif
})();
</script>
@endif
@endsection
