<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferralReward extends Model
{
    protected $fillable = [
        'referral_code_id', 'referrer_id', 'referee_id',
        'order_id', 'reward_amount', 'status',
    ];

    protected $casts = ['reward_amount' => 'decimal:2'];

    public function referralCode()
    {
        return $this->belongsTo(ReferralCode::class);
    }

    public function referrer()
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referee()
    {
        return $this->belongsTo(User::class, 'referee_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'approved' => 'bg-green-100 text-green-700',
            'paid'     => 'bg-blue-100 text-blue-700',
            'rejected' => 'bg-red-100 text-red-700',
            default    => 'bg-yellow-100 text-yellow-700',
        };
    }
}
