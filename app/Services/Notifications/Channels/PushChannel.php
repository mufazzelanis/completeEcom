<?php

namespace App\Services\Notifications\Channels;

use App\Models\NotificationLog;
use App\Models\PushSubscription;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushChannel
{
    public function send(
        ?int $userId,
        string $eventType,
        string $title,
        string $body,
        ?string $url = null
    ): void {
        if (!$userId) {
            return;
        }

        $subscriptions = PushSubscription::where('user_id', $userId)->get();

        if ($subscriptions->isEmpty()) {
            NotificationLog::create([
                'user_id'    => $userId,
                'channel'    => 'push',
                'event_type' => $eventType,
                'recipient'  => "user:{$userId}",
                'status'     => 'skipped',
                'error'      => 'No push subscriptions',
            ]);
            return;
        }

        foreach ($subscriptions as $sub) {
            $this->dispatch($userId, $eventType, $sub, $title, $body, $url);
        }
    }

    private function dispatch(
        int $userId,
        string $eventType,
        PushSubscription $sub,
        string $title,
        string $body,
        ?string $url
    ): void {
        $driver = config('notifications.push.driver', 'fcm');
        $status = 'sent';
        $error  = null;

        try {
            if ($driver === 'fcm') {
                $this->sendViaFcm($sub->endpoint, $title, $body, $url);
            } else {
                Log::channel('stack')->info("[PUSH] To: {$sub->endpoint} | {$title}: {$body}");
            }
        } catch (\Throwable $e) {
            $status = 'failed';
            $error  = $e->getMessage();
        }

        NotificationLog::create([
            'user_id'    => $userId,
            'channel'    => 'push',
            'event_type' => $eventType,
            'recipient'  => "user:{$userId}",
            'status'     => $status,
            'error'      => $error,
        ]);
    }

    private function sendViaFcm(string $token, string $title, string $body, ?string $url): void
    {
        $serverKey = config('notifications.push.server_key');

        $response = Http::withHeaders([
            'Authorization' => "key={$serverKey}",
            'Content-Type'  => 'application/json',
        ])->post(config('notifications.push.fcm_url'), [
            'to'           => $token,
            'notification' => [
                'title' => $title,
                'body'  => $body,
                'click_action' => $url,
            ],
            'data' => ['url' => $url],
        ]);

        if ($response->failed()) {
            throw new \RuntimeException($response->body());
        }
    }
}
