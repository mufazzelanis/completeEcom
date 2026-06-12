<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginActivity extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'ip_address', 'user_agent', 'device', 'browser', 'platform', 'is_current',
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

        preg_match('/(Chrome|Firefox|Safari|Edge|Opera|MSIE|Trident)[\/\s]?([\d.]+)?/i', $userAgent, $browser);
        preg_match('/(Windows|Mac|Linux|Android|iPhone|iPad)/i', $userAgent, $platform);
        $device = preg_match('/(iPhone|iPad|Android)/i', $userAgent) ? 'Mobile' : 'Desktop';

        static::create([
            'user_id'    => $userId,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'device'     => $device,
            'browser'    => $browser[1] ?? 'Unknown',
            'platform'   => $platform[1] ?? 'Unknown',
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
