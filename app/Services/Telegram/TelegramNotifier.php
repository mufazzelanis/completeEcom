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
 * per-customer preferences or templates to manage. Sent after the response has gone out, with
 * a NotificationLog row per attempt, same style as Channels\SmsChannel.
 */
class TelegramNotifier
{
    /** Telegram's limit for a photo caption, counted on the text without its HTML tags. */
    public const CAPTION_LIMIT = 1024;

    /** Most photos one album (sendMediaGroup) may hold. */
    public const ALBUM_LIMIT = 10;

    public static function isEnabled(): bool
    {
        return setting('telegram_notifications_enabled', '0') === '1'
            && (string) setting('telegram_bot_token', '') !== ''
            && (string) setting('telegram_chat_id', '') !== '';
    }

    /**
     * @param string     $message HTML-formatted (Telegram's own subset: <b>, <i>, <code>, etc.)
     * @param array|null $buttons Inline keyboard rows (Telegram InlineKeyboardButton arrays,
     *                            e.g. ['text' => '📋 Phone', 'copy_text' => ['text' => '017…']]).
     *                            If Telegram rejects them, the message is re-sent without
     *                            buttons — an order alert must never be lost over a button.
     */
    public static function send(string $message, string $eventType = 'order', ?array $buttons = null, bool $now = false): void
    {
        self::sendWithPhotos($message, [], $eventType, $buttons, $now);
    }

    /**
     * The message with product photos above it.
     *  - one photo and a message that fits a caption: a single photo post, buttons included;
     *  - otherwise the photos first (one photo, or an album of up to 10, each with its own
     *    caption), then the message with its buttons as a reply to them, so the two stay together.
     * Photos are links Telegram downloads itself, so they must be public https addresses. If
     * Telegram can't fetch one, the message still goes out as plain text.
     *
     * @param array<int, array{url: string, caption?: string}> $photos
     */
    public static function sendWithPhotos(string $message, array $photos, string $eventType = 'order', ?array $buttons = null, bool $now = false): void
    {
        if (!self::isEnabled()) {
            return;
        }
        // Sent after the page has gone out (after_response): the request that placed or
        // updated the order doesn't wait on api.telegram.org. $now: the settings page's test
        // buttons, which report the result on the same page.
        if (!$now) {
            after_response(fn () => self::sendWithPhotos($message, $photos, $eventType, $buttons, true));

            return;
        }

        $photos = array_slice(array_values(array_filter($photos, fn ($p) => str_starts_with((string) ($p['url'] ?? ''), 'https://'))), 0, self::ALBUM_LIMIT);
        $status = 'sent';
        $error = null;

        try {
            $sent = false;
            if (count($photos) === 1 && self::plainLength($message) <= self::CAPTION_LIMIT) {
                $sent = self::call('sendPhoto', ['photo' => $photos[0]['url'], 'caption' => $message], $buttons);
            } elseif ($photos) {
                $replyTo = self::postPhotos($photos);
                if ($replyTo) {
                    $sent = self::call('sendMessage', ['text' => $message, 'reply_parameters' => ['message_id' => $replyTo, 'allow_sending_without_reply' => true]], $buttons);
                }
            }
            if (!$sent && !self::call('sendMessage', ['text' => $message], $buttons)) {
                throw new \RuntimeException(self::$lastError ?: 'Telegram did not accept the message');
            }
        } catch (\Throwable $e) {
            $status = 'failed';
            $error = $e->getMessage();
            Log::warning('Telegram notification failed', ['error' => $error]);
        }

        NotificationLog::create([
            'channel' => 'telegram',
            'event_type' => $eventType,
            'recipient' => setting('telegram_chat_id'),
            'status' => $status,
            'error' => $error,
            'sent_at' => now(),
        ]);
    }

    /** Posts the photos (one, or an album) and returns the id of the first message, or null. */
    private static function postPhotos(array $photos): ?int
    {
        if (count($photos) === 1) {
            return self::call('sendPhoto', ['photo' => $photos[0]['url'], 'caption' => self::fitCaption($photos[0]['caption'] ?? '')]);
        }

        return self::call('sendMediaGroup', ['media' => array_map(fn ($p) => [
            'type' => 'photo',
            'media' => $p['url'],
            'caption' => self::fitCaption($p['caption'] ?? ''),
            'parse_mode' => 'HTML',
        ], $photos)]);
    }

    private static ?string $lastError = null;

    /**
     * One Bot API call. Returns the new message's id (the first one for an album), or null when
     * Telegram refused it. Buttons that are refused are dropped and the call tried once more.
     */
    private static function call(string $method, array $payload, ?array $buttons = null): ?int
    {
        $payload = ['chat_id' => setting('telegram_chat_id')] + $payload;
        if ($method !== 'sendMediaGroup') {
            $payload['parse_mode'] = 'HTML';
        }
        if ($method === 'sendMessage') {
            // an admin-panel link in the text would otherwise grow a preview card
            $payload['link_preview_options'] = ['is_disabled' => true];
        }
        $url = 'https://api.telegram.org/bot' . setting('telegram_bot_token') . '/' . $method;

        $response = Http::timeout(15)->post($url, $buttons ? $payload + ['reply_markup' => ['inline_keyboard' => $buttons]] : $payload);
        if ($response->failed() && $buttons) {
            Log::warning('Telegram rejected the buttons, re-sending without them', ['method' => $method, 'body' => mb_substr($response->body(), 0, 300)]);
            $response = Http::timeout(15)->post($url, $payload);
        }
        if ($response->failed()) {
            self::$lastError = 'Telegram API HTTP ' . $response->status() . ': ' . mb_substr($response->body(), 0, 300);
            Log::warning('Telegram ' . $method . ' failed', ['body' => mb_substr($response->body(), 0, 300)]);

            return null;
        }
        $result = $response->json('result');

        return (int) ($result['message_id'] ?? $result[0]['message_id'] ?? 0) ?: null;
    }

    /** Length as Telegram counts it: the text a reader sees, without the HTML tags. */
    public static function plainLength(string $html): int
    {
        return mb_strlen(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    /** A caption that is too long loses its end; tags are dropped first so none is cut in half. */
    private static function fitCaption(string $caption): string
    {
        if (self::plainLength($caption) <= self::CAPTION_LIMIT) {
            return $caption;
        }

        return e(mb_substr(html_entity_decode(strip_tags($caption), ENT_QUOTES | ENT_HTML5, 'UTF-8'), 0, self::CAPTION_LIMIT - 1)) . '…';
    }
}
