<?php

namespace App\Services\AiBlog;

use App\Models\BlogPost;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

/**
 * "Suggest topics": the cheap model looks at what sells, what people search on Google to
 * reach this site (Search Console) and what the blog already covers, and proposes new articles
 * people actually look for. The admin ticks the good ones into the queue.
 */
class TopicSuggester
{
    /** @return array<int, array{title:string, keyword:string, post_type:string, product_ids:int[], why:string}> */
    public function suggest(int $count = 12, string $focus = ''): array
    {
        $products = Product::active()->with(['brand:id,name', 'category:id,name'])
            ->where('stock', '>', 0)
            ->addSelect(['sold' => \App\Models\OrderItem::selectRaw('COALESCE(SUM(quantity), 0)')
                ->whereColumn('product_id', 'products.id')->where('created_at', '>=', now()->subDays(90))])
            ->orderByDesc('sold')->orderByDesc('views')->limit(60)->get();

        $queries = DB::table('search_console_rows')
            ->where('date', '>=', now()->subDays(28)->toDateString())
            ->select('query', DB::raw('SUM(impressions) as imp'))
            ->groupBy('query')->orderByDesc('imp')->limit(40)->pluck('query')->all();

        $existing = BlogPost::latest('id')->limit(80)->pluck('title')->all();

        $brief = json_encode([
            'wanted'           => "{$count} new blog article ideas",
            'admin_focus'      => $focus !== '' ? $focus : null,
            'site_language'    => Settings::defaultLang() === 'bn' ? 'Write titles in Bangla' : 'Write titles in English',
            'products'         => $products->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'brand' => $p->brand?->name, 'category' => $p->category?->name])->all(),
            'google_searches_that_reach_us' => $queries,
            'existing_posts'   => $existing,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $system = <<<TXT
You plan blog articles for an online shop in Bangladesh. Suggest articles that real people search for and that genuinely help them — buying guides, how-tos, comparisons, problem→solution, seasonal (Bangladesh weather, festivals).
Rules:
- Each idea uses 1–5 products from the list (by id) that truly fit it.
- Never repeat or closely overlap an existing post; ideas must also differ from each other (no "best X for dry skin" + "best X for oily skin" template variations).
- keyword: the phrase people would type into Google (Bangla or English, as people really search).
- why: one short sentence on who it helps / why people search it.
- If admin_focus is given, all ideas must follow it.
TXT;

        $schema = ['type' => 'object', 'additionalProperties' => false, 'required' => ['topics'], 'properties' => [
            'topics' => ['type' => 'array', 'items' => ['type' => 'object', 'additionalProperties' => false,
                'required' => ['title', 'keyword', 'post_type', 'product_ids', 'why'],
                'properties' => [
                    'title'       => ['type' => 'string'],
                    'keyword'     => ['type' => 'string'],
                    'post_type'   => ['type' => 'string', 'enum' => array_keys(Writer::POST_TYPES)],
                    'product_ids' => ['type' => 'array', 'items' => ['type' => 'integer']],
                    'why'         => ['type' => 'string'],
                ]]],
        ]];

        $data = AiClient::make(light: true)->json($system, $brief, $schema, 8000, 'suggest', null, 'low');

        $valid = $products->pluck('id')->flip();

        return collect($data['topics'] ?? [])
            ->map(fn ($t) => [
                'title'       => trim((string) ($t['title'] ?? '')),
                'keyword'     => trim((string) ($t['keyword'] ?? '')),
                'post_type'   => array_key_exists($t['post_type'] ?? '', Writer::POST_TYPES) ? $t['post_type'] : 'guide',
                'product_ids' => collect($t['product_ids'] ?? [])->map(fn ($id) => (int) $id)->filter(fn ($id) => isset($valid[$id]))->unique()->take(5)->values()->all(),
                'why'         => trim((string) ($t['why'] ?? '')),
            ])
            ->filter(fn ($t) => $t['title'] !== '' && $t['product_ids'] !== [])
            ->take($count)->values()->all();
    }
}
