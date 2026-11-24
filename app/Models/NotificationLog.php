<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'channel', 'event_type', 'recipient', 'status', 'error', 'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    /** Same as FacebookConversionLog: never fall back to the DB server's clock for sent_at. */
    protected static function booted(): void
    {
        static::creating(fn ($log) => $log->sent_at ??= now());
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
