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

        // {{ name }} with or without spaces; a placeholder with no value is dropped rather than
        // sent to a customer as literal "{{customer}}". Values going into an email's HTML body
        // are escaped (a name like "<b>Rahim" must not become markup).
        $replace = function (string $text, bool $html = false) use ($vars): string {
            return preg_replace_callback('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', function ($m) use ($vars, $html) {
                $value = $vars[$m[1]] ?? '';
                $value = is_scalar($value) ? (string) $value : '';

                return $html ? e($value) : $value;
            }, $text);
        };

        return [
            'subject'    => $template->subject ? trim(preg_replace('/\s{2,}/', ' ', $replace($template->subject))) : null,
            'body'       => $replace($template->body, $channel === 'email'),
            'push_title' => $template->push_title ? $replace($template->push_title) : null,
        ];
    }
}
