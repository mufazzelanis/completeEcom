<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeSection extends Model
{
    protected $fillable = [
        'title', 'subtitle', 'source_type', 'category_id', 'product_limit',
        'theme', 'view_all_query', 'view_all_label', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Builds the product list for this section from its configured source type,
     * optionally narrowed to a single category regardless of source type — so
     * e.g. "Featured Products" can be scoped to just Electronics if desired.
     */
    public function getProducts()
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

        return $query->take($this->product_limit)->get();
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
