<?php

namespace App\Services\FraudCheck\Providers;

use App\Services\FraudCheck\Contracts\CourierProvider;

/**
 * Confirmed from Pathao Courier's own official Merchant API Integration Documentation
 * (read directly, not guessed): their public merchant API is entirely built around CREATING
 * and TRACKING your own shipments — issue-token (OAuth password grant), stores, orders,
 * orders/bulk, order info by consignment_id, city/zone/area lists, price-plan. There is no
 * endpoint anywhere in it that looks up a phone number's delivery history across Pathao's
 * whole platform the way Steadfast's fraud_check/{phone} does — "Get Order Short Info" only
 * returns status for a consignment_id from an order YOU already created through Pathao, not
 * a phone-based lookup of a customer's history with OTHER merchants.
 *
 * So this genuinely isn't available from Pathao's API today, full stop — not a credentials or
 * documentation gap on this end. isEnabled() stays false (no credentials field to fill in) so
 * it never appears configurable, and this exists mainly so it's not silently missing from the
 * provider list if this needs revisiting later (e.g. if Pathao ever adds such an endpoint, or
 * if a different kind of check — syncing YOUR OWN sent orders' delivery status via Get Order
 * Short Info, which the docs do support — is wanted instead of a phone-based fraud score).
 */
class PathaoProvider implements CourierProvider
{
    public function key(): string
    {
        return 'pathao';
    }

    public function label(): string
    {
        return 'Pathao Courier';
    }

    public function isEnabled(): bool
    {
        return false;
    }

    public function checkPhone(string $phone): array
    {
        return [
            'success' => false,
            'error' => "Pathao's official merchant API has no phone-based delivery-history lookup — confirmed from their own API docs, not a guess. It only supports creating/tracking orders you've sent yourself (by consignment ID), not checking a customer's history across the whole platform.",
            'couriers' => [],
            'raw' => null,
        ];
    }
}
