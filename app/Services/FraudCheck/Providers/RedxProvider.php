<?php

namespace App\Services\FraudCheck\Providers;

use App\Services\FraudCheck\Contracts\CourierProvider;

/**
 * Confirmed from RedX's own official OpenAPI documentation (read directly, not guessed):
 * Track Parcel (/parcel/track/<tracking_id>), Get Parcel Details (/parcel/info/<tracking_id>),
 * Create/Update Parcel, Areas, Pickup Stores, Charge Calculator, and a status-change Webhook.
 * Every one of these is keyed by a tracking_id from a parcel the merchant already created
 * through RedX — there is no endpoint that looks up a phone number's delivery history across
 * RedX's platform the way Steadfast's fraud_check/{phone} does. Same situation as Pathao:
 * this is a real limitation of RedX's public API, not a documentation gap on this end.
 *
 * What RedX's docs DO support, if wanted later as a separate (different) feature: their
 * webhook pushes real-time status updates (delivered/returned/agent-hold/etc.) for parcels
 * this merchant sends, which could build up an accurate LOCAL history per customer phone —
 * but that only covers orders sent through this store's own RedX account, not a cross-
 * merchant fraud signal the way Steadfast's or bdcourier.com's aggregate numbers are.
 */
class RedxProvider implements CourierProvider
{
    public function key(): string
    {
        return 'redx';
    }

    public function label(): string
    {
        return 'RedX';
    }

    public function isEnabled(): bool
    {
        return false;
    }

    public function checkPhone(string $phone): array
    {
        return [
            'success' => false,
            'error' => "RedX's official API has no phone-based delivery-history lookup — confirmed from their own API docs, not a guess. Track Parcel / Get Parcel Details only work for a tracking_id from a parcel you've already sent, not a customer's history across the platform.",
            'couriers' => [],
            'raw' => null,
        ];
    }
}
