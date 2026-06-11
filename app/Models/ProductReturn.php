<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductReturn extends Model
{
    protected $table = 'returns';

    protected $fillable = [
        'order_id', 'user_id', 'return_number', 'status',
        'refund_type', 'reason', 'admin_note', 'processed_by', 'processed_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];

    public static function generateNumber(): string
    {
        $prefix = 'RET-' . date('Ymd') . '-';
        $last = static::where('return_number', 'like', $prefix . '%')->max('return_number');
        $seq = $last ? (int) substr($last, -4) + 1 : 1;
        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    public function statusBadge(): string
    {
        return match($this->status) {
            'pending'   => 'bg-yellow-100 text-yellow-700',
            'approved'  => 'bg-green-100 text-green-700',
            'rejected'  => 'bg-red-100 text-red-700',
            'completed' => 'bg-blue-100 text-blue-700',
            default     => 'bg-gray-100 text-gray-600',
        };
    }

    public function statusLabel(): string
    {
        return ucfirst($this->status);
    }

    public function refundTypeLabel(): string
    {
        return match($this->refund_type) {
            'refund'       => 'Cash Refund',
            'exchange'     => 'Exchange',
            'store_credit' => 'Store Credit',
            default        => $this->refund_type,
        };
    }

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function user(): BelongsTo  { return $this->belongsTo(User::class); }
    public function processedBy(): BelongsTo { return $this->belongsTo(User::class, 'processed_by'); }
    public function items(): HasMany  { return $this->hasMany(ReturnItem::class, 'return_id'); }
}
