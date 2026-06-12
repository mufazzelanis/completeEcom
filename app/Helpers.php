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
