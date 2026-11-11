{{-- Landing page themes (App\Support\LandingThemes). Inline and self-contained: no extra request
     before first paint, and nothing depends on the Tailwind build. Colours come from the
     variables on <body>; every tint is mixed from them, so one brand colour re-colours a theme. --}}
<style>
.lt{margin:0;background:var(--bg);color:var(--ink);font-family:"Hind Siliguri","Noto Sans Bengali","Segoe UI",system-ui,-apple-system,Roboto,Arial,sans-serif;line-height:1.65;-webkit-font-smoothing:antialiased;overflow-x:hidden;text-rendering:optimizeLegibility}
.lt *,.lt *::before,.lt *::after{box-sizing:border-box}
.lt img{max-width:100%;height:auto;display:block}
:where(.lt) a{color:inherit;text-decoration:none}
:where(.lt) button{font:inherit;color:inherit}
.lt h1,.lt h2,.lt h3,.lt p{margin:0}
.lt [x-cloak]{display:none!important}
html:has(body.lt){scroll-behavior:smooth;scroll-padding-top:76px}
.lt-wrap{width:100%;max-width:1140px;margin:0 auto;padding:0 18px}
.lt-narrow{max-width:800px}
.lt-sec{padding:44px 0}
@media(min-width:900px){.lt-sec{padding:72px 0}}
.lt-sec--alt{background:var(--surface);border-block:1px solid var(--line)}
.lt-sec-head{text-align:center;max-width:640px;margin:0 auto 26px}
@media(min-width:900px){.lt-sec-head{margin-bottom:40px}}
.lt-kicker{display:inline-flex;align-items:center;gap:6px;font-size:.78rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--brand);background:color-mix(in srgb,var(--brand) 10%,transparent);padding:5px 12px;border-radius:999px;margin-bottom:10px}
.lt-h2{font-size:clamp(1.35rem,1.05rem + 1.3vw,2.1rem);line-height:1.3;font-weight:800;letter-spacing:-.01em}
.lt-sub{color:var(--muted);margin-top:8px;font-size:1rem}
.lt-muted{color:var(--muted)}

/* buttons */
.lt-btn{position:relative;overflow:hidden;isolation:isolate;display:inline-flex;align-items:center;justify-content:center;gap:10px;border:0;cursor:pointer;touch-action:manipulation;-webkit-tap-highlight-color:transparent;
  background:linear-gradient(135deg,var(--brand),var(--brand-2));color:var(--on-brand);font-weight:800;font-size:1.08rem;line-height:1.2;padding:16px 28px;border-radius:calc(var(--radius) * .75);
  box-shadow:0 12px 28px -12px color-mix(in srgb,var(--brand) 75%,transparent);transition:transform .15s ease,box-shadow .2s ease,filter .2s}
.lt-btn:hover{transform:translateY(-2px);filter:saturate(1.08)}
.lt-btn:active{transform:scale(.97)}
.lt-btn:focus-visible{outline:3px solid color-mix(in srgb,var(--brand) 45%,transparent);outline-offset:3px}
.lt-btn::after{content:'';position:absolute;inset:0 auto 0 -60%;width:45%;background:linear-gradient(110deg,transparent,rgba(255,255,255,.38),transparent);transform:skewX(-20deg);animation:lt-shine 3.6s ease-in-out infinite;z-index:-1}
.lt-btn--block{width:100%}
.lt-btn--sm{font-size:.9rem;padding:10px 16px;box-shadow:none}
.lt-btn--sm::after{display:none}
.lt-btn--ghost{background:transparent;color:var(--ink);border:1.5px solid var(--line);box-shadow:none}
.lt-btn--ghost::after{display:none}
.lt-btn svg{width:20px;height:20px;flex:none}
@keyframes lt-shine{0%,30%{left:-60%}75%,100%{left:130%}}

