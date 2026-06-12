<?php

namespace App\Services\Notifications\Channels;

use App\Models\NotificationLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppChannel
{
    public function send(?int $userId, string $eventType, string $to, string $message): void
    {
        $driver = config('notifications.whatsapp.driver', 'log');
        $status = 'sent';
        $error  = null;

        try {
            if ($driver === 'twilio') {
                $this->sendViaTwilio($to, $message);
            } elseif ($driver === 'meta') {
                $this->sendViaMeta($to, $message);
            } else {
                Log::channel('stack')->info("[WhatsApp] To: {$to} | {$message}");
            }
        } catch (\Throwable $e) {
            $status = 'failed';
            $error  = $e->getMessage();
        }

        NotificationLog::create([
            'user_id'    => $userId,
            'channel'    => 'whatsapp',
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
        $from  = config('notifications.whatsapp.twilio_from');

        $response = Http::withBasicAuth($sid, $token)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                'From' => $from,
                'To'   => 'whatsapp:' . $to,
                'Body' => $message,
            ]);

        if ($response->failed()) {
            throw new \RuntimeException($response->body());
        }
    }

    private function sendViaMeta(string $to, string $message): void
    {
        $token   = config('notifications.whatsapp.meta_token');
        $phoneId = config('notifications.whatsapp.meta_phone_id');

        $response = Http::withToken($token)
            ->post("https://graph.facebook.com/v18.0/{$phoneId}/messages", [
                'messaging_product' => 'whatsapp',
                'to'                => $to,
                'type'              => 'text',
                'text'              => ['body' => $message],
            ]);

        if ($response->failed()) {
            throw new \RuntimeException($response->body());
        }
    }
}
