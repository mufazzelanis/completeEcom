<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeSection extends Model
{
    protected $fillable = [
        'title', 'subtitle', 'source_type', 'category_id', 'product_limit', 'columns',
        'theme', 'view_all_query', 'view_all_label', 'is_active', 'sort_order',
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
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
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

        if ($this->category_id) {
            $query->where(fn ($q) => $q
                ->where('category_id', $this->category_id)
                ->orWhere('subcategory_id', $this->category_id));
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
        return $this->baseQuery()->take(self::MAX_FETCH)->get();
    }

    /**
     * True total matching this section's source/category filter, ignoring any
     * limit — used to decide whether "See More" should render at all (no point
     * showing it when there's nothing left to reveal).
     */
    public function getTotalAvailableCount(): int
    {
        return $this->baseQuery()->count();
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

        $params = match ($this->source_type) {
            'featured'    => ['featured' => 1],
            'top_selling' => ['sort' => 'popular'],
            'on_sale'     => ['on_sale' => 1],
            default       => ['sort' => 'latest'], // 'new_arrivals' and 'category'
        };

        if ($this->category_id && $this->category) {
            $params['category'] = $this->category->slug;
        }

        return route('shop.index', $params);
    }

    public function getViewAllLabelText(): string
    {
        return $this->view_all_label ?: 'VIEW ALL';
    }
}
