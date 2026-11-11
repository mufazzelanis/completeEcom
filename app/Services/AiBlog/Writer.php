<?php

namespace App\Services\AiBlog;

use App\Models\AiBlogTopic;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Writes one blog post for a topic: the article in the main language from the shop's real
 * product data + the admin's "Mitavin Note", the same article adapted into the other language,
 * product cards where the products are discussed, a banner from the real product photos, then
 * the quality check decides "publish" or "keep as draft for a person to check".
 */
class Writer
{
    public const POST_TYPES = [
        'guide'    => 'Buying guide (best picks for a need)',
        'howto'    => 'How to use / routine',
        'compare'  => 'Comparison (A vs B)',
        'problem'  => 'Problem → solution',
        'occasion' => 'Season / occasion (winter, Eid, gifts)',
        'single'   => 'One product in depth',
    ];

    private const TYPE_BRIEF = [
        'guide'    => 'A buying guide: who each product suits, how to choose, what to look for. Help the reader decide.',
        'howto'    => 'A practical how-to / routine: steps in order, how much to use, when, common mistakes.',
        'compare'  => 'A fair comparison: what each product is best at, differences that matter, who should pick which. No winner unless the facts make it clear.',
        'problem'  => 'Start from the reader\'s problem, explain it simply, then what helps — with the products as options, not miracles.',
        'occasion' => 'Seasonal / occasion article for Bangladesh (weather, festivals, gifting) with practical advice and suitable products.',
        'single'   => 'An honest in-depth look at one product: what it is, who it suits, how to use it, what to expect.',
    ];

    public function __construct(private ?AiClient $client = null)
    {
    }

    public function write(AiBlogTopic $topic): BlogPost
    {
        $client = $this->client ??= AiClient::make();
        $products = $this->products($topic);
        if ($products->isEmpty()) {
            throw new AiBlogException('No active product found for this topic — pick products, a category or a brand.');
        }

        $lang = $topic->lang ?: Settings::defaultLang();
        $other = $lang === 'bn' ? 'en' : 'bn';
        $words = Settings::words();

        $main = $client->json($this->systemPrompt($lang), $this->brief($topic, $products, $lang, $words), $this->articleSchema(true),
            16000, 'write', $topic);

        $alt = null;
        $notes = [];
        if (Settings::bilingual()) {
            try {
                $alt = $client->json($this->adaptPrompt($other), $this->adaptBrief($main, $other), $this->articleSchema(false),
                    16000, 'translate', $topic);
            } catch (AiBlogException $e) {
                $notes[] = 'The ' . ($other === 'bn' ? 'Bangla' : 'English') . ' version could not be written (' . $e->getMessage() . ') — add it by hand or leave the post in one language.';
            }
        }

        $ids = $products->pluck('id')->all();
        $content = $this->finishContent((string) ($main['content_html'] ?? ''), (array) ($main['faq'] ?? []), $lang, $products);
        $altContent = $alt ? $this->finishContent((string) ($alt['content_html'] ?? ''), (array) ($alt['faq'] ?? []), $other, $products) : null;

        $notes = array_merge(QualityGate::check($topic, $main, $content, $alt, $altContent, $ids), $notes);
        $publish = Settings::publishMode() === 'auto' && $notes === [];

        $post = BlogPost::create([
            'title'                => Str::limit(trim((string) $main['title']), 250, ''),
            'slug'                 => $this->uniqueSlug((string) ($main['slug'] ?? ''), (string) $main['title'], $topic->keyword),
            'lang'                 => $lang,
            'excerpt'              => trim((string) ($main['excerpt'] ?? '')),
            'content'              => $content,
            'meta_title'           => Str::limit(trim((string) ($main['meta_title'] ?? '')), 250, ''),
            'meta_description'     => Str::limit(trim((string) ($main['meta_description'] ?? '')), 490, ''),
            'meta_keywords'        => Str::limit(collect([$topic->keyword])->merge($main['tags'] ?? [])->filter()->unique()->implode(', '), 250, ''),
            'alt_title'            => $alt ? Str::limit(trim((string) $alt['title']), 250, '') : null,
            'alt_excerpt'          => $alt ? trim((string) ($alt['excerpt'] ?? '')) : null,
            'alt_content'          => $altContent,
            'alt_meta_title'       => $alt ? Str::limit(trim((string) ($alt['meta_title'] ?? '')), 250, '') : null,
            'alt_meta_description' => $alt ? Str::limit(trim((string) ($alt['meta_description'] ?? '')), 490, '') : null,
            'blog_category_id'     => $topic->blog_category_id ?: Settings::blogCategoryId(),
            'user_id'              => Settings::authorId() ?? \App\Models\User::where('role', 'admin')->orderBy('id')->value('id'),
            'status'               => $publish ? 'published' : 'draft',
            'published_at'         => $publish ? now() : null,
            'ai_generated'         => true,
            'review_notes'         => $notes ? implode("\n", $notes) : null,
        ]);

        $post->products()->sync(collect($ids)->mapWithKeys(fn ($id, $i) => [$id => ['sort_order' => $i, 'note' => null]])->all());
        $tagIds = collect($main['tags'] ?? [])->map(fn ($t) => trim((string) $t))->filter()->take(6)
            ->map(fn ($name) => BlogTag::firstOrCreate(['slug' => Str::slug($name) ?: Str::random(8)], ['name' => Str::limit($name, 60, '')])->id);
        $post->tags()->sync($tagIds->all());

        try {
            if ($image = BannerMaker::make($post, $products)) {
                $post->update(['image' => $image]);
            }
        } catch (\Throwable $e) {
            report($e); // a missing banner never loses the article
        }

        return $post;
    }

