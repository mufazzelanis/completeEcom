<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlashSaleProduct extends Model
{
    protected $fillable = [
        'flash_sale_id', 'product_id', 'discount_type', 'discount_value',
        'stock_limit', 'per_order_limit', 'sold_count', 'sort_order', 'is_featured',
    ];

    protected $casts = [
        'discount_value'  => 'decimal:2',
        'stock_limit'     => 'integer',
        'per_order_limit' => 'integer',
        'sold_count'      => 'integer',
        'sort_order'      => 'integer',
        'is_featured'     => 'boolean',
    ];

    public function flashSale()
    {
        return $this->belongsTo(FlashSale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /** The price the flash discount is taken off (the product's regular sale price, if any). */
    public function getBasePriceAttribute(): float
    {
        return (float) ($this->product->sale_price ?? $this->product->price);
    }

    public function getSalePriceAttribute(): float
    {
        $base = $this->base_price;
        if ($this->discount_type === 'percentage') {
            // older rows could hold >100% (no cap in the old form) — never a negative price
            return max(0, round($base * (1 - $this->discount_value / 100), 2));
        }
        return max(0, round($base - $this->discount_value, 2));
    }

    public function getSavingsAttribute(): float
    {
        return round($this->base_price - $this->sale_price, 2);
    }

    public function getDiscountPercentAttribute(): int
    {
        $base = $this->base_price;

        return $base > 0 ? (int) round($this->savings / $base * 100) : 0;
    }

    /** Units left at the flash price; null when the deal has no stock cap. */
    public function getRemainingAttribute(): ?int
    {
        return $this->stock_limit > 0 ? max(0, $this->stock_limit - $this->sold_count) : null;
    }

    public function getSoldPercentAttribute(): int
    {
        return $this->stock_limit > 0 ? (int) min(100, round($this->sold_count / $this->stock_limit * 100)) : 0;
    }

    /** sold_out | almost_gone | selling_fast | available | unlimited — drives the card's stock bar. */
    public function getStockStateAttribute(): string
    {
        if ($this->stock_limit === 0) return 'unlimited';
        if ($this->remaining === 0) return 'sold_out';
        if ($this->sold_percent >= 80 || $this->remaining <= 3) return 'almost_gone';
        if ($this->sold_percent >= 50) return 'selling_fast';
        return 'available';
    }

    public function isAvailable(): bool
    {
        return $this->stock_limit === 0 || $this->sold_count < $this->stock_limit;
    }

    /** Most units of this product one order may take at the flash price (null = no cap). */
    public function maxPerOrder(): ?int
    {
        $caps = array_filter([$this->per_order_limit ?: null, $this->remaining], fn ($v) => $v !== null);

        return $caps ? min($caps) : null;
    }

    /** Count units sold at checkout, never past the deal's stock cap. */
    public function recordSale(int $quantity): void
    {
        $units = $this->remaining === null ? $quantity : min($quantity, $this->remaining);
        if ($units > 0) {
            $this->increment('sold_count', $units);
        }
    }
}
