<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Minimal RFC 4226 (HOTP) / RFC 6238 (TOTP) implementation — no external package
 * dependency, so 2FA works offline and the shared secret never leaves this server
 * (in particular: never sent to a third-party QR-code API, which would otherwise
 * leak a value capable of generating valid codes forever).
 */
class TwoFactorAuthService
{
    private const PERIOD = 30;
    private const DIGITS = 6;
    private const WINDOW = 1; // tolerate ±1 time-step of clock drift

    public function generateSecret(): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; // RFC 4648 Base32
        $secret = '';
        for ($i = 0; $i < 32; $i++) {
            $secret .= $alphabet[random_int(0, 31)];
        }

        return $secret;
    }

    public function otpAuthUri(string $secret, string $email, string $issuer): string
    {
        $label = rawurlencode($issuer . ':' . $email);
        $params = http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => self::DIGITS,
            'period' => self::PERIOD,
        ]);

        return "otpauth://totp/{$label}?{$params}";
    }

    public function verify(string $secret, string $code): bool
    {
        $code = preg_replace('/\D/', '', $code);
        if (strlen($code) !== self::DIGITS) {
            return false;
        }

        $timeStep = (int) floor(time() / self::PERIOD);
        for ($i = -self::WINDOW; $i <= self::WINDOW; $i++) {
            if (hash_equals($this->generateCode($secret, $timeStep + $i), $code)) {
                return true;
            }
        }

        return false;
    }

    private function generateCode(string $secret, int $timeStep): string
    {
        $key = $this->base32Decode($secret);
        $counter = pack('N*', 0, $timeStep); // 8-byte big-endian counter
        $hash = hash_hmac('sha1', $counter, $key, true);

        $offset = ord($hash[19]) & 0x0F;
        $binary = ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF);

        return str_pad((string) ($binary % (10 ** self::DIGITS)), self::DIGITS, '0', STR_PAD_LEFT);
    }

    private function base32Decode(string $secret): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = strtoupper(rtrim($secret, '='));
        $bits = '';
        foreach (str_split($secret) as $char) {
            $pos = strpos($alphabet, $char);
            if ($pos === false) {
                continue;
            }
            $bits .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }

        $bytes = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $bytes .= chr(bindec($byte));
            }
        }

        return $bytes;
    }

    /** @return array<int, string> */
    public function generateRecoveryCodes(int $count = 8): array
    {
        return collect(range(1, $count))
            ->map(fn () => Str::lower(Str::random(4)) . '-' . Str::lower(Str::random(4)))
            ->all();
    }
}
