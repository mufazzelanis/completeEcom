<?php

namespace App\Support;

use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Shared by every "send a 6-digit code by email" flow (login 2FA challenge, admin
 * 2FA setup, ...) so the branded template only lives in one place instead of being
 * duplicated per controller.
 */
class OtpMailer
{
    public static function send(string $email, string $code, string $subject, string $intro, string $logContext): void
    {
        try {
            $html = view('emails.otp-code', [
                'code'    => $code,
                'subject' => $subject,
                'intro'   => $intro,
            ])->render();

            Mail::send([], [], function (Message $message) use ($email, $subject, $html) {
                $message->to($email)->subject($subject)->html($html);
            });
        } catch (Throwable $e) {
            Log::warning("{$logContext} OTP email failed to send: " . $e->getMessage());
        }
    }
}
