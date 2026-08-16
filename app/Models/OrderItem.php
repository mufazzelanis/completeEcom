<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'product_id', 'product_variant_combination_id', 'variant_label',
        'product_name', 'price', 'quantity', 'subtotal',
        'download_expires_at', 'download_count', 'last_downloaded_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'download_expires_at' => 'datetime',
        'last_downloaded_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function combination()
    {
        return $this->belongsTo(ProductVariantCombination::class, 'product_variant_combination_id');
    }
}
