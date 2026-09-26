<?php

namespace App\Http\Controllers;

use App\Models\Otp;
use App\Services\Notifications\Channels\SmsChannel;
use App\Support\PhoneValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * SMS one-time-code check that a checkout phone number is real and reachable
 * (Settings → Orders → Phone check = "otp"). Verified numbers are remembered in the
 * session so the customer only does this once per number.
 */
class CheckoutPhoneController extends Controller
{
    private const PURPOSE = 'checkout_phone';
    private const SESSION_KEY = 'checkout_phone_verified';
    private const TRUST_MINUTES = 60;

    /** Is the OTP gate active? Off when the mode isn't "otp", or in production with no real SMS gateway (so orders never get blocked by a missing gateway). */
    public static function required(): bool
    {
        if (PhoneValidator::mode() !== 'otp') {
            return false;
        }

        return self::gatewayConfigured() || ! app()->environment('production');
    }

    public static function gatewayConfigured(): bool
    {
        return in_array(config('notifications.sms.driver'), ['http', 'twilio'], true)
            && (config('notifications.sms.driver') === 'http'
                ? filled(config('notifications.sms.http_url'))
                : filled(config('notifications.sms.sid')) && filled(config('notifications.sms.token')));
    }

    public static function isVerified(?string $phone): bool
    {
        $local = PhoneValidator::normalize($phone);
        if (! $local) {
            return false;
        }
        $v = session(self::SESSION_KEY);
        if (is_array($v) && ($v['phone'] ?? null) === $local && ($v['until'] ?? 0) > time()) {
            return true;
        }
        // A signed-in customer whose account already carries this number was verified when it was created.
        $user = auth()->user();

        return $user && PhoneValidator::normalize($user->phone) === $local;
    }

    public function send(Request $request)
    {
        $local = PhoneValidator::normalize($request->input('phone'));
        if (! $local || PhoneValidator::looksFake($local)) {
            return response()->json(['message' => PhoneValidator::problem($request->input('phone')) ?? 'Invalid phone number.'], 422);
        }
        if (! self::required()) {
            return response()->json(['ok' => true, 'skipped' => true]);
        }
        if (self::isVerified($local)) {
            return response()->json(['ok' => true, 'verified' => true]);
        }

        // Per number and per visitor: a cap on how many texts one person (or bot) can trigger.
        foreach (['phoneotp:n:' . $local => 3, 'phoneotp:ip:' . $request->ip() => 10] as $key => $max) {
            if (RateLimiter::tooManyAttempts($key, $max)) {
                return response()->json(['message' => 'Too many code requests. Please wait a few minutes and try again.'], 429);
            }
        }
        RateLimiter::hit('phoneotp:n:' . $local, 600);
        RateLimiter::hit('phoneotp:ip:' . $request->ip(), 600);

        $code = Otp::generate($local, self::PURPOSE, 5);
        (new SmsChannel())->send(null, 'checkout_phone_otp', $local, "Your Mitavin verification code is {$code}. It expires in 5 minutes. Do not share it with anyone.");

        return response()->json(['ok' => true]);
    }

    public function verify(Request $request)
    {
        $local = PhoneValidator::normalize($request->input('phone'));
        $code = trim((string) $request->input('code'));
        if (! $local || ! preg_match('/^\d{6}$/', $code)) {
            return response()->json(['message' => 'Enter the 6-digit code.'], 422);
        }
        if (! Otp::verify($local, self::PURPOSE, $code)) {
            return response()->json(['message' => 'That code is wrong or has expired. Request a new one.'], 422);
        }
        session([self::SESSION_KEY => ['phone' => $local, 'until' => time() + self::TRUST_MINUTES * 60]]);

        return response()->json(['ok' => true, 'verified' => true]);
    }
}
