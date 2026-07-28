<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorPayout extends Model
{
    protected $fillable = ['vendor_id', 'amount', 'method', 'reference', 'notes', 'paid_by'];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function transactions()
    {
        return $this->hasMany(VendorTransaction::class, 'payout_id');
    }
}
