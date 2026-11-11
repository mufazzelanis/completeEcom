<?php

namespace App\Support;

/**
 * Is a typed delivery address something a courier could find? Landing page orders come from ads
 * and are placed in seconds, so "Dhaka", "ok", a phone number or "aaaaaaaaaa" turned up as
 * addresses. Mirrored in the order forms' own check (landing/show, landing/themes/_order) so the
 * customer is told before sending.
 */
class AddressCheck
{
    public const MESSAGE = 'পূর্ণ ঠিকানা লিখুন — বাসা/রোড, এলাকা, থানা ও জেলা (যেমন: বাসা ১২, রোড ৫, ধানমন্ডি, ঢাকা)।';

    public static function looksReal(string $address, string $phone = ''): bool
    {
        $address = trim(preg_replace('/\s+/u', ' ', $address));
        if (mb_strlen($address) < 10) {
            return false;
        }
        // at least two parts: "Dhanmondi Dhaka", "Road 5, Mirpur" — not one word
        $parts = preg_split('/[\s,।\-\/]+/u', $address, -1, PREG_SPLIT_NO_EMPTY);
        if (count($parts) < 2) {
            return false;
        }
        // words, not just digits or symbols
        if (preg_match_all('/\p{L}/u', $address) < 4) {
            return false;
        }
        // not the phone number again
        $digits = preg_replace('/\D+/', '', normalize_digits($address) ?? '');
        $phoneDigits = preg_replace('/\D+/', '', normalize_digits($phone) ?? '');
        if ($phoneDigits !== '' && strlen($digits) >= 10 && str_contains($digits, substr($phoneDigits, -10)) && preg_match_all('/\p{L}/u', $address) < 8) {
            return false;
        }
        // keyboard mash: one character over and over ("aaaaaaaaaa", "..........")
        $letters = preg_replace('/[^\p{L}]/u', '', mb_strtolower($address));
        if ($letters !== '' && count(array_unique(mb_str_split($letters))) <= 2) {
            return false;
        }

        return true;
    }
}