/* urgency bar + header */
.lt-urgent{background:var(--ink);color:var(--bg);font-size:.88rem;font-weight:600;text-align:center;padding:8px 14px;display:flex;align-items:center;justify-content:center;gap:10px;flex-wrap:wrap}
.lt-urgent b{font-variant-numeric:tabular-nums;background:var(--brand);color:var(--on-brand);padding:2px 8px;border-radius:6px;font-family:ui-monospace,Menlo,Consolas,monospace}
.lt-head{position:sticky;top:0;z-index:40;background:color-mix(in srgb,var(--surface) 90%,transparent);-webkit-backdrop-filter:blur(12px);backdrop-filter:blur(12px);border-bottom:1px solid var(--line)}
.lt-head .lt-wrap{display:flex;align-items:center;justify-content:space-between;gap:12px;height:64px}
.lt-logo img{height:42px;width:auto;max-width:170px;object-fit:contain}
.lt-logo span{font-weight:800;font-size:1.15rem}
.lt-head-actions{display:flex;align-items:center;gap:8px}
.lt-call{display:inline-flex;align-items:center;gap:6px;font-weight:700;font-size:.9rem;padding:9px 12px;border-radius:999px;border:1.5px solid var(--line);color:var(--ink)}
.lt-call svg{width:18px;height:18px;color:var(--brand)}
.lt-call .lt-call-no{display:none}
@media(min-width:700px){.lt-call .lt-call-no{display:inline}}

/* hero */
.lt-hero{position:relative;overflow:hidden;background:var(--hero);padding:28px 0 40px}
@media(min-width:900px){.lt-hero{padding:72px 0 88px}}
.lt-hero-grid{position:relative;display:grid;gap:24px;align-items:center}
@media(min-width:900px){.lt-hero-grid{grid-template-columns:1.05fr .95fr;gap:60px}}
.lt-hero-media{position:relative;order:-1}
@media(min-width:900px){.lt-hero-media{order:2}}
.lt-hero-frame{position:relative;aspect-ratio:1/1;max-width:520px;margin:0 auto;display:flex;align-items:center;justify-content:center;padding:7%;
  border-radius:calc(var(--radius) * 1.6);background:radial-gradient(circle at 50% 45%,color-mix(in srgb,var(--brand) 16%,var(--surface)) 0%,var(--surface) 68%);
  box-shadow:0 30px 60px -30px color-mix(in srgb,var(--brand) 45%,transparent);border:1px solid color-mix(in srgb,var(--brand) 14%,var(--line))}
