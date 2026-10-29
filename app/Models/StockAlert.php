<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A shopper waiting for an out-of-stock product (see StockAlertController). */
class StockAlert extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['notified_at' => 'datetime'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** 8801XXXXXXXXX for wa.me links */
    public function waPhone(): string
    {
        $digits = preg_replace('/\D/', '', $this->phone);

        return str_starts_with($digits, '01') ? '88' . $digits : $digits;
    }
}
