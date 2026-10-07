<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchQuery extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'session_id', 'query', 'searched_at',
    ];

    protected $casts = [
        'searched_at' => 'datetime',
    ];

    /**
     * Logs a submitted search for the current visitor — skips a term identical to this
     * visitor's own last one within the past hour, so hitting back/resubmitting the same
     * search (or a paginated/filtered revisit of the same results) doesn't pad their
     * history with repeats of a term they already searched minutes ago.
     */
    public static function record(string $term, ?int $userId, ?string $sessionId): void
    {
        $term = trim($term);
        if ($term === '' || mb_strlen($term) < 2) {
            return;
        }

        $query = static::query()->where('query', $term)->where('searched_at', '>=', now()->subHour());
        $userId ? $query->where('user_id', $userId) : $query->where('session_id', $sessionId);

        if ($query->exists()) {
            return;
        }

        static::create([
            'user_id' => $userId,
            'session_id' => $userId ? null : $sessionId,
            'query' => $term,
            'searched_at' => now(),
        ]);
    }
}