.lt-hero-frame img{width:100%;height:100%;object-fit:contain;filter:drop-shadow(0 22px 26px rgba(0,0,0,.18));animation:lt-float 5s ease-in-out infinite}
@media(max-width:899px){.lt-hero-frame{max-width:260px}}
@keyframes lt-float{0%,100%{transform:translateY(0)}50%{transform:translateY(-10px)}}
.lt-burst{position:absolute;top:4%;left:2%;z-index:2;width:78px;height:78px;border-radius:50%;display:flex;flex-direction:column;align-items:center;justify-content:center;line-height:1;
  background:var(--accent);color:#1f1300;font-weight:900;font-size:1.3rem;box-shadow:0 10px 24px -8px rgba(0,0,0,.35);transform:rotate(-10deg);animation:lt-pop 2.2s ease-in-out infinite}
.lt-burst small{font-size:.62rem;font-weight:800;letter-spacing:.04em}
@keyframes lt-pop{0%,100%{transform:rotate(-10deg) scale(1)}50%{transform:rotate(-10deg) scale(1.07)}}
.lt-badge{display:inline-flex;align-items:center;gap:7px;font-size:.85rem;font-weight:700;padding:6px 14px;border-radius:999px;background:var(--surface);color:var(--ink);border:1px solid var(--line);box-shadow:0 4px 14px -8px rgba(0,0,0,.2)}
.lt-badge i{width:8px;height:8px;border-radius:50%;background:var(--accent);box-shadow:0 0 0 4px color-mix(in srgb,var(--accent) 25%,transparent);font-style:normal}
.lt-h1{font-size:clamp(1.65rem,1.1rem + 2.6vw,3.15rem);line-height:1.22;font-weight:800;letter-spacing:-.015em;margin-top:14px}
.lt-lead{font-size:1.06rem;color:var(--muted);margin-top:12px;max-width:560px}
.lt-rating{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-top:14px;font-size:.95rem}
.lt-stars{color:#f59e0b;letter-spacing:1px;font-size:1.05rem}
.lt-price-row{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin:20px 0 22px}
.lt-price{font-size:clamp(2rem,1.6rem + 1.8vw,2.9rem);font-weight:900;line-height:1;color:var(--brand);font-variant-numeric:tabular-nums}
.lt-was{font-size:1.15rem;color:var(--muted);text-decoration:line-through;text-decoration-thickness:2px}
.lt-save{font-size:.85rem;font-weight:800;padding:5px 11px;border-radius:999px;background:color-mix(in srgb,var(--accent) 22%,transparent);color:color-mix(in srgb,var(--accent) 55%,var(--ink))}
.lt-cta-row{display:flex;gap:12px;flex-wrap:wrap}
@media(max-width:899px){.lt-cta-row .lt-btn{width:100%}}
.lt-chips{display:flex;flex-wrap:wrap;gap:8px 16px;margin-top:18px;font-size:.88rem;color:var(--muted)}
.lt-chips span{display:inline-flex;align-items:center;gap:6px}
.lt-chips img{width:18px;height:18px;object-fit:contain}
.lt-clock{display:flex;gap:8px;margin:4px 0 0}
.lt-clock div{min-width:62px;text-align:center;padding:8px 6px;border-radius:12px;background:rgba(0,0,0,.28);border:1px solid rgba(255,255,255,.18)}
.lt-clock b{display:block;font-size:1.6rem;line-height:1;font-variant-numeric:tabular-nums}
.lt-clock small{font-size:.7rem;opacity:.8}

/* trust strip */
.lt-trust{background:var(--surface);border-block:1px solid var(--line);padding:16px 0}
.lt-trust-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
@media(min-width:700px){.lt-trust-grid{grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:16px}}
.lt-trust-item{display:flex;align-items:center;gap:10px;font-size:.9rem;font-weight:600;line-height:1.3;padding:8px}
.lt-ico{flex:none;width:42px;height:42px;border-radius:calc(var(--radius) * .6);display:flex;align-items:center;justify-content:center;font-size:1.3rem;background:color-mix(in srgb,var(--brand) 11%,var(--surface));color:var(--brand)}
.lt-ico img{width:26px;height:26px;object-fit:contain}

/* benefit cards */
.lt-cards{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
@media(min-width:900px){.lt-cards{grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:20px}}
.lt-card{position:relative;background:var(--surface);border:1px solid var(--line);border-radius:var(--radius);padding:18px 16px;transition:transform .25s ease,box-shadow .25s ease,border-color .25s}
.lt-card:hover{transform:translateY(-4px);box-shadow:0 22px 40px -24px color-mix(in srgb,var(--brand) 55%,transparent);border-color:color-mix(in srgb,var(--brand) 35%,var(--line))}
.lt-card .lt-ico{width:50px;height:50px;margin-bottom:12px;font-size:1.5rem}
.lt-card h3{font-size:1.02rem;font-weight:800;line-height:1.35}
.lt-card p{font-size:.9rem;color:var(--muted);margin-top:5px;line-height:1.55}
.lt-num{display:none}

/* who-for list */
.lt-list{display:grid;gap:10px}
@media(min-width:800px){.lt-list{grid-template-columns:1fr 1fr;gap:14px}}
.lt-li{display:flex;align-items:center;gap:12px;background:var(--surface);border:1px solid var(--line);border-radius:calc(var(--radius) * .8);padding:13px 14px;font-size:1.02rem;line-height:1.45}
.lt-li .lt-ico{width:40px;height:40px;font-size:1.2rem;border-radius:50%}
.lt-li strong{color:var(--brand)}

/* article */
.lt-article{background:var(--surface);border:1px solid var(--line);border-radius:calc(var(--radius) * 1.2);padding:24px 20px;box-shadow:0 24px 50px -36px rgba(0,0,0,.35)}
@media(min-width:900px){.lt-article{padding:44px 52px}}
.lt-article .lp-content{font-size:1.04rem}

/* video facade: a picture and a play button until tapped, so no player loads with the page */
.lt-video{position:relative;aspect-ratio:16/9;border-radius:var(--radius);overflow:hidden;background:#0b0b0b;box-shadow:0 26px 50px -28px rgba(0,0,0,.55)}
.lt-video--tall{aspect-ratio:9/16}
.lt-video iframe,.lt-video video{position:absolute;inset:0;width:100%;height:100%;border:0}
.lt-play{position:absolute;inset:0;width:100%;border:0;padding:0;cursor:pointer;background:linear-gradient(135deg,color-mix(in srgb,var(--brand) 55%,#000),#000)}
.lt-play img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:.85;transition:opacity .2s,transform .4s}
.lt-play:hover img{opacity:1;transform:scale(1.03)}
.lt-play span{position:absolute;left:50%;top:50%;width:72px;height:72px;margin:-36px 0 0 -36px;border-radius:50%;background:var(--brand);color:var(--on-brand);display:flex;align-items:center;justify-content:center;box-shadow:0 0 0 10px color-mix(in srgb,var(--brand) 30%,transparent);animation:lt-ring 2s ease-out infinite}
.lt-play span svg{width:28px;height:28px;margin-left:4px}
@keyframes lt-ring{0%{box-shadow:0 0 0 0 color-mix(in srgb,var(--brand) 55%,transparent)}100%{box-shadow:0 0 0 22px transparent}}
.lt-play em{position:absolute;left:12px;bottom:10px;right:12px;color:#fff;font-style:normal;font-weight:700;font-size:.9rem;text-align:left;text-shadow:0 1px 6px rgba(0,0,0,.6)}

/* sliders (testimonials, certificates) */
.lt-rail{display:flex;gap:14px;overflow-x:auto;scroll-snap-type:x mandatory;padding:4px 18px 10px;margin:0 -18px;scrollbar-width:none;-webkit-overflow-scrolling:touch}
.lt-rail::-webkit-scrollbar{display:none}
.lt-rail>*{scroll-snap-align:center;flex:none}
.lt-rail--video>*{width:62%;max-width:270px}
.lt-rail--img>*{width:72%;max-width:300px}
@media(min-width:900px){.lt-rail{justify-content:safe center}}
.lt-shot{display:block;width:100%;padding:0;border:0;cursor:zoom-in;background:var(--surface);border-radius:var(--radius);overflow:hidden;border:1px solid var(--line);box-shadow:0 18px 36px -24px rgba(0,0,0,.45)}
.lt-shot img{width:100%;aspect-ratio:4/5;object-fit:cover}
.lt-certs .lt-shot img{aspect-ratio:3/4;object-fit:contain;background:#fff}
.lt-cap{text-align:center;font-size:.85rem;color:var(--muted);margin-top:8px;font-weight:600}
.lt-certs{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
@media(min-width:900px){.lt-certs{grid-template-columns:repeat(4,minmax(0,1fr));gap:18px}}
.lt-zoom{position:fixed;inset:0;z-index:80;background:rgba(0,0,0,.86);display:flex;align-items:center;justify-content:center;padding:20px;cursor:zoom-out}
.lt-zoom img{max-height:90vh;width:auto;border-radius:12px}

/* offer card */
.lt-offer{position:relative;max-width:560px;margin:0 auto;background:var(--surface);border-radius:calc(var(--radius) * 1.3);padding:30px 22px 24px;text-align:center;
  border:2px solid color-mix(in srgb,var(--brand) 40%,var(--line));box-shadow:0 30px 60px -34px color-mix(in srgb,var(--brand) 70%,transparent)}
.lt-offer-tag{position:absolute;top:-15px;left:50%;transform:translateX(-50%);white-space:nowrap;background:linear-gradient(135deg,var(--brand),var(--brand-2));color:var(--on-brand);font-weight:800;font-size:.85rem;padding:6px 16px;border-radius:999px;box-shadow:0 8px 18px -8px color-mix(in srgb,var(--brand) 80%,transparent)}
.lt-offer-img{width:170px;height:170px;margin:0 auto 14px;object-fit:contain}
.lt-lines{text-align:left;margin:4px 0 16px;border-top:1px dashed var(--line);border-bottom:1px dashed var(--line);padding:10px 0}
.lt-lines div{display:flex;justify-content:space-between;gap:12px;padding:5px 0;font-size:.98rem}
.lt-lines b{white-space:nowrap}
.lt-offer .lt-price-row{justify-content:center;margin:10px 0 18px}

/* FAQ — plain <details>: opens instantly, and every answer is in the page for search engines */
.lt-faq{max-width:780px;margin:0 auto;display:grid;gap:10px}
.lt-faq details{background:var(--surface);border:1px solid var(--line);border-radius:calc(var(--radius) * .8);transition:border-color .2s,box-shadow .2s}
.lt-faq details[open]{border-color:color-mix(in srgb,var(--brand) 45%,var(--line));box-shadow:0 14px 30px -22px color-mix(in srgb,var(--brand) 60%,transparent)}
.lt-faq summary{list-style:none;cursor:pointer;display:flex;align-items:center;justify-content:space-between;gap:14px;padding:16px 18px;font-weight:700;font-size:1.02rem;line-height:1.45}
.lt-faq summary::-webkit-details-marker{display:none}
.lt-faq summary i{flex:none;width:28px;height:28px;border-radius:50%;position:relative;background:color-mix(in srgb,var(--brand) 12%,transparent);transition:transform .25s ease}
.lt-faq summary i::before,.lt-faq summary i::after{content:'';position:absolute;left:50%;top:50%;width:12px;height:2px;margin:-1px 0 0 -6px;background:var(--brand);border-radius:2px}
.lt-faq summary i::after{transform:rotate(90deg);transition:transform .25s ease}
.lt-faq details[open] summary i::after{transform:rotate(0)}
.lt-faq details p{padding:0 18px 18px;color:var(--muted);line-height:1.7;white-space:pre-line}

/* order */
.lt-order{background:linear-gradient(180deg,var(--bg),color-mix(in srgb,var(--brand) 7%,var(--bg)))}
.lt-order-grid{display:grid;gap:18px;align-items:start}
@media(min-width:900px){.lt-order-grid{grid-template-columns:.85fr 1.15fr;gap:36px}}
.lt-sum{background:var(--surface);border:1px solid var(--line);border-radius:var(--radius);padding:16px;display:flex;gap:14px;align-items:center}
@media(min-width:900px){.lt-sum{position:sticky;top:86px;flex-direction:column;align-items:stretch;padding:24px;text-align:center}}
.lt-sum-img{flex:none;width:84px;height:84px;object-fit:contain;border-radius:calc(var(--radius) * .6);background:color-mix(in srgb,var(--brand) 7%,var(--surface));padding:6px}
@media(min-width:900px){.lt-sum-img{width:100%;height:240px;padding:18px}}
.lt-sum-title{font-size:1.02rem;font-weight:800;line-height:1.35}
.lt-sum .lt-price{font-size:1.6rem;margin-top:4px}
.lt-sum-list{display:none}
@media(min-width:900px){.lt-sum-list{display:grid;gap:8px;text-align:left;margin-top:16px;padding-top:16px;border-top:1px dashed var(--line);font-size:.92rem}}
.lt-sum-list span{display:flex;align-items:center;gap:8px}
.lt-sum-list svg{width:18px;height:18px;color:var(--accent);flex:none}
.lt-form{background:var(--surface);border:1px solid var(--line);border-radius:calc(var(--radius) * 1.1);padding:22px 18px;box-shadow:0 30px 60px -40px rgba(0,0,0,.45)}
@media(min-width:900px){.lt-form{padding:32px}}
.lt-form-title{font-size:1.25rem;font-weight:800;text-align:center}
.lt-form-sub{text-align:center;color:var(--muted);font-size:.92rem;margin:4px 0 20px}
.lt-field{margin-bottom:16px}
.lt-label{display:block;font-weight:700;font-size:.98rem;margin-bottom:7px}
.lt-label em{color:#e11d48;font-style:normal}
.lt-input-wrap{position:relative}
.lt-input-wrap>svg{position:absolute;left:14px;top:50%;width:20px;height:20px;margin-top:-10px;color:var(--muted);pointer-events:none}
.lt-input{width:100%;font:inherit;font-size:16px;color:var(--ink);background:var(--bg);border:1.5px solid var(--line);border-radius:calc(var(--radius) * .6);padding:13px 14px;transition:border-color .15s,box-shadow .15s,background .15s;-webkit-appearance:none;appearance:none}
.lt-input-wrap .lt-input{padding-left:44px}
.lt-input:focus{outline:0;border-color:var(--brand);background:var(--surface);box-shadow:0 0 0 4px color-mix(in srgb,var(--brand) 16%,transparent)}
textarea.lt-input{resize:vertical;min-height:80px}
select.lt-input{background:var(--bg) url("data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 20 20%27 fill=%27%23888%27%3E%3Cpath d=%27M5.3 7.3a1 1 0 011.4 0L10 10.6l3.3-3.3a1 1 0 111.4 1.4l-4 4a1 1 0 01-1.4 0l-4-4a1 1 0 010-1.4z%27/%3E%3C/svg%3E") right 12px center/18px no-repeat;padding-right:40px}
.lt-packs{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px}
.lt-pack{position:relative;border:1.5px solid var(--line);background:var(--bg);border-radius:calc(var(--radius) * .6);padding:12px 6px 10px;text-align:center;cursor:pointer;transition:all .15s ease;touch-action:manipulation}
.lt-pack b{display:block;font-size:1.05rem}
.lt-pack span{display:block;font-size:.82rem;color:var(--muted);font-variant-numeric:tabular-nums}
.lt-pack.is-on{border-color:var(--brand);background:color-mix(in srgb,var(--brand) 9%,var(--surface));box-shadow:0 0 0 3px color-mix(in srgb,var(--brand) 16%,transparent)}
.lt-pack.is-on span{color:var(--brand);font-weight:700}
.lt-pack small{position:absolute;top:-9px;left:50%;transform:translateX(-50%);white-space:nowrap;font-size:.66rem;font-weight:800;padding:2px 8px;border-radius:999px;background:var(--accent);color:#1f1300}
.lt-step{display:flex;align-items:center;gap:10px;margin-top:10px;font-size:.88rem;color:var(--muted)}
.lt-step button{width:38px;height:38px;border-radius:10px;border:1.5px solid var(--line);background:var(--surface);font-size:1.2rem;font-weight:800;cursor:pointer;touch-action:manipulation}
.lt-step button:disabled{opacity:.35;cursor:default}
.lt-step b{min-width:26px;text-align:center;font-size:1.1rem;color:var(--ink)}
.lt-zone{display:flex;align-items:center;justify-content:space-between;gap:10px;border:1.5px solid var(--line);border-radius:calc(var(--radius) * .6);padding:12px 14px;margin-bottom:8px;cursor:pointer;background:var(--bg);transition:all .15s}
.lt-zone.is-on{border-color:var(--brand);background:color-mix(in srgb,var(--brand) 8%,var(--surface))}
.lt .lt-zone input{accent-color:var(--brand);color:var(--brand);width:18px;height:18px;flex:none}
.lt-zone span{display:flex;align-items:center;gap:10px}
.lt-hint{font-size:.84rem;color:var(--muted);margin:2px 2px 0}
.lt-hint--ok{color:var(--accent);font-weight:700}
.lt-total{border-radius:calc(var(--radius) * .7);padding:14px 16px;margin:6px 0 18px;background:color-mix(in srgb,var(--brand) 7%,var(--bg));border:1px dashed color-mix(in srgb,var(--brand) 35%,var(--line))}
.lt-total div{display:flex;justify-content:space-between;padding:3px 0;color:var(--muted);font-variant-numeric:tabular-nums}
.lt-total .is-grand{color:var(--ink);font-weight:900;font-size:1.15rem;border-top:1px dashed color-mix(in srgb,var(--brand) 35%,var(--line));margin-top:6px;padding-top:9px}
.lt-total .is-grand span:last-child{color:var(--brand)}
.lt-cod{display:flex;align-items:center;justify-content:center;gap:8px;text-align:center;font-size:.86rem;color:var(--muted);margin-top:12px}
.lt-errors{background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;border-radius:12px;padding:12px 14px;margin-bottom:16px;font-size:.92rem}
.lt-errors ul{margin:0;padding-left:18px}
.lt-spin{width:20px;height:20px;animation:lt-rot 1s linear infinite}
@keyframes lt-rot{to{transform:rotate(360deg)}}

/* sticky mobile order bar */
.lt-bar{position:fixed;left:0;right:0;bottom:0;z-index:50;background:color-mix(in srgb,var(--surface) 95%,transparent);-webkit-backdrop-filter:blur(12px);backdrop-filter:blur(12px);border-top:1px solid var(--line);
  padding:10px 14px max(10px,env(safe-area-inset-bottom));display:flex;align-items:center;gap:12px;box-shadow:0 -12px 30px -18px rgba(0,0,0,.35);transition:transform .3s cubic-bezier(.22,1,.36,1)}
.lt-bar.is-hidden{transform:translateY(110%)}
.lt-bar img{width:46px;height:46px;object-fit:contain;border-radius:10px;background:var(--bg);flex:none}
.lt-bar-txt{min-width:0;flex:1}
.lt-bar-txt p{font-size:.8rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.lt-bar-txt b{color:var(--brand);font-size:1.05rem}
.lt-bar .lt-btn{padding:12px 18px;font-size:.98rem;flex:none}
@media(min-width:900px){.lt-bar{display:none}}

/* footer */
.lt-foot{background:var(--surface);border-top:1px solid var(--line);padding:30px 0 96px;text-align:center;font-size:.86rem;color:var(--muted)}
@media(min-width:900px){.lt-foot{padding-bottom:30px}}
.lt-social{display:flex;justify-content:center;gap:14px;margin-bottom:12px}
.lt-social a{width:38px;height:38px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:1px solid var(--line);color:var(--muted);transition:color .2s,border-color .2s}
.lt-social a:hover{color:var(--brand);border-color:var(--brand)}
.lt-social svg{width:18px;height:18px}

/* scroll reveal (resources/js/scroll-reveal.js adds .is-visible) */
.lt .reveal{opacity:0;transform:translateY(22px);transition:opacity .7s cubic-bezier(.22,1,.36,1),transform .7s cubic-bezier(.22,1,.36,1)}
.lt .reveal.is-visible{opacity:1;transform:none}
.lt .reveal-group>*{opacity:0;transform:translateY(18px);transition:opacity .55s cubic-bezier(.22,1,.36,1),transform .55s cubic-bezier(.22,1,.36,1)}
.lt .reveal-group.is-visible>*{opacity:1;transform:none}
.lt .reveal-group.is-visible>*:nth-child(2){transition-delay:.06s}.lt .reveal-group.is-visible>*:nth-child(3){transition-delay:.12s}.lt .reveal-group.is-visible>*:nth-child(4){transition-delay:.18s}
.lt .reveal-group.is-visible>*:nth-child(5){transition-delay:.24s}.lt .reveal-group.is-visible>*:nth-child(n+6){transition-delay:.3s}
.lt-enter{animation:lt-in .8s cubic-bezier(.22,1,.36,1) both}
.lt-enter-2{animation:lt-in .8s .12s cubic-bezier(.22,1,.36,1) both}
@keyframes lt-in{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:none}}

/* ── Clinical: pharmacy-clean, a faint grid behind the hero ───────────────── */
.lt--clinical .lt-hero{background:linear-gradient(180deg,var(--hero),var(--bg))}
.lt--clinical .lt-hero::before{content:'';position:absolute;inset:0;background-image:linear-gradient(color-mix(in srgb,var(--brand) 7%,transparent) 1px,transparent 1px),linear-gradient(90deg,color-mix(in srgb,var(--brand) 7%,transparent) 1px,transparent 1px);background-size:34px 34px;-webkit-mask-image:radial-gradient(ellipse at 70% 30%,#000 10%,transparent 70%);mask-image:radial-gradient(ellipse at 70% 30%,#000 10%,transparent 70%)}
.lt--clinical .lt-card .lt-ico{border-radius:14px}

/* ── Mega Offer: deep red hero, yellow highlights ─────────────────────────── */
.lt--offer .lt-urgent{background:var(--accent);color:#1f1300}
.lt--offer .lt-urgent b{background:#1f1300;color:var(--accent)}
.lt--offer .lt-hero{background:radial-gradient(120% 90% at 85% 0%,var(--brand) 0%,var(--hero) 55%,#3b0909 100%);color:#fff}
.lt--offer .lt-hero::before{content:'';position:absolute;inset:0;background:repeating-linear-gradient(-45deg,rgba(255,255,255,.035) 0 14px,transparent 14px 28px)}
.lt--offer .lt-hero .lt-lead,.lt--offer .lt-hero .lt-chips,.lt--offer .lt-hero .lt-was{color:rgba(255,255,255,.82)}
.lt--offer .lt-hero .lt-price{color:var(--accent);text-shadow:0 4px 24px rgba(0,0,0,.3)}
.lt--offer .lt-hero .lt-badge{background:rgba(255,255,255,.12);color:#fff;border-color:rgba(255,255,255,.22)}
.lt--offer .lt-hero .lt-save{background:var(--accent);color:#1f1300}
.lt--offer .lt-hero .lt-btn{background:linear-gradient(135deg,var(--accent),#f59e0b);color:#1f1300;box-shadow:0 14px 34px -12px rgba(250,204,21,.7)}
.lt--offer .lt-hero-frame{background:radial-gradient(circle,rgba(255,255,255,.95) 0%,rgba(255,255,255,.75) 55%,rgba(255,255,255,.12) 72%);border:2px dashed rgba(255,255,255,.35);border-radius:50%}
.lt--offer .lt-h1{text-transform:none}
.lt--offer .lt-order{background:linear-gradient(180deg,#fff,#fff3e8)}
.lt--offer .lt-form{border:2px solid var(--brand)}

/* ── Story: editorial, text first ─────────────────────────────────────────── */
.lt--story .lt-hero{background:radial-gradient(90% 70% at 50% 0%,color-mix(in srgb,var(--brand) 9%,var(--hero)),var(--bg))}
.lt--story .lt-hero-grid{grid-template-columns:1fr;max-width:860px;margin:0 auto;text-align:center}
.lt--story .lt-hero-media{order:2}
.lt--story .lt-hero-frame{aspect-ratio:16/10;max-width:620px}
.lt--story .lt-lead,.lt--story .lt-sub{margin-left:auto;margin-right:auto}
.lt--story .lt-price-row,.lt--story .lt-cta-row,.lt--story .lt-chips,.lt--story .lt-rating{justify-content:center}
.lt--story .lt-h1{font-size:clamp(1.8rem,1.15rem + 3vw,3.5rem)}
.lt--story .lt-cards{grid-template-columns:1fr;max-width:800px;margin:0 auto;counter-reset:lt-n}
.lt--story .lt-card{display:grid;grid-template-columns:auto 1fr;gap:4px 16px;align-items:start;padding:20px}
.lt--story .lt-card .lt-ico{display:none}
.lt--story .lt-num{display:block;grid-row:span 2;font-size:2.2rem;font-weight:900;line-height:1;color:color-mix(in srgb,var(--brand) 35%,transparent);font-variant-numeric:tabular-nums}
.lt--story .lt-li{background:color-mix(in srgb,#e11d48 4%,var(--surface));border-color:color-mix(in srgb,#e11d48 14%,var(--line))}
.lt--story .lt-li .lt-ico{background:color-mix(in srgb,#e11d48 12%,var(--surface));color:#be123c}

/* ── Soft Care: pastel, rounded, gentle blobs ─────────────────────────────── */
.lt--care .lt-hero{background:linear-gradient(180deg,var(--hero),var(--bg))}
.lt--care .lt-hero::before,.lt--care .lt-hero::after{content:'';position:absolute;border-radius:50%;filter:blur(40px);opacity:.55;pointer-events:none}
.lt--care .lt-hero::before{width:320px;height:320px;left:-80px;top:-60px;background:color-mix(in srgb,var(--brand) 35%,#fff)}
.lt--care .lt-hero::after{width:300px;height:300px;right:-60px;bottom:-80px;background:color-mix(in srgb,var(--accent) 45%,#fff)}
.lt--care .lt-hero-frame{border-radius:46% 54% 50% 50% / 52% 46% 54% 48%;border:6px solid #fff}
.lt--care .lt-card{text-align:center}
.lt--care .lt-card .lt-ico{margin:0 auto 12px;border-radius:50%;background:color-mix(in srgb,var(--accent) 20%,var(--surface));color:color-mix(in srgb,var(--accent) 60%,var(--ink))}
.lt--care .lt-btn{border-radius:999px}
.lt--care .lt-badge i{background:var(--brand)}

/* ── Premium Dark: deep green-black, gold lines ───────────────────────────── */
.lt--premium .lt-hero{background:radial-gradient(70% 80% at 75% 35%,color-mix(in srgb,var(--brand) 20%,var(--hero)),var(--hero) 65%)}
.lt--premium .lt-h1,.lt--premium .lt-h2{letter-spacing:.005em}
.lt--premium .lt-h1{background:linear-gradient(180deg,#fff,#e9dcc0);-webkit-background-clip:text;background-clip:text;color:transparent}
.lt--premium .lt-hero-frame{background:radial-gradient(circle at 50% 45%,color-mix(in srgb,var(--brand) 22%,#1d2622),#121916 70%);border:1px solid color-mix(in srgb,var(--brand) 45%,transparent)}
.lt--premium .lt-hero-frame img{filter:drop-shadow(0 26px 30px rgba(0,0,0,.6))}
.lt--premium .lt-card .lt-ico,.lt--premium .lt-trust .lt-ico{background:transparent;border:1px solid color-mix(in srgb,var(--brand) 55%,transparent)}
.lt--premium .lt-article{background:#f7f3ea;color:#1b1a16;border-color:#e8dfcc}
.lt--premium .lt-urgent{background:var(--brand);color:#14110a}
.lt--premium .lt-urgent b{background:#14110a;color:var(--brand)}
.lt--premium .lt-errors{background:#2a1414;border-color:#5b2323;color:#fecaca}
.lt--premium .lt-burst{background:var(--brand)}

@media (prefers-reduced-motion: reduce){
  .lt *,.lt *::before,.lt *::after{animation:none!important;transition:none!important}
  .lt .reveal,.lt .reveal-group>*{opacity:1!important;transform:none!important}
}
</style>
