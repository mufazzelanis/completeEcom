<?php

namespace App\Services\AdminAlerts;

use App\Models\PushSubscription;
use GuzzleHttp\Client;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Throwable;

/**
 * Thin wrapper over minishlink/web-push (VAPID-signed, RFC 8291 encrypted Web Push) — what
 * actually lets a phone show a system notification for an order while the admin app is
 * closed. Deliberately fail-soft everywhere: a push outage, a bad key, or a dead
 * subscription must never break checkout or the newsletter form, and must never lose the
 * in-app alert (which is already saved by the time this runs).
 */
class WebPushSender
{
    public static function isConfigured(): bool
    {
        $vapid = config('admin_alerts.vapid');
        $subject = (string) ($vapid['subject'] ?? '');

        return ! empty($vapid['public_key'])
            && ! empty($vapid['private_key'])
            && (str_starts_with($subject, 'mailto:') || str_starts_with($subject, 'https://'));
    }

    /**
     * @param  Collection<int, array{subscription: PushSubscription, payload: array}>  $messages
     *         one entry per (subscription, notification) pair — payloads differ per admin
     *         because each carries that admin's own alert id for the mark-as-read redirect.
     * @return array{sent:int, failed:int, expired:int}
     */
    public function send(Collection $messages, string $urgency = 'high'): array
    {
        $result = ['sent' => 0, 'failed' => 0, 'expired' => 0];

        if ($messages->isEmpty() || ! self::isConfigured()) {
            return $result;
        }

        try {
            $vapid = config('admin_alerts.vapid');
            $webPush = new WebPush(
                ['VAPID' => [
                    'subject'    => $vapid['subject'],
                    'publicKey'  => $vapid['public_key'],
                    'privateKey' => $vapid['private_key'],
                ]],
                ['TTL' => 3600, 'urgency' => $urgency],
                new Client(['timeout' => 8, 'connect_timeout' => 4, 'http_errors' => false]),
            );

            $byEndpoint = [];
            foreach ($messages as $message) {
                /** @var PushSubscription $sub */
                $sub = $message['subscription'];
                $byEndpoint[$sub->endpoint] = $sub;

                $webPush->queueNotification(
                    Subscription::create([
                        'endpoint'        => $sub->endpoint,
                        'publicKey'       => $sub->p256dh,
                        'authToken'       => $sub->auth,
                        'contentEncoding' => $sub->content_encoding ?: 'aes128gcm',
                    ]),
                    json_encode($message['payload'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                );
            }

            foreach ($webPush->flush() as $report) {
                $sub = $byEndpoint[$report->getEndpoint()] ?? null;

                if ($report->isSuccess()) {
                    $result['sent']++;
                    $sub?->forceFill(['last_used_at' => now()])->save();
                } elseif ($report->isSubscriptionExpired()) {
                    // 404/410 from the push service = the user revoked permission or
                    // uninstalled the app; it will never work again, so stop trying.
                    $result['expired']++;
                    $sub?->delete();
                } else {
                    $result['failed']++;
                    Log::warning('Admin alert web push failed: ' . $report->getReason());
                }
            }
        } catch (Throwable $e) {
            $result['failed'] += max(1, $messages->count() - $result['sent'] - $result['expired']);
            Log::warning('Admin alert web push crashed: ' . $e->getMessage());
        }

        return $result;
    }
}
