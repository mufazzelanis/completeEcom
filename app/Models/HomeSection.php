<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeSection extends Model
{
    protected $fillable = [
        'title', 'subtitle', 'source_type', 'category_id', 'category_ids', 'product_limit', 'columns',
        'theme', 'view_all_query', 'view_all_label', 'is_active', 'sort_order',
        'see_more_label', 'see_more_color_from', 'see_more_color_to', 'see_more_text_color',
    ];

    /**
     * Full literal Tailwind class strings, one per supported column count — the
     * Tailwind CDN build compiles by scanning final rendered HTML, so a class built
     * by string concatenation (e.g. "md:grid-cols-{$n}") would never get generated.
     * Mobile stays at 2 and tablet caps at 3 regardless, since 5-6 columns would be
     * too cramped on a phone; only the desktop breakpoint follows the admin's choice.
     */
    private const GRID_COLS_CLASSES = [
        2 => 'grid-cols-2 sm:grid-cols-2 md:grid-cols-2',
        3 => 'grid-cols-2 sm:grid-cols-3 md:grid-cols-3',
        4 => 'grid-cols-2 sm:grid-cols-3 md:grid-cols-4',
        5 => 'grid-cols-2 sm:grid-cols-3 md:grid-cols-5',
        6 => 'grid-cols-2 sm:grid-cols-3 md:grid-cols-6',
    ];

    public function getGridColsClass(): string
    {
        return self::GRID_COLS_CLASSES[$this->columns] ?? self::GRID_COLS_CLASSES[4];
    }

    protected $casts = [
        'is_active' => 'boolean',
        'category_ids' => 'array',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * The categories this section is scoped to. category_ids (multi-select) is the
     * source of truth going forward; category_id (the old single-select column) is
     * only read as a fallback for any row that somehow has neither — shouldn't
     * happen post-migration, but keeps old data working if it ever does.
     */
    public function getCategoryIdsList(): array
    {
        if (!empty($this->category_ids)) {
            return $this->category_ids;
        }

        return $this->category_id ? [$this->category_id] : [];
    }

    public function selectedCategories()
    {
        $ids = $this->getCategoryIdsList();

        return $ids ? Category::whereIn('id', $ids)->orderBy('name')->get() : collect();
    }

    /**
     * Hard ceiling on how many products a homepage section ever fetches — "See More"
     * reveals the rest of THIS batch in place (no extra request), it doesn't mean
     * "fetch the entire catalog." A section with more products than this still gets
     * a working "See More", it just also has the small "VIEW ALL" link (which goes
     * to the real, unbounded /shop listing) for anything beyond the batch.
     */
    private const MAX_FETCH = 40;

    private function baseQuery()
    {
        $query = Product::with('category', 'brand', 'reviews', 'activeFlashSaleProduct')->active();

        $categoryIds = $this->getCategoryIdsList();
        if ($categoryIds) {
            $query->where(fn ($q) => $q
                ->whereIn('category_id', $categoryIds)
                ->orWhereIn('subcategory_id', $categoryIds));

            // Section is scoped to specific categories, so the admin's manual
            // per-category product order (set via Admin > Products > Manual sort)
            // applies here too — takes priority over the source_type's own
            // ordering below. (Only meaningful for a single category — with
            // several combined, "sort_order" isn't a shared sequence across them,
            // but it's still a stable, deterministic order rather than none.)
            $query->orderBy('sort_order');
        }

        match ($this->source_type) {
            'featured'     => $query->featured()->latest(),
            'top_selling'  => $query->where('stock', '>', 0)->orderByDesc('views'),
            'on_sale'      => $query->whereNotNull('sale_price')->orderByDesc('updated_at'),
            default        => $query->latest(), // 'new_arrivals' and 'category'
        };

        return $query;
    }

    /**
     * Builds the product list for this section from its configured source type,
     * optionally narrowed to a single category regardless of source type — so
     * e.g. "Featured Products" can be scoped to just Electronics if desired.
     * Fetches up to MAX_FETCH (not just product_limit) so "See More" on the
     * homepage can reveal the rest without a second request.
     */
    public function getProducts()
    {
        if ($this->source_type === 'personalized') {
            return $this->personalizedProducts ??= $this->buildPersonalizedProducts();
        }

        return $this->baseQuery()->take(self::MAX_FETCH)->get();
    }

    /**
     * True total matching this section's source/category filter, ignoring any
     * limit — used to decide whether "See More" should render at all (no point
     * showing it when there's nothing left to reveal).
     */
    public function getTotalAvailableCount(): int
    {
        if ($this->source_type === 'personalized') {
            // "Total" doesn't mean quite the same thing here — there's no single fixed
            // filter, just however many products buildPersonalizedProducts() could fill
            // the feed with (personalized matches + general fallback). Reusing the
            // already-built list (memoized on the instance) both answers "is there enough
            // to bother with See More" correctly AND avoids computing the whole thing twice
            // in the same request (home.blade.php calls both this and getProducts()).
            return $this->getProducts()->count();
        }

        return $this->baseQuery()->count();
    }

    /**
     * Not persisted — getProducts()/getTotalAvailableCount() both need the same computed
     * personalized list within one request (building it involves several queries), so it's
     * built once and reused rather than recomputed per call.
     */
    private $personalizedProducts = null;

    /**
     * The homepage "for you" feed (Admin > Home Sections > Product Source > "Personalized
     * (For You)"). Deliberately a MIX, not a purely personalized list: a brand-new guest
     * with no search/view history yet would otherwise see an empty or near-empty section,
     * and even for a returning visitor, filling the whole grid with only their own narrow
     * interests reads as a filter bubble rather than "more to discover." Two signals, each
     * re-matched against the CURRENT catalog (not resolved once and cached, which could
     * point at products that are since out of stock/deleted):
     *   1. Categories of this visitor's recently viewed products (ProductView).
     *   2. Their recent search terms, re-run as a live name/description match (SearchQuery).
     * Whatever's left after that (always true for a first-time guest, often true even for
     * a returning one) is filled with generally popular products, and the combined list is
     * shuffled once so it reads as a single blended feed rather than "personalized block,
     * then generic block" — no visible seam between the two.
     */
    private function buildPersonalizedProducts()
    {
        $userId = auth()->id();
        $sessionId = session()->getId();

        $viewedQuery = ProductView::query()->orderByDesc('viewed_at')->limit(30)
            ->with('product:id,category_id,subcategory_id');
        $userId ? $viewedQuery->where('user_id', $userId) : $viewedQuery->where('session_id', $sessionId);
        $viewed = $viewedQuery->get()->filter(fn ($v) => $v->product);

        $viewedProductIds = $viewed->pluck('product_id');
        $interestedCategoryIds = $viewed->pluck('product.category_id')
            ->merge($viewed->pluck('product.subcategory_id'))
            ->filter()
            ->unique()
            ->values();

        $searchQuery = SearchQuery::query()->orderByDesc('searched_at')->limit(10);
        $userId ? $searchQuery->where('user_id', $userId) : $searchQuery->where('session_id', $sessionId);
        $terms = $searchQuery->pluck('query');

        $personalizedIds = collect();

        if ($interestedCategoryIds->isNotEmpty()) {
            $personalizedIds = $personalizedIds->merge(
                Product::active()
                    ->where(fn ($q) => $q
                        ->whereIn('category_id', $interestedCategoryIds)
                        ->orWhereIn('subcategory_id', $interestedCategoryIds))
                    ->whereNotIn('id', $viewedProductIds)
                    ->inRandomOrder()
                    ->limit(self::MAX_FETCH)
                    ->pluck('id')
            );
        }

        foreach ($terms as $term) {
            $personalizedIds = $personalizedIds->merge(
                Product::active()
                    ->where(fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('short_description', 'like', "%{$term}%"))
                    ->whereNotIn('id', $viewedProductIds)
                    ->limit(10)
                    ->pluck('id')
            );
        }

        $personalizedIds = $personalizedIds->unique()->values();

        // Fill the rest with generally popular products — always runs for a first-time
        // guest (both signals above are empty), and tops up a returning visitor's feed
        // whenever their own history doesn't fill the whole batch on its own.
        $remaining = self::MAX_FETCH - $personalizedIds->count();
        $fallbackIds = collect();
        if ($remaining > 0) {
            $fallbackIds = Product::active()
                ->whereNotIn('id', $personalizedIds)
                // Also excluded here, not just from the two personalized queries above —
                // otherwise a product this visitor already looked at can slip back in
                // through the popularity fallback once it's no longer in $personalizedIds,
                // showing them the exact item they just viewed again instead of something
                // new to discover.
                ->whereNotIn('id', $viewedProductIds)
                ->orderByDesc('views')
                ->limit($remaining)
                ->pluck('id');
        }

        $allIds = $personalizedIds->merge($fallbackIds)->unique()->take(self::MAX_FETCH);

        return Product::with('category', 'brand', 'reviews', 'activeFlashSaleProduct')
            ->whereIn('id', $allIds)
            ->get()
            ->shuffle();
    }

    /**
     * "View All" link for this section, built automatically from its own source_type
     * and category filter — admins never type a query string by hand. view_all_query
     * still wins if set, so anyone who saved a manual override keeps it.
     */
    public function getViewAllUrl(): string
    {
        if ($this->view_all_query) {
            return route('shop.index') . '?' . $this->view_all_query;
        }

        // No single filter to replicate for a personalized mix — plain /shop (everything)
        // is the only honest destination.
        if ($this->source_type === 'personalized') {
            return route('shop.index');
        }

        $params = match ($this->source_type) {
            'featured'    => ['featured' => 1],
            'top_selling' => ['sort' => 'popular'],
            'on_sale'     => ['on_sale' => 1],
            default       => ['sort' => 'latest'], // 'new_arrivals' and 'category'
        };

        $slugs = $this->selectedCategories()->pluck('slug');
        // A section scoped to one category links to that category's own page — the real,
        // indexable URL — instead of a /shop?category=… filter duplicate of it, so homepage
        // link equity flows to the category page Google should rank.
        if ($slugs->count() === 1) {
            return route('shop.category', $slugs->first());
        }
        if ($slugs->isNotEmpty()) {
            // ShopController accepts a comma-separated list of slugs for ?category=,
            // so this works whether the section is scoped to one category or several.
            $params['category'] = $slugs->implode(',');
        }

        return route('shop.index', $params);
    }

    public function getViewAllLabelText(): string
    {
        return $this->view_all_label ?: 'VIEW ALL';
    }

    /**
     * Text for the big in-page "reveal more products" pill button (home.blade.php) — kept
     * separate from getViewAllLabelText() above (the small header link to the full /shop
     * listing) so the two don't have to read identically, since they do different things.
     */
    public function getSeeMoreLabelText(): string
    {
        return $this->see_more_label ?: 'See More';
    }

    /**
     * The "reveal more" button's background — a CSS gradient string built from the admin's
     * own two colors when both are set, otherwise null so the caller falls back to its
     * existing hardcoded theme classes (bg-gradient-to-r from-orange-500 to-red-500, or the
     * 'sale' theme's white/orange-text) and nothing changes for a section nobody's customized.
     */
    public function getSeeMoreBackgroundStyle(): ?string
    {
        if (!$this->see_more_color_from || !$this->see_more_color_to) {
            return null;
        }

        return "background-image: linear-gradient(to right, {$this->see_more_color_from}, {$this->see_more_color_to});";
    }
}
