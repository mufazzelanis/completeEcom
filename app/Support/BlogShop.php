<?php

namespace App\Support;

use App\Models\BlogPost;
use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Shoppable blog posts. The admin editor's "Product" button drops a placeholder into the content:
 *
 *   <div class="blog-embed blog-embed--spotlight bp-ids-12-40">🛍️ Product: …names…</div>
 *
 * (classes only: the rich_content purifier keeps div[class] and drops data-* attributes). On the
 * public page each placeholder becomes server-rendered product cards (blog/partials/embed), so the
 * products are real links in the HTML Google reads — not something a script adds later.
 */
class BlogShop
{
    public const LAYOUTS = [
        'spotlight' => 'Spotlight (one big card each)',
        'grid'      => 'Grid',
        'carousel'  => 'Swipe row',
        'list'      => 'Ranked list (#1, #2 …)',
    ];

    private const EMBED = '#<div[^>]*\bclass="([^"]*\bblog-embed\b[^"]*)"[^>]*>.*?</div>#is';

    /** Product ids used by the content's product blocks, in order of appearance. */
    public static function inlineIds(?string $html): array
    {
        preg_match_all('/\bbp-ids-([\d-]+)/', (string) $html, $m);

        return collect($m[1])->flatMap(fn ($ids) => explode('-', $ids))->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();
    }

    /**
     * Everything the article page needs in one product query:
     * byId (all products, for the inline blocks), shelf (end-of-post / sidebar list), primary (sticky bar).
     */
    public static function forPost(BlogPost $post): array
    {
        $attached = $post->products()->pluck('blog_post_product.note', 'products.id'); // id => note, in the author's order
        $inline = self::inlineIds($post->content);
        $ids = $attached->keys()->merge($inline)->unique()->values();

        $byId = $ids->isEmpty() ? collect() : self::query()->whereIn('id', $ids)->get()->keyBy('id');
        $byId->each(fn ($p) => $p->setAttribute('blog_note', $attached[$p->id] ?? null));

        $shelf = $ids->map(fn ($id) => $byId->get($id))->filter()->values();

        return [
            'byId'    => $byId,
            'shelf'   => $shelf,
            'primary' => $shelf->first(fn ($p) => $p->available_stock > 0) ?? $shelf->first(),
            'manual'  => $shelf->isNotEmpty(),
        ];
    }

    /** Active products with what the blog cards show (rating, flash price) and nothing more. */
    public static function query()
    {
        return Product::active()
            ->with(['brand:id,name', 'activeFlashSaleProduct.flashSale'])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews');
    }

    /** Swaps every product placeholder in the (already sanitized) content for its rendered cards. */
    public static function render(string $html, Collection $byId): string
    {
        if (!str_contains($html, 'blog-embed')) {
            return $html;
        }

        $n = 0;

        return preg_replace_callback(self::EMBED, function ($m) use ($byId, &$n) {
            preg_match('/\bblog-embed--([a-z]+)/', $m[1], $l);
            preg_match('/\bbp-ids-([\d-]+)/', $m[1], $i);

            $products = collect(explode('-', $i[1] ?? ''))->map(fn ($id) => $byId->get((int) $id))->filter()->values();
            if ($products->isEmpty()) {
                return ''; // product deleted / hidden since the post was written: leave no empty box behind
            }

            $layout = array_key_exists($l[1] ?? '', self::LAYOUTS) ? $l[1] : ($products->count() > 1 ? 'grid' : 'spotlight');

            return view('blog.partials.embed', ['products' => $products, 'layout' => $layout, 'index' => $n++])->render();
        }, $html);
    }
}
