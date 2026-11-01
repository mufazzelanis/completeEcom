{{-- One promo popup card, drawn from an Alpine object `p` (PromoPopup::toClient shape) — used by the
     storefront popup (partials/storefront/promo-popup) and the admin live preview. The layout follows
     the content: picture only, text only, or picture + text. The host component provides `cd`
     (countdown text), `copied`, and go() / close() / copy(). --}}
<style>
    .pp-card { --pp-accent: #f97316; position: relative; width: 100%; max-width: 440px; background: #fff; color: #111827; border-radius: 1.25rem; overflow: hidden; box-shadow: 0 25px 60px -12px rgba(0,0,0,.45); font-family: inherit; }
    .pp-x { position: absolute; top: .6rem; right: .6rem; z-index: 3; width: 2.1rem; height: 2.1rem; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; background: rgba(17,24,39,.55); color: #fff; backdrop-filter: blur(6px); transition: transform .15s, background-color .15s; }
    .pp-x:hover { background: rgba(17,24,39,.8); transform: scale(1.06); }
    .pp-text .pp-x { background: rgba(255,255,255,.22); }
    .pp-handle { display: none; }
    .pp-media { position: relative; background: #f3f4f6; line-height: 0; }
    .pp-media.is-link { cursor: pointer; }
    .pp-img { display: block; width: 100%; height: auto; max-height: 62vh; object-fit: contain; margin: 0 auto; }
    .pp-content { padding: 1.15rem 1.25rem 1.3rem; text-align: center; }
    .pp-title { font-size: 1.3rem; font-weight: 800; line-height: 1.25; letter-spacing: -.01em; }
    .pp-body { margin-top: .45rem; font-size: .92rem; line-height: 1.5; color: #4b5563; white-space: pre-line; }
    .pp-cd { display: inline-flex; align-items: center; gap: .35rem; margin-top: .75rem; padding: .3rem .7rem; border-radius: 999px; font-size: .8rem; font-weight: 800; font-variant-numeric: tabular-nums; background: color-mix(in srgb, var(--pp-accent) 12%, #fff); color: var(--pp-accent); }
    .pp-cd-badge { position: absolute; left: .6rem; bottom: .6rem; line-height: 1.2; margin: 0; background: rgba(17,24,39,.75); color: #fff; }
    .pp-coupon { display: flex; align-items: center; justify-content: space-between; gap: .6rem; width: 100%; margin-top: .85rem; padding: .6rem .8rem; border-radius: .9rem; border: 2px dashed color-mix(in srgb, var(--pp-accent) 55%, transparent); background: color-mix(in srgb, var(--pp-accent) 7%, #fff); text-align: left; transition: transform .15s; }
    .pp-coupon:active { transform: scale(.98); }
    .pp-coupon span { font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #6b7280; }
    .pp-coupon b { display: block; font-size: 1.1rem; font-weight: 900; letter-spacing: .08em; color: #111827; }
    .pp-coupon em { font-style: normal; font-size: .72rem; font-weight: 800; padding: .3rem .6rem; border-radius: .6rem; background: var(--pp-accent); color: #fff; white-space: nowrap; }
    .pp-cta { display: block; width: 100%; margin-top: 1rem; padding: .85rem 1rem; border-radius: .9rem; font-size: 1rem; font-weight: 800; color: #fff; background: var(--pp-accent); box-shadow: 0 10px 22px -10px var(--pp-accent); transition: transform .15s, filter .15s; }
    .pp-cta:hover { filter: brightness(1.06); transform: translateY(-1px); }
    .pp-cta:active { transform: scale(.98); }
    /* text only: the whole card in the accent colour */
    .pp-text { background: linear-gradient(145deg, var(--pp-accent), color-mix(in srgb, var(--pp-accent) 70%, #000)); color: #fff; }
    .pp-text .pp-content { padding: 2rem 1.5rem 1.5rem; }
    .pp-text .pp-title { font-size: 1.55rem; }
    .pp-text .pp-body { color: rgba(255,255,255,.88); }
    .pp-text .pp-cd { background: rgba(255,255,255,.18); color: #fff; }
    .pp-text .pp-coupon { background: rgba(255,255,255,.12); border-color: rgba(255,255,255,.55); }
    .pp-text .pp-coupon span { color: rgba(255,255,255,.75); }
    .pp-text .pp-coupon b { color: #fff; }
    .pp-text .pp-coupon em { background: #fff; color: var(--pp-accent); }
    .pp-text .pp-cta { background: #fff; color: var(--pp-accent); box-shadow: 0 10px 24px -10px rgba(0,0,0,.45); }
    .pp-spark { position: absolute; inset: 0; pointer-events: none; background: radial-gradient(circle at 85% 10%, rgba(255,255,255,.25), transparent 40%), radial-gradient(circle at 0% 100%, rgba(255,255,255,.12), transparent 45%); }
    .dark .pp-card:not(.pp-text) { background: #111827; color: #f9fafb; }
    .dark .pp-card:not(.pp-text) .pp-body { color: #d1d5db; }
    .dark .pp-card:not(.pp-text) .pp-coupon { background: #1f2937; }
    .dark .pp-card:not(.pp-text) .pp-coupon b { color: #f9fafb; }
    .dark .pp-media { background: #0b1220; }
    @media (max-width: 767px) {
        .pp-mobile .pp-card, .pp-sheet .pp-card { max-width: none; border-radius: 1.4rem 1.4rem 0 0; padding-bottom: env(safe-area-inset-bottom); }
        .pp-mobile .pp-handle, .pp-sheet .pp-handle { display: block; position: absolute; top: .45rem; left: 50%; z-index: 3; width: 2.6rem; height: .3rem; margin-left: -1.3rem; border-radius: 999px; background: rgba(255,255,255,.75); box-shadow: 0 0 0 1px rgba(0,0,0,.08); }
        .pp-sheet .pp-img { max-height: 48vh; }
    }
</style>
<div class="pp-card" :class="'pp-' + p.layout" :style="'--pp-accent:' + (p.accent || '#f97316')">
    <span class="pp-handle" aria-hidden="true"></span>
    <button type="button" class="pp-x" @click="close()" aria-label="Close">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
    </button>
    <template x-if="p.layout === 'text'"><span class="pp-spark"></span></template>
    <template x-if="p.image">
        <div class="pp-media" :class="p.url && 'is-link'" @click="p.url && go()">
            <img class="pp-img" :src="p.image" :alt="p.title || 'Special offer'" decoding="async">
            <template x-if="p.layout === 'image' && cd"><span class="pp-cd pp-cd-badge">⏳ <span x-text="cd"></span></span></template>
        </div>
    </template>
    <template x-if="p.layout !== 'image'">
        <div class="pp-content" style="position: relative;">
            <p class="pp-title" x-show="p.title" x-text="p.title" :id="'pp-t-' + p.id"></p>
            <p class="pp-body" x-show="p.body" x-text="p.body"></p>
            <div x-show="cd"><span class="pp-cd">⏳ Ends in <span x-text="cd"></span></span></div>
            <button type="button" class="pp-coupon" x-show="p.coupon" @click="copy()">
                <div><span>Use code</span><b x-text="p.coupon"></b></div>
                <em x-text="copied ? 'Copied ✓' : 'Copy'"></em>
            </button>
            <button type="button" class="pp-cta" x-show="p.url" @click="go()" x-text="p.button || 'Shop now'"></button>
        </div>
    </template>
</div>
