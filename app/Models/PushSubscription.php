<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushSubscription extends Model
{
    protected $fillable = [
        'user_id', 'scope', 'type', 'endpoint', 'endpoint_hash', 'p256dh', 'auth', 'content_encoding',
        'device_type', 'browser', 'user_agent', 'last_used_at',
    ];

    protected $casts = ['last_used_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
