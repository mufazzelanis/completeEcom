<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacebookConversionLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'event_name', 'event_id', 'pixel_id', 'landing_page_id', 'product_id', 'items', 'value', 'source_url', 'ip_address', 'user_agent',
        'status', 'http_status', 'response', 'sent_at',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    /** The column's DB default (CURRENT_TIMESTAMP) is the MySQL server's own clock — live, a US
     *  timezone, 5 hours behind the UTC the app stores and reads — so the app stamps it itself. */
    protected static function booted(): void
    {
        static::creating(fn ($log) => $log->sent_at ??= now());
    }

    public function landingPage()
    {
        return $this->belongsTo(LandingPage::class);
    }
}
