<?php

namespace App\Support;

/**
 * Bangladesh mobile-number check used at checkout.
 *
 * What it can prove without sending anything: the number is a real-looking BD mobile
 * (operator prefix 013-019, 11 digits) and not an obvious keyboard-mash fake such as
 * 01711111111 or 01712345678. It cannot prove the SIM is active — only an SMS code
 * (see CheckoutPhoneController) can do that.
 */
class PhoneValidator
{
    /** Returns the 11-digit local form (01XXXXXXXXX), or null if it isn't a BD mobile number. */
    public static function normalize(?string $raw): ?string
    {
        $d = preg_replace('/\D+/', '', normalize_digits((string) $raw) ?? '');
        if (str_starts_with($d, '00880')) {
            $d = substr($d, 2);
        }
        if (str_starts_with($d, '880') && strlen($d) === 13) {
            $d = '0' . substr($d, 3);
        }

        return preg_match('/^01[3-9]\d{8}$/', $d) ? $d : null;
    }

    /** Human-readable reason the number is rejected, or null when it is acceptable. */
    public static function problem(?string $raw): ?string
    {
        $local = self::normalize($raw);
        if ($local === null) {
            return 'Please enter a valid Bangladeshi mobile number (e.g. 01712345678).';
        }
        if (self::looksFake($local)) {
            return 'This phone number looks invalid. Please enter your real, working mobile number so we can confirm your order.';
        }

        return null;
    }

    /** Keyboard-mash patterns: long runs of one digit, straight ascending/descending runs, or a 1-2 digit loop. */
    public static function looksFake(string $local): bool
    {
        $tail = substr($local, 3); // the 8 subscriber digits after the 01X operator prefix

        if (preg_match('/(\d)\1{5,}/', $tail)) {
            return true;
        }
        if (strlen(count_chars($tail, 3)) <= 2) {          // e.g. 12121212, 00000000
            return true;
        }
        for ($i = 0; $i <= strlen($tail) - 6; $i++) {       // 6 digits in a row going +1 or -1
            $run = substr($tail, $i, 6);
            $up = $down = true;
            for ($j = 1; $j < 6; $j++) {
                $up = $up && ((int) $run[$j] - (int) $run[$j - 1]) === 1;
                $down = $down && ((int) $run[$j] - (int) $run[$j - 1]) === -1;
            }
            if ($up || $down) {
                return true;
            }
        }

        return false;
    }

    /** off | format | otp — set in Settings → Orders. Defaults to format so fake numbers are blocked out of the box. */
    public static function mode(): string
    {
        $mode = (string) setting('phone_check', 'format');

        return in_array($mode, ['off', 'format', 'otp'], true) ? $mode : 'format';
    }
}
