<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    protected $fillable = [
        'event_type', 'channel', 'recipient', 'subject', 'body', 'push_title', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public static function render(string $eventType, string $channel, string $recipient, array $vars): ?array
    {
        $template = static::where('event_type', $eventType)
            ->where('channel', $channel)
            ->where('recipient', $recipient)
            ->where('is_active', true)
            ->first();

        if (!$template) {
            return null;
        }

        $replace = function (string $text) use ($vars): string {
            foreach ($vars as $key => $value) {
                $text = str_replace('{{' . $key . '}}', $value, $text);
            }
            return $text;
        };

        return [
            'subject'    => $template->subject ? $replace($template->subject) : null,
            'body'       => $replace($template->body),
            'push_title' => $template->push_title ? $replace($template->push_title) : null,
        ];
    }
}
