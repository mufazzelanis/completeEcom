<?php

namespace App\Support;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;

/**
 * Finds the live product/category behind a URL that no longer matches exactly — an old
 * WooCommerce slug, a slug that lost its "-200ml", "15ml" vs "-15-ml", a duplicate's "-2" — so
 * it can be 301'd there (Google passes the old URL's ranking on) instead of 404/410 (ranking
 * lost). Deliberately strict: most of the words must match and every number in the requested
 * slug (sizes, counts, strengths) must be in the match too, so 100ml never lands on 200ml.
 */
class SlugMatcher
{
    private const STOP = ['and', 'the', 'for', 'with', 'of', 'in', 'a', 'an', 'to', 'by', 'from', 'pcs', 'pc', 'piece', 'pieces', 'pack', 'x'];

    /** lowercase words, letters split from digits ("eyes15ml" → eyes 15 ml), stop-words dropped */
    public static function tokens(string $slug): array
    {
        $s = strtolower(urldecode($slug));
        $s = preg_replace('/(\d)([a-z])|([a-z])(\d)/', '$1$3 $2$4', $s);
        $s = preg_replace('/(\d)([a-z])|([a-z])(\d)/', '$1$3 $2$4', $s); // twice: overlapping pairs
        $words = preg_split('/[^a-z0-9]+/', $s, -1, PREG_SPLIT_NO_EMPTY);
        // singular = plural ("electronic" / "electronics", "capsule" / "capsules")
        $words = array_map(fn ($w) => strlen($w) > 3 && str_ends_with($w, 's') && !str_ends_with($w, 'ss') ? substr($w, 0, -1) : $w, $words);

        return array_values(array_unique(array_diff($words, self::STOP)));
    }

    public static function score(array $want, array $have): float
    {
        if (!$want || !$have) {
            return 0;
        }
        // every number asked for must be there (sizes/strengths/counts decide the product)
        foreach ($want as $w) {
            if (ctype_digit($w) && !in_array($w, $have, true)) {
                return 0;
            }
        }
        $common = count(array_intersect($want, $have));

        return $common / count(array_unique(array_merge($want, $have))); // Jaccard
    }

    public static function product(string $slug): ?Product
    {
        $id = Cache::remember('slugmatch:p:' . md5(strtolower($slug)), 86400, function () use ($slug) {
            $want = self::tokens($slug);
            $words = array_values(array_filter($want, fn ($w) => !ctype_digit($w) && strlen($w) > 2));
            if (count($want) < 2 || !$words) {
                return 0;
            }
            // squashed match first: "pipingrock-yohimbe…" = "piping-rock-yohimbe…"
            $squash = preg_replace('/[^a-z0-9]/', '', strtolower($slug));
            usort($words, fn ($a, $b) => strlen($b) <=> strlen($a));
            $candidates = Product::active()
                ->where(function ($q) use ($words) {
                    foreach (array_slice($words, 0, 3) as $w) {
                        $q->orWhere('slug', 'like', "%{$w}%");
                    }
                })
                ->limit(200)->get(['id', 'slug']);

            $best = null;
            $bestScore = 0;
            foreach ($candidates as $c) {
                if (preg_replace('/[^a-z0-9]/', '', strtolower($c->slug)) === $squash) {
                    return $c->id;
                }
                $s = self::score($want, self::tokens($c->slug));
                if ($s > $bestScore) {
                    [$best, $bestScore] = [$c->id, $s];
                }
            }

            return $bestScore >= 0.6 ? $best : 0;
        });

        return $id ? Product::find($id) : null;
    }

    public static function category(string $slug): ?Category
    {
        $want = self::tokens($slug);
        $squash = preg_replace('/[^a-z0-9]/', '', strtolower($slug));
        $best = null;
        $bestScore = 0;
        foreach (Category::where('is_active', true)->get(['id', 'slug', 'name']) as $c) {
            if (preg_replace('/[^a-z0-9]/', '', strtolower($c->slug)) === $squash) {
                return $c;
            }
            $s = self::score($want, self::tokens($c->slug));
            if ($s > $bestScore) {
                [$best, $bestScore] = [$c, $s];
            }
        }

        return $bestScore >= 0.67 ? $best : null;
    }

    public static function brand(string $slug): ?Brand
    {
        $squash = preg_replace('/[^a-z0-9]/', '', strtolower($slug));

        return Brand::get(['id', 'slug'])->first(fn ($b) => preg_replace('/[^a-z0-9]/', '', strtolower($b->slug)) === $squash);
    }
}
