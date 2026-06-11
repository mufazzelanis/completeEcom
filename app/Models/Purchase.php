<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected $fillable = [
        'supplier_id', 'reference_no', 'status', 'total_amount', 'paid_amount',
        'notes', 'purchased_at', 'received_at', 'created_by',
    ];

    protected $casts = [
        'purchased_at' => 'date',
        'received_at'  => 'date',
        'total_amount' => 'decimal:2',
        'paid_amount'  => 'decimal:2',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function statusBadge(): string
    {
        return match($this->status) {
            'draft'     => 'bg-gray-100 text-gray-600',
            'ordered'   => 'bg-blue-100 text-blue-700',
            'partial'   => 'bg-yellow-100 text-yellow-700',
            'received'  => 'bg-green-100 text-green-700',
            'cancelled' => 'bg-red-100 text-red-600',
            default     => 'bg-gray-100 text-gray-600',
        };
    }

    public static function generateReference(): string
    {
        $prefix = 'PO-' . date('Ymd') . '-';
        $last = static::where('reference_no', 'like', $prefix . '%')
            ->orderByDesc('id')->value('reference_no');
        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;
        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
