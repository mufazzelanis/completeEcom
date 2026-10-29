<?php

namespace App\Services\Notifications;

use App\Models\NotificationLog;
use App\Models\Order;
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

    // Both run after the response has been sent (after_response): email (an SMTP connection
    // alone takes seconds), SMS, WhatsApp and push used to happen inside the request, so e.g.
    // changing an order's status kept the admin waiting until the customer's email went out.
    public static function customer(string $eventType, User $user, array $vars = []): void
    {
        after_response(fn () => static::dispatch('customer', $eventType, $user, $vars));
    }

    public static function admin(string $eventType, array $vars = []): void
    {
        after_response(fn () => static::dispatch('admin', $eventType, null, $vars));
    }

    /**
     * $vars may carry the Order as '_order' (and '_context' extras): order emails then get the
     * full layout (OrderEmail) and every order placeholder is filled in. Placeholders every
     * template can use: {{customer}}, {{first_name}}, {{site_name}}, plus the event's own.
     */
    private static function dispatch(string $recipient, string $eventType, ?User $user, array $vars): void
    {
        $order = ($vars['_order'] ?? null) instanceof Order ? $vars['_order'] : null;
        $vars = static::templateVars($eventType, $user, $vars);

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
                    $emailChannel, $recipient, $user, $eventType, $template, $order
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

    /** Every placeholder value for a template: the defaults, the order's, then the caller's own (which win). */
    public static function templateVars(string $eventType, ?User $user, array $vars): array
    {
        $order = ($vars['_order'] ?? null) instanceof Order ? $vars['_order'] : null;
        $context = (array) ($vars['_context'] ?? []);
        unset($vars['_order'], $vars['_context']);

        $name = trim((string) ($user?->name ?? ''));

        return array_merge(
            ['customer' => $name ?: 'there', 'first_name' => $name ? strtok($name, ' ') : 'there', 'site_name' => setting('site_name', config('app.name'))],
            $order ? OrderEmail::vars($eventType, $order, $user, $context) : [],
            array_filter($vars, fn ($v) => $v !== null && $v !== ''),
        );
    }

    private static function dispatchEmail(
        EmailChannel $ch,
        string $recipient,
        ?User $user,
        string $eventType,
        array $template,
        ?Order $order = null
    ): void {
        if ($recipient === 'admin') {
            $to = config('notifications.admin_email');
        } else {
            // a guest checkout's account may have no email; the one typed at checkout still counts
            $to = $user?->email ?: $order?->guest_email;
        }

        if (!$to || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $body = $template['body'];
        $preheader = null;
        if ($order) {
            $body = OrderEmail::body($eventType, $recipient, $body, $order);
            $preheader = OrderEmail::preheader($eventType, $order);
        }

        $ch->send($user?->id, $eventType, $to, $template['subject'] ?? '', $body, $preheader);
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
