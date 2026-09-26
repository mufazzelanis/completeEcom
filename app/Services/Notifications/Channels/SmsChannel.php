<?php

namespace App\Services\Notifications\Channels;

use App\Models\NotificationLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsChannel
{
    public function send(?int $userId, string $eventType, string $to, string $message): void
    {
        $driver = config('notifications.sms.driver', 'log');
        $status = 'sent';
        $error  = null;

        try {
            if ($driver === 'twilio') {
                $this->sendViaTwilio($to, $message);
            } elseif ($driver === 'http') {
                $this->sendViaHttp($to, $message);
            } else {
                Log::channel('stack')->info("[SMS] To: {$to} | {$message}");
            }
        } catch (\Throwable $e) {
            $status = 'failed';
            $error  = $e->getMessage();
        }

        NotificationLog::create([
            'user_id'    => $userId,
            'channel'    => 'sms',
            'event_type' => $eventType,
            'recipient'  => $to,
            'status'     => $status,
            'error'      => $error,
        ]);
    }

    /**
     * Generic gateway for local (Bangladesh) SMS providers: SMS_HTTP_URL is a URL template with
     * {to} (11-digit 01XXXXXXXXX), {to88} (8801XXXXXXXXX) and {message} placeholders, sent as GET
     * (default) or POST. Example: https://provider.example/api?key=KEY&senderid=ID&number={to88}&message={message}
     */
    private function sendViaHttp(string $to, string $message): void
    {
        $url = (string) config('notifications.sms.http_url');
        if ($url === '') {
            throw new \RuntimeException('SMS_HTTP_URL is not set.');
        }
        $to88 = '88' . ltrim(preg_replace('/\D+/', '', $to), '8');
        $url = strtr($url, ['{to88}' => rawurlencode($to88), '{to}' => rawurlencode($to), '{message}' => rawurlencode($message)]);

        $response = strtoupper((string) config('notifications.sms.http_method', 'GET')) === 'POST'
            ? Http::timeout(10)->asForm()->post(strtok($url, '?'), $this->queryToArray($url))
            : Http::timeout(10)->get($url);

        if ($response->failed()) {
            throw new \RuntimeException('SMS gateway HTTP ' . $response->status() . ': ' . mb_substr($response->body(), 0, 300));
        }
    }

    private function queryToArray(string $url): array
    {
        parse_str((string) parse_url($url, PHP_URL_QUERY), $out);

        return $out;
    }

    private function sendViaTwilio(string $to, string $message): void
    {
        $sid   = config('notifications.sms.sid');
        $token = config('notifications.sms.token');
        $from  = config('notifications.sms.from');

        $response = Http::withBasicAuth($sid, $token)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                'From' => $from,
                'To'   => $to,
                'Body' => $message,
            ]);

        if ($response->failed()) {
            throw new \RuntimeException($response->body());
        }
    }
}
