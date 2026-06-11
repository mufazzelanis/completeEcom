<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    protected $fillable = [
        'reference_no', 'from_warehouse_id', 'to_warehouse_id',
        'status', 'notes', 'created_by', 'completed_at',
    ];

    protected $casts = ['completed_at' => 'datetime'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($t) {
            if (empty($t->reference_no)) {
                $t->reference_no = 'TRF-' . date('Ymd') . '-' . str_pad(
                    static::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT
                );
            }
        });
    }

    public function fromWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function items()
    {
        return $this->hasMany(StockTransferItem::class, 'transfer_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function statusBadge(): string
    {
        return match($this->status) {
            'draft'      => 'bg-gray-100 text-gray-600',
            'pending'    => 'bg-yellow-100 text-yellow-700',
            'in_transit' => 'bg-blue-100 text-blue-700',
            'completed'  => 'bg-green-100 text-green-700',
            'cancelled'  => 'bg-red-100 text-red-600',
            default      => 'bg-gray-100 text-gray-600',
        };
    }

    public function statusLabel(): string
    {
        return match($this->status) {
            'draft'      => 'Draft',
            'pending'    => 'Pending',
            'in_transit' => 'In Transit',
            'completed'  => 'Completed',
            'cancelled'  => 'Cancelled',
            default      => ucfirst($this->status),
        };
    }
}
