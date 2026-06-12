<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlashSaleProduct extends Model
{
    protected $fillable = [
        'flash_sale_id', 'product_id', 'discount_type', 'discount_value',
        'stock_limit', 'sold_count',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
    ];

    public function flashSale()
    {
        return $this->belongsTo(FlashSale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getSalePriceAttribute(): float
    {
        $base = (float) ($this->product->sale_price ?? $this->product->price);
        if ($this->discount_type === 'percentage') {
            return round($base * (1 - $this->discount_value / 100), 2);
        }
        return max(0, round($base - $this->discount_value, 2));
    }

    public function getSavingsAttribute(): float
    {
        $base = (float) ($this->product->sale_price ?? $this->product->price);
        return round($base - $this->sale_price, 2);
    }

    public function isAvailable(): bool
    {
        return $this->stock_limit === 0 || $this->sold_count < $this->stock_limit;
    }
}
