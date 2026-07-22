<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

if (!function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        return Setting::get($key, $default);
    }
}

if (!function_exists('setting_file_url')) {
    function setting_file_url(string $key, ?string $default = null): ?string
    {
        return Setting::fileUrl($key, $default);
    }
}

if (!function_exists('normalize_digits')) {
    /**
     * Convert Bangla numerals (০-৯) to English digits (0-9), so a customer typing
     * a phone number in Bangla script still resolves to a usable phone value.
     */
    function normalize_digits(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        return strtr($value, [
            '০' => '0', '১' => '1', '২' => '2', '৩' => '3', '৪' => '4',
            '৫' => '5', '৬' => '6', '৭' => '7', '৮' => '8', '৯' => '9',
        ]);
    }
}

if (!function_exists('format_currency')) {
    function format_currency(float|int $amount): string
    {
        $symbol    = setting('currency_symbol', '৳');
        $position  = setting('currency_position', 'left');
        $decimals  = (int) setting('decimal_places', 0);
        $thousands = setting('thousand_separator', ',');
        $decimal   = setting('decimal_separator', '.');
        $formatted = number_format((float) $amount, $decimals, $decimal, $thousands);
        return $position === 'right' ? $formatted . $symbol : $symbol . $formatted;
    }
}

if (!function_exists('hex_to_hsl')) {
    function hex_to_hsl(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            $hex = 'ea580c';
        }

        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;

        if ($max === $min) {
            $h = $s = 0.0;
        } else {
            $d = $max - $min;
            $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
            $h = match ($max) {
                $r => ($g - $b) / $d + ($g < $b ? 6 : 0),
                $g => ($b - $r) / $d + 2,
                $b => ($r - $g) / $d + 4,
                default => 0,
            };
            $h /= 6;
        }

        return [$h * 360, $s * 100, $l * 100];
    }
}

if (!function_exists('hsl_to_hex')) {
    function hsl_to_hex(float $h, float $s, float $l): string
    {
        $h = fmod($h, 360) / 360;
        $s = max(0, min(100, $s)) / 100;
        $l = max(0, min(100, $l)) / 100;

        if ($s === 0.0) {
            $r = $g = $b = $l;
        } else {
            $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
            $p = 2 * $l - $q;
            $hue2rgb = function ($p, $q, $t) {
                if ($t < 0) $t += 1;
                if ($t > 1) $t -= 1;
                if ($t < 1 / 6) return $p + ($q - $p) * 6 * $t;
                if ($t < 1 / 2) return $q;
                if ($t < 2 / 3) return $p + ($q - $p) * (2 / 3 - $t) * 6;
                return $p;
            };
            $r = $hue2rgb($p, $q, $h + 1 / 3);
            $g = $hue2rgb($p, $q, $h);
            $b = $hue2rgb($p, $q, $h - 1 / 3);
        }

        $toHex = fn ($c) => str_pad(dechex((int) round($c * 255)), 2, '0', STR_PAD_LEFT);
        return '#' . $toHex($r) . $toHex($g) . $toHex($b);
    }
}

if (!function_exists('wrap_branded_email')) {
    /**
     * Wrap a raw notification/campaign email body with the admin-configured Email Logo
     * (falling back to the Main Logo, then the site name as text) plus a simple footer,
     * so outgoing emails aren't just an unbranded snippet of HTML.
     */
    function wrap_branded_email(string $bodyHtml, ?string $footerExtra = null): string
    {
        $siteName = setting('site_name', 'ShopVista');
        $logoUrl  = setting_file_url('email_logo', setting_file_url('site_logo'));

        $logoHtml = $logoUrl
            ? '<img src="' . e($logoUrl) . '" alt="' . e($siteName) . '" style="max-height:48px;">'
            : '<span style="font-size:20px;font-weight:bold;color:#1f2937;">' . e($siteName) . '</span>';

        return '<div style="max-width:600px;margin:0 auto;font-family:sans-serif;">'
            . '<div style="padding:24px 0;text-align:center;border-bottom:1px solid #e5e7eb;">' . $logoHtml . '</div>'
            . '<div style="padding:24px 0;">' . $bodyHtml . '</div>'
            . '<div style="padding:16px 0;text-align:center;font-size:12px;color:#9ca3af;border-top:1px solid #e5e7eb;">'
            . '&copy; ' . date('Y') . ' ' . e($siteName) . '. All rights reserved.'
            . ($footerExtra ? '<br>' . $footerExtra : '')
            . '</div>'
            . '</div>';
    }
}

if (!function_exists('brand_color_shades')) {
    /**
     * Generate a Tailwind-style 50–900 shade ramp from a single brand color,
     * so one admin-picked hex can re-theme every "orange-500", "orange-600", etc.
     * utility class already hardcoded throughout the storefront.
     */
    function brand_color_shades(string $hex): array
    {
        [$h, $s] = hex_to_hsl($hex);

        $lightnessCurve = [
            '50' => 97, '100' => 93, '200' => 85, '300' => 74,
            '400' => 62, '500' => 53, '600' => 45, '700' => 38,
            '800' => 31, '900' => 24,
        ];

        $shades = [];
        foreach ($lightnessCurve as $step => $lightness) {
            $stepSaturation = $lightness > 90 ? max($s * 0.7, 20) : $s;
            $shades[$step] = hsl_to_hex($h, $stepSaturation, $lightness);
        }

        return $shades;
    }
}
