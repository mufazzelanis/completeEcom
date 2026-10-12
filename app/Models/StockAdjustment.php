<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockAdjustment extends Model
{
    protected $fillable = [
        'product_id', 'product_name', 'product_sku', 'order_id', 'reason_id', 'type', 'quantity',
        'stock_before', 'stock_after', 'reference', 'reason', 'adjusted_by',
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

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function stockReason()
    {
        return $this->belongsTo(StockReason::class, 'reason_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function adjustedBy()
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }

    public function typeLabel(): string
    {
        return match($this->type) {
            'return_in'   => 'Customer Return',
            'damage_out'  => 'Damage / Loss',
            'manual_in'   => 'Manual Stock In',
            'manual_out'  => 'Manual Stock Out',
            'purchase_in' => 'Purchase Received',
            default       => $this->type,
        };
    }

    public function typeBadge(): string
    {
        return match($this->type) {
            'return_in', 'manual_in', 'purchase_in' => 'bg-green-100 text-green-700',
            'damage_out', 'manual_out'               => 'bg-red-100 text-red-600',
            default                                  => 'bg-gray-100 text-gray-600',
        };
    }
}
