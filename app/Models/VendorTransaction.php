<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorTransaction extends Model
{
    protected $fillable = [
        'vendor_id', 'order_id', 'order_item_id', 'sale_amount', 'commission_rate',
        'commission_amount', 'net_amount', 'status', 'payout_id', 'paid_at', 'notes',
    ];

    protected $casts = [
        'sale_amount' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function payout()
    {
        return $this->belongsTo(VendorPayout::class);
    }

    public function statusBadge(): string
    {
        return match ($this->status) {
            'paid' => 'bg-green-100 text-green-700',
            'available' => 'bg-blue-100 text-blue-700',
            'hold' => 'bg-yellow-100 text-yellow-700',
            'cancelled' => 'bg-red-100 text-red-700',
            default => 'bg-gray-100 text-gray-600',
        };
    }
}
