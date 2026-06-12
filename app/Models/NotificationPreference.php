<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'email_order', 'email_return', 'email_ticket', 'email_promo',
        'sms_order',   'sms_return',   'sms_ticket',   'sms_promo',
        'push_order',  'push_return',  'push_ticket',  'push_promo',
        'whatsapp_order', 'whatsapp_return', 'whatsapp_ticket', 'whatsapp_promo',
    ];

    protected $casts = [
        'email_order' => 'boolean', 'email_return' => 'boolean',
        'email_ticket' => 'boolean', 'email_promo' => 'boolean',
        'sms_order' => 'boolean',   'sms_return' => 'boolean',
        'sms_ticket' => 'boolean',  'sms_promo' => 'boolean',
        'push_order' => 'boolean',  'push_return' => 'boolean',
        'push_ticket' => 'boolean', 'push_promo' => 'boolean',
        'whatsapp_order' => 'boolean', 'whatsapp_return' => 'boolean',
        'whatsapp_ticket' => 'boolean', 'whatsapp_promo' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function forUser(int $userId): self
    {
        return static::firstOrCreate(['user_id' => $userId]);
    }

    public function allows(string $channel, string $eventGroup): bool
    {
        $column = $channel . '_' . $eventGroup;
        return (bool) ($this->$column ?? false);
    }
}