    /** The topic's products, or the best-selling in-stock ones of its category / brand. */
    public function products(AiBlogTopic $topic): Collection
    {
        $ids = array_values(array_filter(array_map('intval', (array) $topic->product_ids)));
        $base = fn () => Product::active()->with(['brand:id,name', 'category:id,name']);

        if ($ids) {
            return $base()->whereIn('id', $ids)->get()->sortBy(fn ($p) => array_search($p->id, $ids))->values();
        }
        if (!$topic->category_id && !$topic->brand_id) {
            return collect();
        }

        return $base()
            ->when($topic->category_id, fn ($q) => $q->where(fn ($w) => $w->where('category_id', $topic->category_id)->orWhere('subcategory_id', $topic->category_id)))
            ->when($topic->brand_id, fn ($q) => $q->where('brand_id', $topic->brand_id))
            ->where('stock', '>', 0)
            ->addSelect(['sold' => \App\Models\OrderItem::selectRaw('COALESCE(SUM(quantity), 0)')
                ->whereColumn('product_id', 'products.id')->where('created_at', '>=', now()->subDays(90))])
            ->orderByDesc('sold')->orderByDesc('views')
            ->limit(5)->get();
    }

    private function systemPrompt(string $lang): string
    {
        $site = setting('site_name', 'our shop');
        $language = $lang === 'bn'
            ? "Write in natural, everyday standard Bangla (চলিত ভাষা) the way people in Bangladesh write online. Keep product and brand names in English letters exactly as given. Common English words Bangladeshi readers use for skincare/health (sunscreen, serum, moisturizer, SPF) may stay in English."
            : 'Write in simple, clear English for readers in Bangladesh (many read English as a second language): short sentences, everyday words.';

        $style = Settings::styleGuide();

        return trim(<<<TXT
You write helpful blog articles for {$site}, an online shop in Bangladesh. The article is published on the shop's own blog, next to live product cards (with price and an order button).

LANGUAGE
{$language}

TONE
{$this->line(Settings::tone())}

FACTS — the most important rule
- Use only facts given in the brief: the product names, brands, categories, descriptions and the shop's own note. You may add widely known, general, non-medical common-sense advice (e.g. "apply sunscreen 15 minutes before going out").
- Never invent ingredients, specifications, test results, studies, statistics, awards, prices, discounts, delivery promises, customer reviews, quotes or people's names.
- Do not write prices or discounts in the text — the product cards show the live price.
- If the brief doesn't say something about a product, don't claim it.

SAFETY
- No medical claims: never say a product cures, treats, heals or prevents a disease or condition. Use careful wording ("may help", "many people find"). For medical conditions (eczema, acne that hurts, allergies, babies' skin problems) tell readers to consult a doctor or dermatologist.
- No "100% guaranteed", "miracle", "permanent results", "doctor recommended" (unless the brief says so), no fake urgency.
- Never criticise other shops or brands by name.

WRITING LIKE A HELPFUL HUMAN (Google rewards this; mass-produced filler gets a site penalised)
- Answer what the reader came for in the first 2–3 sentences. No throat-clearing openings ("In today's world…", "Are you looking for…?").
- Be specific and practical: who it suits, how to use it, mistakes to avoid, local context (Bangladesh heat, humidity, winter dryness, dust, load-shedding — only where it truly fits).
- Weave in the shop's own note (the "Mitavin Note" in the brief) as first-hand experience of the shop team ("আমাদের কাছে অনেক ক্রেতা জানতে চান…" / "Many of our customers ask…"). This is what makes the article unique — use it prominently but do not invent anything beyond it.
- Vary sentence length; use lists and tables only where they help. No keyword stuffing: use the keyword naturally in the title, the first paragraph and one heading.
- No conclusion clichés ("In conclusion", "To sum up"). End with a short, useful closing thought.

FORMAT
- content_html: clean HTML using only <h2>, <h3>, <p>, <ul>, <ol>, <li>, <strong>, <em>, <blockquote>, <table>, <thead>, <tbody>, <tr>, <th>, <td>, <a>. No <h1> (the title is shown separately), no images, no inline styles, no scripts.
- Links: only to the product URLs given in the brief.
- Product cards: right after the part that discusses a product, put a paragraph containing only the marker [[product:ID]] (ID from the brief). Each product at most once. You may group 2–3 products in one marker: [[product:ID,ID]].
- Do not put the FAQ inside content_html — return it in the faq field (3–5 real questions people ask, short honest answers).
- title: compelling and specific, under 65 characters, no clickbait. meta_title: under 60 characters. meta_description: 120–155 characters, a real sentence that makes people want to click. excerpt: 1–2 sentences.
- slug: 3–7 lowercase English words joined by hyphens, describing the topic (even when the article is in Bangla).
- tags: 3–6 short topic tags in the article's language.
{$this->section('SHOP STYLE GUIDE (follow it)', $style)}
TXT);
    }

    private function brief(AiBlogTopic $topic, Collection $products, string $lang, int $words): string
    {
        $type = $topic->post_type && isset(self::TYPE_BRIEF[$topic->post_type]) ? $topic->post_type : 'guide';

        $items = $products->map(fn (Product $p) => array_filter([
            'id'          => $p->id,
            'name'        => $p->name,
            'brand'       => $p->brand?->name,
            'category'    => $p->category?->name,
            'url'         => route('products.show', $p->slug),
            'in_stock'    => $p->available_stock > 0,
            'summary'     => $this->plain($p->short_description, 600),
            'description' => $this->plain($p->description, 1800),
            'size'        => $p->size_description ?: null,
            'origin'      => $p->country_of_origin ?: null,
        ], fn ($v) => $v !== null && $v !== ''))->values()->all();

        return json_encode([
            'topic'          => $topic->title,
            'search_keyword' => $topic->keyword ?: null,
            'article_type'   => self::TYPE_BRIEF[$type],
            'language'       => $lang === 'bn' ? 'Bangla' : 'English',
            'target_length'  => "about {$words} words (excluding the FAQ)",
            'mitavin_note'   => trim((string) $topic->note) ?: '(none given — do not invent first-hand experience; keep advice general)',
            'products'       => $items,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    private function adaptPrompt(string $lang): string
    {
        $target = $lang === 'bn'
            ? 'natural, everyday standard Bangla (চলিত ভাষা) as people in Bangladesh write online — product and brand names stay in English letters'
            : 'simple, clear English for readers in Bangladesh';

        return trim(<<<TXT
You adapt a blog article into {$target}.
- Write it the way a native writer would — not a word-for-word translation. Same facts, same advice, same structure; nothing added, nothing invented.
- Keep every [[product:ID]] marker exactly as it is, in the same place. Keep the HTML tags and links exactly.
- Keep the safety wording (no medical claims; consult a doctor where the original says so).
- title under 65 characters, meta_title under 60, meta_description 120–155 characters, excerpt 1–2 sentences, all written fresh for this language's readers (not literal).
- Adapt the FAQ the same way.
TXT);
    }

    private function adaptBrief(array $main, string $lang): string
    {
        return "Adapt this article into " . ($lang === 'bn' ? 'Bangla' : 'English') . ":\n\n" . json_encode([
            'title'            => $main['title'] ?? '',
            'meta_title'       => $main['meta_title'] ?? '',
            'meta_description' => $main['meta_description'] ?? '',
            'excerpt'          => $main['excerpt'] ?? '',
            'content_html'     => $main['content_html'] ?? '',
            'faq'              => $main['faq'] ?? [],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    /** JSON schema the AI answer must match (strict: every field required, nothing extra). */
    private function articleSchema(bool $main): array
    {
        $props = [
            'title'            => ['type' => 'string'],
            'meta_title'       => ['type' => 'string'],
            'meta_description' => ['type' => 'string'],
            'excerpt'          => ['type' => 'string'],
            'content_html'     => ['type' => 'string'],
            'faq'              => ['type' => 'array', 'items' => [
                'type' => 'object',
                'properties' => ['question' => ['type' => 'string'], 'answer' => ['type' => 'string']],
                'required' => ['question', 'answer'], 'additionalProperties' => false,
            ]],
        ];
        if ($main) {
            $props['slug'] = ['type' => 'string'];
            $props['tags'] = ['type' => 'array', 'items' => ['type' => 'string']];
        }

        return ['type' => 'object', 'properties' => $props, 'required' => array_keys($props), 'additionalProperties' => false];
    }

    /** Product markers → the blog's product card blocks, FAQ + disclaimer appended, no <h1>. */
    public function finishContent(string $html, array $faq, string $lang, Collection $products): string
    {
        $byId = $products->keyBy('id');
        $used = [];

        $html = preg_replace('#<h1[^>]*>.*?</h1>#is', '', $html); // the title is shown above the article already
        $html = preg_replace_callback('#(?:<p[^>]*>\s*)?\[\[product:\s*([\d,\s]+)\]\](?:\s*</p>)?#i', function ($m) use ($byId, &$used) {
            $ids = collect(preg_split('/[\s,]+/', $m[1]))->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $byId->has($id) && !isset($used[$id]))->unique()->values();
            if ($ids->isEmpty()) {
                return '';
            }
            $ids->each(function ($id) use (&$used) { $used[$id] = true; });
            $layout = $ids->count() > 1 ? 'grid' : 'spotlight';
            $names = e($ids->map(fn ($id) => $byId[$id]->name)->implode(', '));

            return '<div class="blog-embed blog-embed--' . $layout . ' bp-ids-' . $ids->implode('-') . '">🛍️ Product: ' . $names . '</div>';
        }, $html);

        $faq = collect($faq)->filter(fn ($f) => trim((string) ($f['question'] ?? '')) !== '' && trim((string) ($f['answer'] ?? '')) !== '');
        if ($faq->isNotEmpty()) {
            $html .= "\n<h2>" . ($lang === 'bn' ? 'সাধারণ জিজ্ঞাসা' : 'Frequently asked questions') . "</h2>\n";
            foreach ($faq->take(6) as $f) {
                $html .= '<h3>' . e(trim($f['question'])) . '</h3><p>' . e(trim($f['answer'])) . "</p>\n";
            }
        }

        if (($disclaimer = Settings::disclaimer($lang)) !== '') {
            $html .= "\n<p><em>" . e($disclaimer) . '</em></p>';
        }

        return trim($html);
    }

    private function uniqueSlug(string $aiSlug, string $title, ?string $keyword): string
    {
        $slug = Str::slug(Str::limit($aiSlug, 80, ''));
        if ($slug === '' || !preg_match('/[a-z]/', $slug)) {
            $slug = Str::slug((string) $keyword) ?: Str::slug($title);
        }
        $slug = trim(Str::limit($slug, 80, ''), '-') ?: 'post-' . Str::lower(Str::random(6));

        $base = $slug;
        for ($i = 2; BlogPost::where('slug', $slug)->exists(); $i++) {
            $slug = $base . '-' . $i;
        }

        return $slug;
    }

    private function plain(?string $html, int $max): ?string
    {
        $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        return $text === '' ? null : Str::limit($text, $max, '…');
    }

    private function line(string $s): string
    {
        return trim($s);
    }

    private function section(string $title, string $body): string
    {
        return $body === '' ? '' : "\n{$title}\n{$body}\n";
    }
}
