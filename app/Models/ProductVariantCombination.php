<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariantCombination extends Model
{
    protected $fillable = [
        'product_id', 'product_color_id', 'product_size_id',
        'sku', 'price', 'stock', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function color()
    {
        return $this->belongsTo(ProductColor::class, 'product_color_id');
    }

    public function size()
    {
        return $this->belongsTo(ProductSize::class, 'product_size_id');
    }

    /**
     * Display label for cart/order lines and the admin matrix, e.g. "Red / Large",
     * or just "Red" / "Large" when the product only uses one attribute.
     */
    public function getLabelAttribute(): string
    {
        return collect([$this->color?->name, $this->size?->name])
            ->filter()
            ->implode(' / ');
    }
}
