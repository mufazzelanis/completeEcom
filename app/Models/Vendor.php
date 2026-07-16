<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Vendor extends Model
{
    protected $fillable = [
        'user_id', 'business_name', 'slug', 'logo', 'banner', 'description',
        'phone', 'email', 'commission_rate', 'status', 'payout_method',
        'payout_details', 'rejection_reason', 'approved_at', 'approved_by',
    ];

    protected $casts = [
        'payout_details' => 'array',
        'commission_rate' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($vendor) {
            if (empty($vendor->slug)) {
                $vendor->slug = Str::slug($vendor->business_name).'-'.Str::random(5);
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'seller_id');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function statusBadge(): string
    {
        return match ($this->status) {
            'approved' => 'bg-green-100 text-green-700',
            'pending' => 'bg-yellow-100 text-yellow-700',
            'rejected' => 'bg-red-100 text-red-700',
            'suspended' => 'bg-gray-200 text-gray-600',
            default => 'bg-gray-100 text-gray-600',
        };
    }
}
