<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacebookConversionLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'event_name', 'event_id', 'pixel_id', 'status', 'http_status', 'response', 'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];
}
