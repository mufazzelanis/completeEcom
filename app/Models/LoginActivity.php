<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginActivity extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'ip_address', 'user_agent', 'device', 'browser', 'platform', 'is_current', 'created_at',
    ];

    protected $casts = [
        'is_current'  => 'boolean',
        'created_at'  => 'datetime',
    ];

    public function user() { return $this->belongsTo(User::class); }

    public static function record(int $userId, string $userAgent, string $ip): void
    {
        // Clear old current flag
        static::where('user_id', $userId)->where('is_current', true)->update(['is_current' => false]);

        // Same parser ActivityLog uses — this used to carry its own copy of the old,
        // unordered version (every Chromium browser's UA also contains "Chrome" and
        // "Safari" tokens, so checking them unordered mislabeled most non-Chrome
        // browsers), which had quietly drifted from ActivityLog's copy.
        [$device, $browser, $platform] = ActivityLog::parseUserAgent($userAgent);

        static::create([
            'user_id'    => $userId,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'device'     => $device,
            'browser'    => $browser,
            'platform'   => $platform,
            'is_current' => true,
            'created_at' => now(),
        ]);

        // Keep only last 20 logins per user
        $ids = static::where('user_id', $userId)->latest('created_at')->skip(20)->take(1000)->pluck('id');
        if ($ids->isNotEmpty()) {
            static::whereIn('id', $ids)->delete();
        }
    }
}
