<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    protected $fillable = [
        'user_id', 'guest_email', 'guest_token', 'payment_id', 'order_number', 'status', 'subtotal', 'discount', 'shipping', 'tax', 'total',
        'coupon_code', 'payment_method', 'payment_status', 'payment_charge',
        'shipping_name', 'shipping_phone', 'shipping_address', 'shipping_city',
        'shipping_state', 'shipping_zip', 'shipping_country', 'notes',
        'fraud_score', 'fraud_flags', 'is_fraud_flagged', 'fraud_checked_at',
    ];

    protected $casts = [
        'subtotal'          => 'decimal:2',
        'discount'          => 'decimal:2',
        'shipping'          => 'decimal:2',
        'tax'               => 'decimal:2',
        'total'             => 'decimal:2',
        'fraud_flags'       => 'array',
        'is_fraud_flagged'  => 'boolean',
        'fraud_checked_at'  => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($order) {
            if (empty($order->guest_token) && !$order->user_id) {
                $order->guest_token = Str::random(64);
            }
        });
    }

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

    public function isGuest(): bool
    {
        return is_null($this->user_id);
    }

    public function accessibleBy(?int $userId, ?string $token = null): bool
    {
        if ($this->user_id && $userId === $this->user_id) {
            return true;
        }
        if ($this->guest_token && $token === $this->guest_token) {
            return true;
        }
        return false;
    }

    public static function generateOrderNumber(): string
    {
        return 'ORD-' . strtoupper(uniqid());
    }

    public static function generateGuestToken(): string
    {
        return Str::random(64);
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
