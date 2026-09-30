<?php

namespace App\Services;

use App\Models\Otp;
use App\Models\User;
use App\Support\OtpMailer;
use Illuminate\Support\Facades\RateLimiter;

/**
 * The "enable 2FA on my own account" flow — shared by the admin, customer account,
 * and seller panels so the send/dedupe/throttle logic (see Otp::activeFor()) only
 * lives in one place. Each caller supplies its own purpose string so codes (and
 * rate-limit keys) never collide across the three areas even for the same email.
 */
class TwoFactorEnrollmentService
{
    public function __construct(private TwoFactorAuthService $totp)
    {
    }

    /**
     * Sends a fresh code only when one isn't already active for this purpose, or
     * when explicitly asked to via $resend — throttled to one per 30 seconds either
     * way, so a plain page reload never re-sends and rapid resend clicks can't spam.
     */
    public function issueCodeIfNeeded(User $user, string $purpose, bool $resend, string $subject, string $intro, string $logContext): void
    {
        $throttleKey = "otp-resend-{$purpose}:{$user->email}";
        $needsFreshCode = ! Otp::activeFor($user->email, $purpose);
        $wantsResend = $resend && ! RateLimiter::tooManyAttempts($throttleKey, 1);

        if ($needsFreshCode || $wantsResend) {
            $code = Otp::generate($user->email, $purpose);
            OtpMailer::send($user->email, $code, $subject, $intro, $logContext);
            RateLimiter::hit($throttleKey, 30);
        }
    }

    /** @return array<int, string>|null recovery codes on success, null on a bad/expired code */
    public function confirm(User $user, string $purpose, string $code): ?array
    {
        if (! Otp::verify($user->email, $purpose, trim($code))) {
            return null;
        }

        $recoveryCodes = $this->totp->generateRecoveryCodes();
        $user->two_factor_recovery_codes = $recoveryCodes;
        $user->two_factor_confirmed_at = now();
        $user->save();

        return $recoveryCodes;
    }

    public function disable(User $user): void
    {
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;
        $user->save();
    }

    /** @return array<int, string> */
    public function regenerateRecoveryCodes(User $user): array
    {
        $codes = $this->totp->generateRecoveryCodes();
        $user->two_factor_recovery_codes = $codes;
        $user->save();

        return $codes;
    }
}
