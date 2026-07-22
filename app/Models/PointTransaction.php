<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PointTransaction extends Model
{
    protected $fillable = ['user_id', 'type', 'points', 'order_id', 'description'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function getTypeBadgeAttribute(): string
    {
        return match ($this->type) {
            'purchase_earned' => 'bg-green-100 text-green-700',
            'referral_earned' => 'bg-indigo-100 text-indigo-700',
            'redeemed'        => 'bg-red-100 text-red-700',
            default           => 'bg-gray-100 text-gray-600',
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'purchase_earned' => 'Purchase',
            'referral_earned' => 'Referral',
            'redeemed'        => 'Redeemed',
            default           => ucfirst($this->type),
        };
    }
}
