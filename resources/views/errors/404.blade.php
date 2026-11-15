{{--
    "Page not found" (404) — also used for 410 Gone (errors/410). A page of its own, not the
    storefront layout: an address that matches no route never runs the web middleware (session,
    shared view data) the layout depends on, and a not-found page must never itself fail.
    Everything that reads the database is guarded, so the worst case is a plainer page.

    What it does for a lost visitor: brand-coloured animated "4 🛍 4", a search box, "did you mean…"
    products matched from the words in the broken address, popular categories and best sellers —
    so a dead link still ends in a product page instead of the back button. noindex; status stays 404.
--}}
@php
    $gone = ($gone ?? false) || (isset($exception) && method_exists($exception, 'getStatusCode') && $exception->getStatusCode() === 410);
    $siteName = 'Mitavin';
    $primary = '#ea580c';
    $secondary = '#ec4899';
    $logo = null;
    $currency = '৳';
    $guess = collect();
    $popular = collect();
    $categories = collect();

    try {
        $siteName = setting('site_name', $siteName) ?: $siteName;
        $primary = preg_match('/^#[0-9a-f]{6}$/i', (string) setting('primary_color')) ? setting('primary_color') : $primary;
        $secondary = preg_match('/^#[0-9a-f]{6}$/i', (string) setting('secondary_color')) ? setting('secondary_color') : $secondary;
        $logo = setting_file_url('site_logo');
        $currency = setting('currency_symbol', '৳') ?: '৳';

        // "did you mean": products whose names share the words of the broken address
        $words = collect(preg_split('/[^a-z0-9]+/i', strtolower((string) basename(request()->path()))))
            ->filter(fn ($w) => strlen($w) >= 3 && !is_numeric($w) && !in_array($w, ['products', 'product', 'shop', 'category', 'brand', 'pages', 'html', 'php', 'www'], true))
            ->unique()->take(5)->values();
        if ($words->isNotEmpty()) {
            $guess = \App\Models\Product::active()->where('stock', '>', 0)
                ->where(fn ($q) => $words->each(fn ($w) => $q->orWhere('name', 'like', "%{$w}%")))
                ->orderByDesc('views')->limit(30)->get(['id', 'name', 'slug', 'price', 'sale_price', 'image'])
                ->sortByDesc(fn ($p) => $words->filter(fn ($w) => str_contains(strtolower($p->name), $w))->count())
                ->take(4)->values();
        }

        $cache = fn (string $key, \Closure $fn) => \Illuminate\Support\Facades\Cache::remember('notfound:' . $key, 900, $fn);
        $popular = $cache('popular', fn () => \App\Models\Product::active()->where('stock', '>', 0)->whereNotNull('image')
            ->orderByDesc('views')->limit(8)->get(['id', 'name', 'slug', 'price', 'sale_price', 'image']))
            ->reject(fn ($p) => $guess->contains('id', $p->id))->take($guess->isEmpty() ? 8 : 4)->values();
        $categories = $cache('categories', fn () => \App\Models\Category::active()->whereNull('parent_id')
            ->withCount(['products' => fn ($q) => $q->where('is_active', true)])->orderByDesc('products_count')
            ->limit(8)->get(['id', 'name', 'slug'])->filter(fn ($c) => $c->products_count > 0)->values());
    } catch (\Throwable $e) {
        // a broken database never turns "page not found" into "server error"
    }

    $price = fn ($p) => $currency . number_format((float) ($p->sale_price && $p->sale_price < $p->price ? $p->sale_price : $p->price));
    $thumb = fn ($p) => $p->image ? (image_thumb($p->image, 300) ?? \Illuminate\Support\Facades\Storage::url($p->image)) : null;
    $home = url('/');
    $shop = url('/shop');
