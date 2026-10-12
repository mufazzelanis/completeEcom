<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    protected $fillable = [
        'purchase_id', 'product_id', 'product_name', 'product_sku', 'quantity_ordered', 'quantity_received', 'unit_cost', 'total_cost',
    ];

    /**
     * Ledger rows keep their own copy of the product name/SKU (see the
     * keep_inventory_ledger_when_products_are_deleted migration) so history still reads
     * correctly after the product itself is deleted (product_id is then set to NULL).
     */
    protected static function booted(): void
    {
        static::creating(function (self $row) {
            if ($row->product_id && !$row->product_name && ($product = Product::find($row->product_id))) {
                $row->product_name = $product->name;
                $row->product_sku = $product->sku;
            }
        });
    }

    /** Name for display — live product name, or the snapshot once the product is gone. */
    public function getDisplayNameAttribute(): string
    {
        return $this->product?->name ?? $this->product_name ?? 'Deleted product';
    }

    protected $casts = [
        'unit_cost'  => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }
}
