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