@endphp
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, follow">
    <title>{{ $gone ? 'This page is no longer available' : 'Page not found' }} | {{ $siteName }}</title>
    <meta name="theme-color" content="{{ $primary }}">
    <style>
        :root { --p: {{ $primary }}; --s: {{ $secondary }}; --bg: #fff8f4; --card: #ffffff; --ink: #111827; --muted: #6b7280; --line: #f1e7e1; }
        @media (prefers-color-scheme: dark) { :root { --bg: #0b0f17; --card: #111827; --ink: #f3f4f6; --muted: #9ca3af; --line: #1f2937; } }
        * { box-sizing: border-box; }
        html, body { margin: 0; }
        body { font-family: "Hind Siliguri", "Inter", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; background: var(--bg); color: var(--ink); min-height: 100vh; overflow-x: hidden; -webkit-font-smoothing: antialiased; }
        a { color: inherit; text-decoration: none; }

        /* soft brand glows drifting in the background */
        .glow { position: fixed; border-radius: 50%; filter: blur(70px); opacity: .28; pointer-events: none; z-index: 0; animation: drift 18s ease-in-out infinite alternate; }
        .glow.a { width: 420px; height: 420px; background: var(--p); top: -120px; left: -100px; }
        .glow.b { width: 360px; height: 360px; background: var(--s); bottom: -120px; right: -80px; animation-delay: -6s; }
        @keyframes drift { to { transform: translate(60px, 40px) scale(1.12); } }

        .wrap { position: relative; z-index: 1; max-width: 1080px; margin: 0 auto; padding: 20px 16px 48px; }
        header { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
        .brand { display: flex; align-items: center; gap: 10px; font-weight: 800; font-size: 20px; }
        .brand img { height: 36px; width: auto; }
        .help { font-size: 14px; color: var(--muted); }
        .help a { color: var(--p); font-weight: 600; }

        .hero { text-align: center; padding: 28px 0 8px; }
        .digits { display: flex; align-items: center; justify-content: center; gap: clamp(6px, 2vw, 18px); font-weight: 900; font-size: clamp(88px, 22vw, 180px); line-height: 1; letter-spacing: -4px; user-select: none; }
        .digits span { background: linear-gradient(135deg, var(--p), var(--s)); -webkit-background-clip: text; background-clip: text; color: transparent; display: inline-block; animation: pop .8s cubic-bezier(.2, 1.4, .4, 1) both; }
        .digits span:last-child { animation-delay: .15s; }
        @keyframes pop { from { transform: translateY(30px) scale(.6); opacity: 0; } }

        .bag { width: clamp(84px, 20vw, 150px); height: clamp(84px, 20vw, 150px); animation: float 3.2s ease-in-out infinite; filter: drop-shadow(0 18px 22px color-mix(in srgb, var(--p) 35%, transparent)); }
        @keyframes float { 50% { transform: translateY(-14px) rotate(-4deg); } }
        .bag .eye { animation: blink 4s infinite; transform-origin: center; transform-box: fill-box; }
        @keyframes blink { 0%, 92%, 100% { transform: scaleY(1); } 95% { transform: scaleY(.1); } }
        .bag .lens { animation: search 2.6s ease-in-out infinite; transform-origin: 70% 70%; transform-box: fill-box; }
        @keyframes search { 50% { transform: translate(-6px, 4px) rotate(-12deg); } }
        .shadow { width: 90px; height: 12px; margin: 6px auto 0; border-radius: 50%; background: color-mix(in srgb, var(--ink) 12%, transparent); animation: shadow 3.2s ease-in-out infinite; }
        @keyframes shadow { 50% { transform: scaleX(.7); opacity: .5; } }

        .sparkle { position: absolute; color: var(--p); font-size: 18px; animation: twinkle 2.4s ease-in-out infinite; }
        @keyframes twinkle { 50% { opacity: .2; transform: scale(.6) rotate(45deg); } }

        h1 { margin: 22px 0 6px; font-size: clamp(24px, 5vw, 36px); line-height: 1.25; }
        .sub { margin: 0 auto; max-width: 560px; color: var(--muted); font-size: 16px; line-height: 1.6; }
        .sub small { display: block; margin-top: 4px; font-size: 14px; }

        .search { margin: 26px auto 0; max-width: 560px; display: flex; background: var(--card); border: 2px solid var(--line); border-radius: 999px; padding: 6px; box-shadow: 0 12px 30px -18px rgba(0, 0, 0, .35); transition: border-color .2s, box-shadow .2s; }
        .search:focus-within { border-color: var(--p); box-shadow: 0 0 0 5px color-mix(in srgb, var(--p) 18%, transparent); }
        .search input { flex: 1; min-width: 0; border: 0; outline: 0; background: transparent; color: var(--ink); font: inherit; font-size: 16px; padding: 10px 16px; }
        .search button { border: 0; cursor: pointer; border-radius: 999px; padding: 0 22px; font: inherit; font-weight: 700; color: #fff; background: linear-gradient(135deg, var(--p), var(--s)); }

        .actions { margin-top: 18px; display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; }
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 12px 22px; border-radius: 14px; font-weight: 700; font-size: 15px; transition: transform .15s, box-shadow .15s, background .15s; }
        .btn:hover { transform: translateY(-2px); }
        .btn:active { transform: scale(.97); }
        .btn svg { width: 18px; height: 18px; }
        .btn.primary { color: #fff; background: linear-gradient(135deg, var(--p), var(--s)); box-shadow: 0 12px 24px -12px var(--p); }
        .btn.ghost { background: var(--card); border: 1.5px solid var(--line); }
        .btn.ghost:hover { border-color: var(--p); color: var(--p); }

        section { margin-top: 44px; }
        .sec-title { display: flex; align-items: baseline; justify-content: space-between; gap: 10px; margin-bottom: 14px; }
        .sec-title h2 { margin: 0; font-size: 20px; }
        .sec-title a { color: var(--p); font-weight: 600; font-size: 14px; }

        .chips { display: flex; flex-wrap: wrap; gap: 8px; }
        .chip { padding: 9px 16px; border-radius: 999px; background: var(--card); border: 1.5px solid var(--line); font-size: 14px; font-weight: 600; transition: all .15s; }
        .chip:hover { border-color: var(--p); color: var(--p); transform: translateY(-1px); }

        .grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; }
        @media (max-width: 760px) { .grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; } }
        .card { background: var(--card); border: 1px solid var(--line); border-radius: 18px; overflow: hidden; display: flex; flex-direction: column; transition: transform .2s, box-shadow .2s; animation: rise .5s ease both; }
        .card:hover { transform: translateY(-4px); box-shadow: 0 18px 30px -20px rgba(0, 0, 0, .45); }
        @keyframes rise { from { opacity: 0; transform: translateY(16px); } }
        .card .img { aspect-ratio: 1; background: #fff; display: flex; align-items: center; justify-content: center; padding: 10px; }
        .card img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .card .body { padding: 10px 12px 14px; display: flex; flex-direction: column; gap: 6px; flex: 1; }
        .card .name { font-size: 14px; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .card .price { margin-top: auto; font-weight: 800; color: var(--p); }

        footer { margin-top: 48px; text-align: center; color: var(--muted); font-size: 13px; }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { animation: none !important; transition: none !important; } }
    </style>
</head>
<body>
<div class="glow a"></div><div class="glow b"></div>

<div class="wrap">
    <header>
        <a href="{{ $home }}" class="brand" aria-label="{{ $siteName }} home">
            @if($logo)<img src="{{ $logo }}" alt="{{ $siteName }}">@else{{ $siteName }}@endif
        </a>
        <span class="help">সাহায্য দরকার? <a href="{{ url('/contact') }}">যোগাযোগ করুন</a></span>
    </header>

    <main class="hero">
        <div class="digits" aria-hidden="true" style="position: relative">
            <span>4</span>
            <div>
                <svg class="bag" viewBox="0 0 120 120" fill="none">
                    <defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="{{ $primary }}"/><stop offset="1" stop-color="{{ $secondary }}"/></linearGradient></defs>
                    <path d="M40 38c0-13 9-22 20-22s20 9 20 22" stroke="url(#g)" stroke-width="7" stroke-linecap="round"/>
                    <path d="M22 38h76l-6 66a8 8 0 0 1-8 7H36a8 8 0 0 1-8-7l-6-66z" fill="url(#g)"/>
                    <ellipse class="eye" cx="48" cy="62" rx="5" ry="6.5" fill="#fff"/>
                    <ellipse class="eye" cx="72" cy="62" rx="5" ry="6.5" fill="#fff"/>
                    <circle cx="49" cy="63.5" r="2.4" fill="#111827"/><circle cx="73" cy="63.5" r="2.4" fill="#111827"/>
                    <path d="M50 84c6-5 14-5 20 0" stroke="#fff" stroke-width="4" stroke-linecap="round"/>
                    <g class="lens"><circle cx="92" cy="86" r="13" fill="#fff" fill-opacity=".9" stroke="#111827" stroke-width="5"/><path d="M101 96l11 11" stroke="#111827" stroke-width="7" stroke-linecap="round"/></g>
                </svg>
                <div class="shadow"></div>
            </div>
            <span>{{ $gone ? '0' : '4' }}</span>
            <i class="sparkle" style="top: 8%; left: 18%">✦</i>
            <i class="sparkle" style="top: 20%; right: 16%; animation-delay: .8s; color: {{ $secondary }}">✦</i>
            <i class="sparkle" style="bottom: 12%; left: 30%; animation-delay: 1.4s; font-size: 12px">✦</i>
        </div>

        @if($gone)
            <h1>এই পেজটি আর নেই</h1>
            <p class="sub">পণ্যটি বা লেখাটি সরিয়ে ফেলা হয়েছে। নিচে খুঁজে দেখুন, একই রকম আরও অনেক কিছু আছে!
                <small>This page has been removed — but there's plenty more to explore.</small></p>
        @else
            <h1>উফ! পেজটি খুঁজে পাওয়া যাচ্ছে না</h1>
            <p class="sub">লিংকটি হয়তো ভুল, অথবা পেজটি সরিয়ে নেওয়া হয়েছে। চিন্তা নেই, আপনার পছন্দের জিনিস এখানেই আছে!
                <small>Looks like this page wandered off. Let's find what you were looking for.</small></p>
        @endif

        <form class="search" action="{{ $shop }}" method="GET" role="search">
            <input type="search" name="search" placeholder="কী খুঁজছেন? যেমন: CeraVe, Biotin, baby lotion…" aria-label="Search products" autocomplete="off">
            <button type="submit">খুঁজুন</button>
        </form>

        <div class="actions">
            <a href="{{ $home }}" class="btn primary">
                <svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 11l9-7 9 7v9a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/></svg>
                হোম পেজ
            </a>
            <a href="{{ $shop }}" class="btn ghost">
                <svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12l-1 13H7L6 7zm3 0a3 3 0 0 1 6 0"/></svg>
                সব পণ্য দেখুন
            </a>
            <a href="{{ $home }}" class="btn ghost" onclick="if (history.length > 1 && document.referrer.indexOf(location.host) !== -1) { history.back(); return false; }">
                <svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                আগের পেজে ফিরুন
            </a>
        </div>
    </main>

    @if($guess->isNotEmpty())
    <section>
        <div class="sec-title"><h2>🔍 আপনি কি এটা খুঁজছিলেন?</h2></div>
        <div class="grid">
            @foreach($guess as $i => $p)
                <a class="card" href="{{ route('products.show', $p->slug) }}" style="animation-delay: {{ $i * 70 }}ms">
                    <div class="img">@if($thumb($p))<img src="{{ $thumb($p) }}" alt="{{ $p->name }}" loading="lazy" width="300" height="300">@endif</div>
                    <div class="body"><span class="name">{{ $p->name }}</span><span class="price">{{ $price($p) }}</span></div>
                </a>
            @endforeach
        </div>
    </section>
    @endif

    @if($categories->isNotEmpty())
    <section>
        <div class="sec-title"><h2>জনপ্রিয় ক্যাটাগরি</h2><a href="{{ url('/categories') }}">সব দেখুন →</a></div>
        <div class="chips">
            @foreach($categories as $c)
                <a class="chip" href="{{ route('shop.category', $c->slug) }}">{{ $c->name }}</a>
            @endforeach
        </div>
    </section>
    @endif

    @if($popular->isNotEmpty())
    <section>
        <div class="sec-title"><h2>🔥 সবচেয়ে জনপ্রিয়</h2><a href="{{ $shop }}">আরও দেখুন →</a></div>
        <div class="grid">
            @foreach($popular as $i => $p)
                <a class="card" href="{{ route('products.show', $p->slug) }}" style="animation-delay: {{ ($i + 2) * 70 }}ms">
                    <div class="img">@if($thumb($p))<img src="{{ $thumb($p) }}" alt="{{ $p->name }}" loading="lazy" width="300" height="300">@endif</div>
                    <div class="body"><span class="name">{{ $p->name }}</span><span class="price">{{ $price($p) }}</span></div>
                </a>
            @endforeach
        </div>
    </section>
    @endif

    <footer>© {{ date('Y') }} {{ $siteName }} · অরিজিনাল পণ্য · ক্যাশ অন ডেলিভারি · সারা দেশে ডেলিভারি</footer>
</div>
</body>
</html>
