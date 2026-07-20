<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsletterSubscriber extends Model
{
    protected $fillable = [
        'email', 'is_active', 'unsubscribe_token', 'subscribed_at', 'unsubscribed_at',
    ];

    protected $casts = [
        'is_active'       => 'boolean',
        'subscribed_at'   => 'datetime',
        'unsubscribed_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
