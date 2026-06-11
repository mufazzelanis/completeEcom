<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id', 'payment_id', 'order_number', 'status', 'subtotal', 'discount', 'shipping', 'tax', 'total',
        'coupon_code', 'payment_method', 'payment_status', 'payment_charge',
        'shipping_name', 'shipping_phone', 'shipping_address', 'shipping_city',
        'shipping_state', 'shipping_zip', 'shipping_country', 'notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'shipping' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function returns()
    {
        return $this->hasMany(\App\Models\ProductReturn::class);
    }

    public static function generateOrderNumber(): string
    {
        return 'ORD-' . strtoupper(uniqid());
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'pending'    => 'bg-yellow-100 text-yellow-800',
            'processing' => 'bg-blue-100 text-blue-800',
            'shipped'    => 'bg-purple-100 text-purple-800',
            'delivered'  => 'bg-green-100 text-green-800',
            'cancelled'  => 'bg-red-100 text-red-800',
            'refunded'   => 'bg-gray-100 text-gray-800',
            default      => 'bg-gray-100 text-gray-800',
        };
    }
}
