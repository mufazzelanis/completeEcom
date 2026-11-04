{{-- Shared look for every flash sale surface. Colours come from the sale's theme as
     --fs-* custom properties (FlashSale::style_vars), so switching theme in the admin
     needs no asset rebuild. --}}
@once
<style>
    .fs-grad { background: linear-gradient(120deg, var(--fs-from), var(--fs-via) 55%, var(--fs-to)); }
    .fs-accent { color: var(--fs-accent); }
    .fs-band { position: relative; overflow: hidden; isolation: isolate; }
    .fs-band::before { content: ""; position: absolute; inset: 0; z-index: -1; opacity: .18;
        background-image: radial-gradient(rgba(255,255,255,.9) 1px, transparent 1.2px); background-size: 14px 14px;
        mask-image: linear-gradient(90deg, transparent, #000 60%); -webkit-mask-image: linear-gradient(90deg, transparent, #000 60%); }
    .fs-band::after { content: ""; position: absolute; z-index: -1; width: 18rem; height: 18rem; right: -5rem; top: -9rem; border-radius: 999px;
        background: radial-gradient(circle, rgba(255,255,255,.35), transparent 65%); }
    .fs-shine { overflow: hidden; } /* needs a positioned element (absolute/relative) */
    .fs-shine::after { content: ""; position: absolute; inset: 0; transform: translateX(-120%);
        background: linear-gradient(100deg, transparent 30%, rgba(255,255,255,.45) 50%, transparent 70%); animation: fsShine 3.2s ease-in-out infinite; }
    @keyframes fsShine { 0%, 55% { transform: translateX(-120%); } 100% { transform: translateX(120%); } }

    .fs-cd { display: inline-flex; align-items: flex-start; gap: .3rem; font-variant-numeric: tabular-nums; }
    .fs-cd-unit { display: flex; flex-direction: column; align-items: center; }
    .fs-cd-num { min-width: 2rem; padding: .3rem .35rem; border-radius: .55rem; text-align: center; font-weight: 800; line-height: 1;
        background: rgba(255,255,255,.95); color: #111827; box-shadow: 0 2px 0 rgba(0,0,0,.12); }
    .fs-cd-lbl { margin-top: .2rem; font-size: .55rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; opacity: .85; }
    .fs-cd-sep { font-weight: 800; padding-top: .25rem; opacity: .8; }
    .fs-cd-sm .fs-cd-num { font-size: .8rem; }
    .fs-cd-lg { gap: .5rem; }
    .fs-cd-lg .fs-cd-num { min-width: 3.4rem; padding: .6rem .5rem; font-size: 1.7rem; border-radius: .9rem; }
    .fs-cd-lg .fs-cd-lbl { font-size: .65rem; margin-top: .35rem; }
    .fs-cd-lg .fs-cd-sep { font-size: 1.5rem; padding-top: .5rem; }

    .fs-card { transition: box-shadow .2s, transform .2s; }
    .fs-card:hover { transform: translateY(-2px); box-shadow: 0 0 0 1.5px var(--fs-accent), 0 14px 28px -14px rgba(0,0,0,.35); }
    .fs-bar { height: .4rem; border-radius: 999px; background: rgba(148,163,184,.25); overflow: hidden; }
    .fs-bar > i { display: block; height: 100%; border-radius: inherit; background: linear-gradient(90deg, var(--fs-from), var(--fs-to)); }
    .fs-bar.is-hot > i { background-size: 200% 100%; animation: fsFlow 1.4s linear infinite;
        background-image: linear-gradient(90deg, var(--fs-from), var(--fs-to), var(--fs-from)); }
    @keyframes fsFlow { to { background-position: -200% 0; } }

    .fs-track { scroll-snap-type: x mandatory; scroll-padding-inline: .75rem; scrollbar-width: none; }
    @media (min-width: 768px) { .fs-track { scroll-padding-inline: 1rem; } }
    .fs-track::-webkit-scrollbar { display: none; }
    .fs-track > * { scroll-snap-align: start; }

    @media (prefers-reduced-motion: reduce) {
        .fs-shine::after, .fs-bar.is-hot > i { animation: none; }
        .fs-card:hover { transform: none; }
    }
</style>
@endonce
