<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'event', 'description', 'subject_type', 'subject_id',
        'url', 'ip_address', 'user_agent', 'device', 'browser', 'platform',
        'properties', 'created_at',
    ];

    protected $casts = [
        'properties' => 'array',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subject()
    {
        return $this->morphTo();
    }

    public function getEventLabelAttribute(): string
    {
        return match (true) {
            $this->event === 'login'          => 'Login',
            $this->event === 'logout'         => 'Logout',
            str_starts_with($this->event, 'product.') => 'Product View',
            str_starts_with($this->event, 'order.')   => 'Order View',
            str_starts_with($this->event, 'cart.')    => 'Cart ' . ucfirst(substr($this->event, 5)),
            default => ucfirst(str_replace(['.', '_'], ' ', $this->event)),
        };
    }

    public function getEventColorAttribute(): string
    {
        return match (true) {
            $this->event === 'login'                  => 'bg-green-100 text-green-700',
            $this->event === 'logout'                 => 'bg-gray-100 text-gray-600',
            str_starts_with($this->event, 'product.') => 'bg-blue-100 text-blue-700',
            str_starts_with($this->event, 'order.')   => 'bg-purple-100 text-purple-700',
            $this->event === 'cart.add'                => 'bg-orange-100 text-orange-700',
            $this->event === 'cart.remove'             => 'bg-red-100 text-red-700',
            default => 'bg-gray-100 text-gray-700',
        };
    }

    /**
     * Rough UA parse — same regex approach as LoginActivity::record(), factored
     * out here so every logging call site (login, product view, cart, etc.)
     * gets consistent device/browser/platform values without duplicating it.
     */
    public static function parseUserAgent(string $userAgent): array
    {
        preg_match('/(Chrome|Firefox|Safari|Edge|Opera|MSIE|Trident)[\/\s]?([\d.]+)?/i', $userAgent, $browser);
        preg_match('/(Windows|Mac|Linux|Android|iPhone|iPad)/i', $userAgent, $platform);
        $device = preg_match('/(iPhone|iPad|Android)/i', $userAgent) ? 'Mobile' : 'Desktop';

        return [$device, $browser[1] ?? 'Unknown', $platform[1] ?? 'Unknown'];
    }
}
