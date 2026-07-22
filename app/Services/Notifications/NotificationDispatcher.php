<?php

namespace App\Services\Notifications;

use App\Models\NotificationLog;
use App\Models\NotificationPreference;
use App\Models\NotificationTemplate;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\Notifications\Channels\EmailChannel;
use App\Services\Notifications\Channels\SmsChannel;
use App\Services\Notifications\Channels\PushChannel;
use App\Services\Notifications\Channels\WhatsAppChannel;

class NotificationDispatcher
{
    // Map event types to the preference "group" column suffix
    private const EVENT_GROUP = [
        'order_placed'         => 'order',
        'order_status_changed' => 'order',
        'return_status_changed' => 'return',
        'ticket_replied'       => 'ticket',
        'new_order'            => 'order',
        'low_stock'            => 'order',
        'fraud_flagged'        => 'order',
        'new_ticket'           => 'ticket',
        'ticket_reply_received' => 'ticket',
        'new_return'           => 'return',
    ];

    public static function customer(string $eventType, User $user, array $vars = []): void
    {
        static::dispatch('customer', $eventType, $user, $vars);
    }

    public static function admin(string $eventType, array $vars = []): void
    {
        static::dispatch('admin', $eventType, null, $vars);
    }

    private static function dispatch(string $recipient, string $eventType, ?User $user, array $vars): void
    {
        $eventConfig = config("notifications.events.{$eventType}", []);
        $channels    = $eventConfig['channels'] ?? [];
        $group       = self::EVENT_GROUP[$eventType] ?? 'order';
        $prefs       = $user ? NotificationPreference::forUser($user->id) : null;

        $emailChannel    = new EmailChannel();
        $smsChannel      = new SmsChannel();
        $pushChannel     = new PushChannel();
        $whatsappChannel = new WhatsAppChannel();

        // Also create in-app notification for customer
        if ($recipient === 'customer' && $user) {
            $template = NotificationTemplate::render($eventType, 'push', $recipient, $vars);
            $title    = $template['push_title'] ?? ucwords(str_replace('_', ' ', $eventType));
            $body     = $template['body'] ?? '';
            UserNotification::send($user->id, $eventType, $title, $body, $vars['url'] ?? null);
        }

        foreach ($channels as $channel) {
            if ($recipient === 'customer' && $user && $prefs) {
                if (!$prefs->allows($channel, $group)) {
                    NotificationLog::create([
                        'user_id' => $user->id,
                        'channel' => $channel,
                        'event_type' => $eventType,
                        'recipient' => self::resolveAddress($channel, $recipient, $user),
                        'status' => 'skipped',
                        'error' => 'Customer has disabled this notification channel',
                        'sent_at' => now(),
                    ]);
                    continue;
                }
            }

            $template = NotificationTemplate::render($eventType, $channel, $recipient, $vars);
            if (!$template) {
                NotificationLog::create([
                    'user_id' => $user?->id,
                    'channel' => $channel,
                    'event_type' => $eventType,
                    'recipient' => self::resolveAddress($channel, $recipient, $user),
                    'status' => 'skipped',
                    'error' => 'No active template configured for this event/channel',
                    'sent_at' => now(),
                ]);
                continue;
            }

            match ($channel) {
                'email' => self::dispatchEmail(
                    $emailChannel, $recipient, $user, $eventType, $template
                ),
                'sms' => self::dispatchSms(
                    $smsChannel, $recipient, $user, $eventType, $template
                ),
                'push' => self::dispatchPush(
                    $pushChannel, $recipient, $user, $eventType, $template
                ),
                'whatsapp' => self::dispatchWhatsApp(
                    $whatsappChannel, $recipient, $user, $eventType, $template
                ),
                default => null,
            };
        }
    }

    private static function dispatchEmail(
        EmailChannel $ch,
        string $recipient,
        ?User $user,
        string $eventType,
        array $template
    ): void {
        if ($recipient === 'admin') {
            $to = config('notifications.admin_email');
        } else {
            $to = $user?->email;
        }

        if (!$to) {
            return;
        }

        $ch->send($user?->id, $eventType, $to, $template['subject'] ?? '', $template['body']);
    }

    private static function dispatchSms(
        SmsChannel $ch,
        string $recipient,
        ?User $user,
        string $eventType,
        array $template
    ): void {
        if ($recipient === 'admin') {
            $to = config('notifications.admin_phone');
        } else {
            $to = $user?->phone;
        }

        if (!$to) {
            return;
        }

        $ch->send($user?->id, $eventType, $to, $template['body']);
    }

    private static function dispatchPush(
        PushChannel $ch,
        string $recipient,
        ?User $user,
        string $eventType,
        array $template
    ): void {
        if ($recipient === 'admin') {
            // Admin push would go to a special admin device token if configured
            return;
        }

        if (!$user) {
            return;
        }

        $ch->send(
            $user->id,
            $eventType,
            $template['push_title'] ?? $template['subject'] ?? '',
            $template['body'],
        );
    }

    private static function dispatchWhatsApp(
        WhatsAppChannel $ch,
        string $recipient,
        ?User $user,
        string $eventType,
        array $template
    ): void {
        if ($recipient === 'admin') {
            $to = config('notifications.admin_phone');
        } else {
            $to = $user?->phone;
        }

        if (!$to) {
            return;
        }

        $ch->send($user?->id, $eventType, $to, $template['body']);
    }

    private static function resolveAddress(string $channel, string $recipient, ?User $user): string
    {
        if ($recipient === 'admin') {
            return match ($channel) {
                'email' => config('notifications.admin_email') ?? 'admin',
                'sms', 'whatsapp' => config('notifications.admin_phone') ?? 'admin',
                default => 'admin',
            };
        }

        return match ($channel) {
            'email' => $user?->email ?? 'customer',
            'sms', 'whatsapp' => $user?->phone ?? 'customer',
            default => $user ? "user #{$user->id}" : 'customer',
        };
    }
}
