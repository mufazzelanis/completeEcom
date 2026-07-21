<?php

namespace App\Services;

class ShippingCalculator
{
    public const ZONE_DHAKA = 'dhaka';
    public const ZONE_OUTSIDE_DHAKA = 'outside_dhaka';

    public static function usesZones(): bool
    {
        return setting('shipping_method', 'zone') === 'zone';
    }

    public static function calculate(float $subtotal, ?string $zone = null): float
    {
        if (setting('free_shipping_enabled', '0') === '1' && $subtotal >= (float) setting('free_shipping_min', 999)) {
            return 0.0;
        }

        if (!self::usesZones()) {
            return (float) setting('flat_rate_amount', 60);
        }

        return $zone === self::ZONE_OUTSIDE_DHAKA
            ? (float) setting('outside_dhaka_charge', 120)
            : (float) setting('dhaka_charge', 60);
    }

    public static function label(?string $zone = null): string
    {
        if (!self::usesZones()) {
            return setting('flat_rate_label', 'Standard Delivery');
        }

        return $zone === self::ZONE_OUTSIDE_DHAKA ? 'Outside Dhaka' : 'Inside Dhaka';
    }
}
