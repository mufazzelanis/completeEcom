<?php

namespace App\Services\FraudCheck\Providers;

use App\Services\FraudCheck\Contracts\CourierProvider;

/**
 * Shared base for couriers whose delivery-history-by-phone API I do NOT have confident,
 * documented knowledge of — unlike Steadfast (SteadfastProvider) or bdcourier.com's public
 * aggregator (BdCourierProvider), which are real, wired integrations. Guessing an endpoint
 * or response shape here risks silently returning wrong fraud data, which is worse than
 * returning nothing — so checkPhone() always fails honestly instead.
 *
 * The credential field is still collected in Settings (so it's ready to go), and isEnabled()
 * still reports true once filled in, but checkPhone() never claims success. To finish one of
 * these for real: get that courier's official delivery-history/fraud-check API documentation
 * (or a working example request+response from your own merchant account) and the HTTP call
 * can be dropped in here in a few lines, the same shape as SteadfastProvider.
 */
abstract class UnverifiedProvider implements CourierProvider
{
    abstract public function key(): string;

    abstract public function label(): string;

    public function isEnabled(): bool
    {
        return (string) setting("courier_{$this->key()}_api_key", '') !== '';
    }

    public function checkPhone(string $phone): array
    {
        return [
            'success' => false,
            'error' => "{$this->label()}'s integration isn't finalized yet — its credentials are saved, but no verified API endpoint is wired in until real documentation (or a working example from your merchant account) is confirmed.",
            'couriers' => [],
            'raw' => null,
        ];
    }
}
