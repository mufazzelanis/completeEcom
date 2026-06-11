<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    protected $fillable = ['name', 'code', 'address', 'city', 'phone', 'manager_name', 'is_active', 'sort_order'];

    protected $casts = ['is_active' => 'boolean'];

    public function stockEntries()
    {
        return $this->hasMany(WarehouseStock::class);
    }

    public function outgoingTransfers()
    {
        return $this->hasMany(StockTransfer::class, 'from_warehouse_id');
    }

    public function incomingTransfers()
    {
        return $this->hasMany(StockTransfer::class, 'to_warehouse_id');
    }

    public function batches()
    {
        return $this->hasMany(Batch::class);
    }

    public function totalStock(): int
    {
        return $this->stockEntries()->sum('stock');
    }
}
