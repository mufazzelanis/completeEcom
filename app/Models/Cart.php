<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $fillable = ['user_id', 'session_id', 'product_id', 'product_variant_combination_id', 'quantity'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function combination()
    {
        return $this->belongsTo(ProductVariantCombination::class, 'product_variant_combination_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * A combination's own price overrides the product's (flash sale included) —
     * leaving it blank means "inherit the product's price", which is what most
     * variants will do.
     */
    public function getUnitPriceAttribute()
    {
        if ($this->combination && $this->combination->price !== null) {
            return (float) $this->combination->price;
        }

        return $this->product->final_price;
    }

    public function getSubtotalAttribute()
    {
        return $this->unit_price * $this->quantity;
    }
}
