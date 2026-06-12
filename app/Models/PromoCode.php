<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromoCode extends Model
{
    protected $fillable = ['batch_id', 'code', 'user_id', 'order_id', 'used_at'];

    protected $casts = ['used_at' => 'datetime'];

    public function batch()
    {
        return $this->belongsTo(PromoCodeBatch::class, 'batch_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    public function isValid(): bool
    {
        if ($this->isUsed()) return false;
        if (! $this->batch->is_active) return false;
        if ($this->batch->expires_at && $this->batch->expires_at->isPast()) return false;
        return true;
    }
}
