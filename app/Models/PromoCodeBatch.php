<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PromoCodeBatch extends Model
{
    protected $fillable = [
        'name', 'prefix', 'discount_type', 'discount_value',
        'min_order_amount', 'expires_at', 'generated_count', 'used_count', 'is_active',
    ];

    protected $casts = [
        'discount_value'    => 'decimal:2',
        'min_order_amount'  => 'decimal:2',
        'expires_at'        => 'datetime',
        'is_active'         => 'boolean',
    ];

    public function codes()
    {
        return $this->hasMany(PromoCode::class, 'batch_id');
    }

    public function generate(int $count): void
    {
        $prefix = strtoupper($this->prefix ? $this->prefix . '-' : '');
        $codes  = [];
        $now    = now();

        for ($i = 0; $i < $count; $i++) {
            $codes[] = [
                'batch_id'   => $this->id,
                'code'       => $prefix . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4)),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        PromoCode::insert($codes);
        $this->increment('generated_count', $count);
    }

    public function getUsageRateAttribute(): float
    {
        return $this->generated_count > 0
            ? round(($this->used_count / $this->generated_count) * 100, 1)
            : 0;
    }
}
