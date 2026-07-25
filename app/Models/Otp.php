<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class Otp extends Model
{
    protected $fillable = [
        'identifier', 'purpose', 'otp_code', 'otp_code_plain', 'attempts', 'expires_at', 'verified_at', 'ip_address',
    ];

    protected $casts = [
        'expires_at'  => 'datetime',
        'verified_at' => 'datetime',
    ];

    private const MAX_ATTEMPTS = 5;

    /**
     * Create a new OTP for identifier+purpose, invalidating any earlier unverified
     * one for the same pair first (so only the most recently sent code ever works).
     * Returns the plain code — this is the only moment it exists unhashed; the
     * caller is responsible for sending it (SMS/email) and must not persist it.
     */
    public static function generate(string $identifier, string $purpose = 'verification', int $ttlMinutes = 5): string
    {
        static::where('identifier', $identifier)->where('purpose', $purpose)->delete();

        $code = (string) random_int(100000, 999999);

        static::create([
            'identifier'     => $identifier,
            'purpose'        => $purpose,
            'otp_code'       => Hash::make($code),
            // Only stored outside production — there's no real SMS gateway wired up
            // in dev, so this lets you read the code straight from the DB to log in
            // while testing. Never populated on a live site (this column stays null),
            // so a production DB leak still can't hand out a working code.
            'otp_code_plain' => app()->environment('production') ? null : $code,
            'expires_at'     => now()->addMinutes($ttlMinutes),
            'ip_address'     => request()->ip(),
        ]);

        return $code;
    }

    /**
     * Verify a submitted code. Returns false (without revealing why) for: no
     * matching record, expired, already used, too many failed attempts, or a
     * wrong code — each wrong attempt is counted so this can't be brute-forced.
     */
    public static function verify(string $identifier, string $purpose, string $code): bool
    {
        $otp = static::where('identifier', $identifier)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->latest()
            ->first();

        if (! $otp || $otp->expires_at->isPast() || $otp->attempts >= self::MAX_ATTEMPTS) {
            return false;
        }

        if (! Hash::check($code, $otp->otp_code)) {
            $otp->increment('attempts');
            return false;
        }

        $otp->update(['verified_at' => now()]);
        return true;
    }
}
