<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BundleItem extends Model
{
    protected $fillable = ['bundle_product_id', 'item_product_id', 'quantity', 'discount_pct', 'sort_order'];

    protected $casts = ['discount_pct' => 'decimal:2'];

    public function bundleProduct()
    {
        return $this->belongsTo(Product::class, 'bundle_product_id');
    }

    public function itemProduct()
    {
        return $this->belongsTo(Product::class, 'item_product_id');
    }

    public function getEffectivePriceAttribute(): float
    {
        $base = (float) ($this->itemProduct->sale_price ?? $this->itemProduct->price);
        return round($base * (1 - $this->discount_pct / 100), 2);
    }
}
