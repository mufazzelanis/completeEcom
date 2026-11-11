<?php

namespace App\Services;

class ShippingCalculator
{
    public const ZONE_DHAKA = 'dhaka';
    public const ZONE_OUTSIDE_DHAKA = 'outside_dhaka';

    /** Settings → Shipping → Default Delivery Area: what checkout, cart and landing pages start on. */
    public const DEFAULT_ASK = 'ask';

    public static function usesZones(): bool
    {
        return setting('shipping_method', 'zone') === 'zone';
    }

    /**
     * The delivery area a new order starts on: 'dhaka', 'outside_dhaka', or null when the shop
     * wants every customer to pick one themselves (nothing preselected, so no one is charged a
     * rate they didn't choose). Outside Dhaka unless set otherwise — the store's behaviour before
     * the setting existed.
     */
    public static function defaultZone(): ?string
    {
        $zone = (string) setting('shipping_default_zone', self::ZONE_OUTSIDE_DHAKA);

        return match ($zone) {
            self::ZONE_DHAKA => self::ZONE_DHAKA,
            self::DEFAULT_ASK => null,
            default => self::ZONE_OUTSIDE_DHAKA,
        };
    }

    /** For a figure shown before any area is known (cart estimate): the default, else Outside Dhaka (never under-quotes). */
    public static function estimateZone(): string
    {
        return self::defaultZone() ?? self::ZONE_OUTSIDE_DHAKA;
    }

    /** The other area, for "(Inside Dhaka ৳60)" hints next to an estimate. */
    public static function otherZone(string $zone): string
    {
        return $zone === self::ZONE_DHAKA ? self::ZONE_OUTSIDE_DHAKA : self::ZONE_DHAKA;
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
