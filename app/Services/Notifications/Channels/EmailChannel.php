<?php

namespace App\Services\Notifications\Channels;

use App\Models\NotificationLog;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;

class EmailChannel
{
    public function send(
        ?int $userId,
        string $eventType,
        string $to,
        string $subject,
        string $body
    ): void {
        $status = 'sent';
        $error  = null;

        try {
            $html = wrap_branded_email($body);

            Mail::send([], [], function (Message $message) use ($to, $subject, $html) {
                $message->to($to)
                    ->subject($subject)
                    ->html($html);
            });
        } catch (\Throwable $e) {
            $status = 'failed';
            $error  = $e->getMessage();
        }

        NotificationLog::create([
            'user_id'    => $userId,
            'channel'    => 'email',
            'event_type' => $eventType,
            'recipient'  => $to,
            'status'     => $status,
            'error'      => $error,
        ]);
    }
}
