<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A checkout that got as far as a phone number but no order (see CheckoutLeadController). */
class CheckoutLead extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'items' => 'array',
        'subtotal' => 'decimal:2',
        'contacted_at' => 'datetime',
        'sms_sent_at' => 'datetime',
        'last_activity_at' => 'datetime',
    ];

    /** Still on the checkout page within this many minutes — not "abandoned" yet. */
    public const ACTIVE_MINUTES = 15;

    /** status => [label, hex] */
    public const STATUSES = [
        'open'      => ['Not contacted', '#ef4444'],
        'contacted' => ['Contacted', '#f59e0b'],
        'recovered' => ['Ordered', '#10b981'],
        'dismissed' => ['Dismissed', '#94a3b8'],
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function statusMeta(): array
    {
        return self::STATUSES[$this->status] ?? self::STATUSES['open'];
    }

    public function isStillOnCheckout(): bool
    {
        return in_array($this->status, ['open', 'contacted'], true)
            && $this->last_activity_at?->gt(now()->subMinutes(self::ACTIVE_MINUTES));
    }

    /** 8801XXXXXXXXX for wa.me links */
    public function waPhone(): string
    {
        $digits = preg_replace('/\D/', '', $this->phone);

        return str_starts_with($digits, '01') ? '88' . $digits : $digits;
    }

    /** Close every open lead for this session or phone once an order is placed. */
    public static function markRecovered(Order $order, ?string $sessionId): void
    {
        static::whereIn('status', ['open', 'contacted'])
            ->where(function ($q) use ($order, $sessionId) {
                $q->where('phone', $order->shipping_phone);
                if ($sessionId) {
                    $q->orWhere('session_id', $sessionId);
                }
            })
            ->update(['status' => 'recovered', 'order_id' => $order->id, 'updated_at' => now()]);
    }
}
