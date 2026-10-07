<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductView extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'session_id', 'product_id', 'view_count', 'viewed_at',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Records a view for the current visitor (upsert, not insert) — a repeat view of the
     * same product bumps view_count and refreshes viewed_at instead of adding a new row,
     * so recency/frequency ranking stays meaningful instead of the table just accumulating
     * duplicate rows for the same (visitor, product) pair.
     */
    public static function record(int $productId, ?int $userId, ?string $sessionId): void
    {
        $query = static::query()->where('product_id', $productId);
        $userId ? $query->where('user_id', $userId) : $query->where('session_id', $sessionId);

        $existing = $query->first();
        if ($existing) {
            $existing->increment('view_count');
            $existing->update(['viewed_at' => now()]);
            return;
        }

        static::create([
            'user_id' => $userId,
            'session_id' => $userId ? null : $sessionId,
            'product_id' => $productId,
            'viewed_at' => now(),
        ]);
    }
}
