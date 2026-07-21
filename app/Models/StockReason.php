<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockReason extends Model
{
    protected $fillable = ['label', 'type', 'sort_order', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForType($query, ?string $type)
    {
        return $query->where(function ($q) use ($type) {
            $q->where('type', 'any');
            if ($type) {
                $q->orWhere('type', $type);
            }
        });
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'return_in'   => 'Customer Return',
            'damage_out'  => 'Damage / Loss',
            'manual_in'   => 'Manual Stock In',
            'manual_out'  => 'Manual Stock Out',
            'purchase_in' => 'Purchase Received',
            default       => 'Any Type',
        };
    }
}
