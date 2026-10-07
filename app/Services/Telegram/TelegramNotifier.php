<?php

namespace App\Services\Telegram;

use App\Models\NotificationLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Admin-only order alerts sent to a Telegram chat via a bot (Settings -> Notifications ->
 * "Telegram Bot Notifications"). Deliberately separate from the customer-facing, template-
 * driven NotificationDispatcher/Channels system (App\Services\Notifications\*) — this is a
 * single fixed destination (the admin's own chat, set once as a bot token + chat ID) with no
 * per-customer preferences or templates to manage, so wiring it into that heavier machinery
 * would be the wrong amount of ceremony for what it actually does. Synchronous HTTP call and
 * a NotificationLog row per attempt, same style as Channels\SmsChannel — this codebase
 * doesn't queue any of its other notification channels either, and Telegram's API is fast
 * enough (hitting api.telegram.org directly, no local gateway round-trip) that there's no
 * real latency cost being added to the request that triggers it.
 */
class TelegramNotifier
{
    public static function isEnabled(): bool
    {
        return setting('telegram_notifications_enabled', '0') === '1'
            && (string) setting('telegram_bot_token', '') !== ''
            && (string) setting('telegram_chat_id', '') !== '';
    }

    /**
     * @param string $message HTML-formatted (Telegram's own subset: <b>, <i>, <code>, etc.) —
     *                        callers build this, not this method, so it stays a plain
     *                        "send this text" primitive rather than knowing about orders.
     */
    public static function send(string $message, string $eventType = 'order'): void
    {
        if (!self::isEnabled()) {
            return;
        }

        $botToken = setting('telegram_bot_token');
        $chatId = setting('telegram_chat_id');
        $status = 'sent';
        $error = null;

        try {
            $response = Http::timeout(10)->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
                // Telegram link-previews any URL in the text by default, which for an order
                // notification (admin panel link) just adds visual noise under a message
                // that's already short and self-contained.
                'disable_web_page_preview' => true,
            ]);

            if ($response->failed()) {
                throw new \RuntimeException('Telegram API HTTP ' . $response->status() . ': ' . mb_substr($response->body(), 0, 300));
            }
        } catch (\Throwable $e) {
            $status = 'failed';
            $error = $e->getMessage();
            Log::warning('Telegram notification failed', ['error' => $error]);
        }

        NotificationLog::create([
            'channel' => 'telegram',
            'event_type' => $eventType,
            'recipient' => $chatId,
            'status' => $status,
            'error' => $error,
            'sent_at' => now(),
        ]);
    }
}
