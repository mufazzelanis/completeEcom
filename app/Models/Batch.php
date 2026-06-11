<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Batch extends Model
{
    protected $fillable = [
        'product_id', 'warehouse_id', 'lot_number', 'quantity',
        'manufacture_date', 'expiry_date', 'notes', 'is_active',
    ];

    protected $casts = [
        'manufacture_date' => 'date',
        'expiry_date'      => 'date',
        'is_active'        => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->expiry_date
            && !$this->isExpired()
            && $this->expiry_date->diffInDays(now()) <= $days;
    }

    public function statusBadge(): string
    {
        if ($this->isExpired()) return 'bg-red-100 text-red-700';
        if ($this->isExpiringSoon()) return 'bg-yellow-100 text-yellow-700';
        return 'bg-green-100 text-green-700';
    }

    public function statusLabel(): string
    {
        if ($this->isExpired()) return 'Expired';
        if ($this->isExpiringSoon()) return 'Expiring Soon';
        return 'Active';
    }
}
