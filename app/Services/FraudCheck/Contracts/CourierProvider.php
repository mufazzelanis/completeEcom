<?php

namespace App\Services\FraudCheck\Contracts;

/**
 * One courier's own delivery-history API — or, for BdCourierProvider, an aggregator that
 * reports on several couriers in a single call. Each provider manages its own credentials
 * (read from Settings) independently, so any subset can be enabled at once: run only your
 * own Steadfast key, only the bdcourier.com aggregator, both, or any other combination.
 */
interface CourierProvider
{
    /** Stable identifier, e.g. 'steadfast', 'pathao', 'bdcourier'. Used as the settings-key
     *  prefix and as the courier key in a check's stored breakdown. */
    public function key(): string;

    /** Human label for the admin UI, e.g. "Steadfast Courier". */
    public function label(): string;

    /** Whether the admin has actually filled in this provider's credentials. */
    public function isEnabled(): bool;

    /**
     * @return array{
     *     success: bool,
     *     error: string|null,
     *     couriers: array<string, array{delivered: int, cancelled: int}>,
     *     raw: mixed,
     * }
     *     'couriers' is keyed by courier identifier — normally just [$this->key() => ...],
     *     except an aggregator (BdCourierProvider) which can report several couriers at once.
     *     'raw' is stored verbatim on the CourierFraudCheck row so a wrong field-name
     *     assumption can be diagnosed from real data without re-running anything.
     */
    public function checkPhone(string $phone): array;
}
