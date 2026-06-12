<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class FlashSale extends Model
{
    protected $fillable = [
        'name', 'description', 'banner_text', 'banner_color',
        'starts_at', 'ends_at', 'is_active',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at'   => 'datetime',
        'is_active' => 'boolean',
    ];

    public function products()
    {
        return $this->hasMany(FlashSaleProduct::class);
    }

    public function isLive(): bool
    {
        return $this->is_active
            && now()->between($this->starts_at, $this->ends_at);
    }

    public function getStatusAttribute(): string
    {
        if (! $this->is_active) return 'inactive';
        if (now()->lt($this->starts_at))  return 'upcoming';
        if (now()->gt($this->ends_at))    return 'ended';
        return 'live';
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'live'     => 'bg-green-100 text-green-700',
            'upcoming' => 'bg-blue-100 text-blue-700',
            'ended'    => 'bg-gray-100 text-gray-500',
            default    => 'bg-red-100 text-red-600',
        };
    }

    // Returns the currently live flash sale (if any)
    public static function current(): ?self
    {
        return self::where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->first();
    }
}
