<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminAlert extends Model
{
    protected $fillable = ['user_id', 'type', 'title', 'body', 'url', 'data', 'read_at'];

    protected $casts = [
        'data'    => 'array',
        'read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    /**
     * Shape sent to the browser (bell dropdown, toasts, sound choice). "open_url" goes
     * through the mark-as-read redirect so a click from the bell, a toast, or a phone
     * notification all clear the alert the same way instead of needing three code paths.
     */
    public function toFeedItem(): array
    {
        return [
            'id'       => $this->id,
            'type'     => $this->type,
            'title'    => $this->title,
            'body'     => $this->body,
            'open_url' => route('admin.alerts.open', $this),
            'read'     => $this->read_at !== null,
            'time_ago' => $this->created_at?->diffForHumans(short: true),
        ];
    }
}
