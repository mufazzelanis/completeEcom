{{-- Buying guide under a category / brand listing (App\Support\ListingGuide): facts and links
     that are specific to this listing, plus a short FAQ (also as FAQPage structured data).
     Needs: $guide. --}}
<style>
    .lg { margin-top: 2.5rem; background: #fff; border: 1px solid #eef2f7; border-radius: 1.25rem; padding: 1.4rem 1.5rem; }
    .lg h2 { font-size: 1.2rem; font-weight: 800; color: #111827; line-height: 1.3; }
    .lg-lead { margin-top: .5rem; font-size: .92rem; line-height: 1.6; color: #4b5563; }
    .lg-facts { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .6rem; margin-top: 1rem; }
    .lg-facts div { background: #f9fafb; border-radius: .9rem; padding: .7rem .85rem; }
    .lg-facts b { display: block; font-size: 1rem; font-weight: 800; color: #111827; }
    .lg-facts span { font-size: .72rem; color: #6b7280; }
    .lg h3 { margin-top: 1.4rem; margin-bottom: .55rem; font-size: .95rem; font-weight: 800; color: #111827; }
    .lg-chips { display: flex; flex-wrap: wrap; gap: .4rem; }
    .lg-chips a { font-size: .8rem; font-weight: 600; padding: .35rem .75rem; border-radius: 999px; border: 1px solid #e5e7eb; color: #374151; background: #fff; }
    .lg-chips a:hover { border-color: #fb923c; color: #c2410c; }
    .lg-chips small { color: #9ca3af; font-weight: 500; }
    .lg-pop { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .1rem 1.25rem; }
    .lg-pop a { display: flex; justify-content: space-between; gap: .75rem; padding: .5rem 0; border-bottom: 1px dashed #eef2f7; font-size: .86rem; color: #374151; }
    .lg-pop a:hover { color: #c2410c; }
    .lg-pop a b { white-space: nowrap; color: #111827; }
    .lg-faq details { border-bottom: 1px solid #f1f5f9; }
    .lg-faq summary { cursor: pointer; list-style: none; padding: .7rem 0; font-size: .9rem; font-weight: 700; color: #1f2937; display: flex; justify-content: space-between; gap: 1rem; }
    .lg-faq summary::-webkit-details-marker { display: none; }
    .lg-faq summary::after { content: '+'; color: #9ca3af; font-weight: 800; }
    .lg-faq details[open] summary::after { content: '–'; }
    .lg-faq p { padding: 0 0 .8rem; font-size: .88rem; line-height: 1.6; color: #4b5563; }
    .dark .lg { background: #111827; border-color: #1f2937; }
    .dark .lg h2, .dark .lg h3, .dark .lg-facts b, .dark .lg-pop a b, .dark .lg-faq summary { color: #f9fafb; }
    .dark .lg-lead, .dark .lg-faq p, .dark .lg-pop a, .dark .lg-chips a { color: #d1d5db; }
    .dark .lg-facts div { background: #1f2937; }
    .dark .lg-chips a { background: #111827; border-color: #374151; }
    .dark .lg-pop a, .dark .lg-faq details { border-color: #1f2937; }
    @media (max-width: 640px) {
        .lg { padding: 1.1rem 1rem; border-radius: 1rem; }
        .lg-facts { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .4rem; }
        .lg-facts div { padding: .55rem .6rem; }
        .lg-facts b { font-size: .88rem; }
        .lg-pop { grid-template-columns: minmax(0, 1fr); }
    }
</style>
<section class="lg" aria-labelledby="lg-title">
    <h2 id="lg-title">{{ $guide['name'] }} Price in Bangladesh — Buying Guide</h2>
    <p class="lg-lead">
        Compare {{ $guide['count'] }} {{ $guide['name'] }} products at {{ setting('site_name', config('app.name')) }}, priced from
        <strong>{{ $guide['lo'] }}</strong> to <strong>{{ $guide['hi'] }}</strong>.
        @if($guide['links'])
            {{ $guide['groupLabel'] === 'Popular brands' ? 'Popular brands include' : 'It covers' }}
            {{ collect($guide['links'])->pluck('name')->take(4)->join(', ', ' and ') }}.
        @endif
        Order online with delivery across Bangladesh.
    </p>

    <div class="lg-facts">
        <div><b>{{ $guide['lo'] }}</b><span>lowest price</span></div>
        <div><b>{{ $guide['hi'] }}</b><span>highest price</span></div>
        <div><b>{{ $guide['in_stock'] }} / {{ $guide['count'] }}</b><span>in stock</span></div>
    </div>

    @if($guide['links'])
        <h3>{{ $guide['groupLabel'] }}</h3>
        <div class="lg-chips">
            @foreach($guide['links'] as $l)
                <a href="{{ $l['url'] }}">{{ $l['name'] }} <small>({{ $l['count'] }})</small></a>
            @endforeach
        </div>
    @endif

    @if($guide['popular'])
        <h3>Popular {{ $guide['name'] }} products</h3>
        <div class="lg-pop">
            @foreach($guide['popular'] as $p)
                <a href="{{ $p['url'] }}"><span>{{ $p['name'] }}</span><b>{{ $p['priceText'] }}</b></a>
            @endforeach
        </div>
    @endif

    <h3>Frequently asked questions</h3>
    <div class="lg-faq">
        @foreach($guide['faq'] as [$q, $a])
            <details @if($loop->first) open @endif><summary>{{ $q }}</summary><p>{{ $a }}</p></details>
        @endforeach
    </div>
</section>
<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => collect($guide['faq'])->map(fn ($f) => ['@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]]])->all(),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
