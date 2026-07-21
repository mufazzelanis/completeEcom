<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockAdjustment extends Model
{
    protected $fillable = [
        'product_id', 'order_id', 'reason_id', 'type', 'quantity',
        'stock_before', 'stock_after', 'reference', 'reason', 'adjusted_by',
    ];

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
