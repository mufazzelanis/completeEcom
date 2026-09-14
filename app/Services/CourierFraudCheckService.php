<?php

namespace App\Services;

use App\Models\CourierFraudCheck;
use App\Services\FraudCheck\Contracts\CourierProvider;
use App\Services\FraudCheck\Providers\BdCourierProvider;
use App\Services\FraudCheck\Providers\EcourierProvider;
use App\Services\FraudCheck\Providers\PaperflyProvider;
use App\Services\FraudCheck\Providers\PathaoProvider;
use App\Services\FraudCheck\Providers\RedxProvider;
use App\Services\FraudCheck\Providers\SteadfastProvider;
use Illuminate\Support\Collection;

/**
 * Orchestrates every configured courier provider (see App\Services\FraudCheck\Providers) for
 * one phone number and merges their results into a single verdict — complements the existing
 * FraudDetectionService (which scores an ORDER's own behavior: value, account age, rapid
 * re-ordering) with the thing that matters most for COD in Bangladesh: has this customer
 * actually accepted delivery before, elsewhere?
 *
 * Each courier is its own independent provider with its own credentials (Admin > Settings >
 * Fraud Checker), so any combination can be enabled: only your own Steadfast key, only the
 * bdcourier.com aggregator, several direct couriers together, or any mix. When a courier is
 * covered by BOTH a direct provider and the bdcourier.com aggregator, the direct provider's
 * numbers win for that courier — the aggregator only fills in couriers nothing else reported.
 */
class CourierFraudCheckService
{
    /** @return CourierProvider[] */
    public function providers(): array
    {
        return [
            app(SteadfastProvider::class),
            app(PathaoProvider::class),
            app(RedxProvider::class),
            app(PaperflyProvider::class),
            app(EcourierProvider::class),
            app(BdCourierProvider::class),
        ];
    }

    public static function isEnabled(): bool
    {
        return collect((new self)->providers())->contains(fn (CourierProvider $p) => $p->isEnabled());
    }

    /**
     * Bangladeshi numbers arrive in every shape imaginable (+8801..., 8801..., 01...,
     * spaces/dashes, Bangla digits from a phone typed in Bangla keyboard mode) — every
     * provider's API expects the plain 11-digit local form.
     */
    public static function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/[^0-9]/', '', normalize_digits($phone) ?? '') ?? '';

        if (str_starts_with($digits, '880') && strlen($digits) === 13) {
            $digits = '0' . substr($digits, 3);
        } elseif (strlen($digits) === 10 && $digits[0] !== '0') {
            $digits = '0' . $digits;
        }

        return $digits;
    }

    /**
     * @param bool $forceRefresh Skip the cache and query every provider again even if this
     *                           phone was checked recently — used by the explicit "Re-check"
     *                           button; normal lookups reuse a recent cached result so
     *                           opening the same order twice doesn't burn extra API calls.
     */
    public function check(string $phone, ?int $orderId = null, bool $forceRefresh = false): CourierFraudCheck
    {
        $normalized = self::normalizePhone($phone);

        if (! $forceRefresh) {
            $cacheHours = (int) setting('courier_fraud_cache_hours', 24);
            $recent = CourierFraudCheck::where('phone', $normalized)
                ->where('success', true)
                ->where('created_at', '>=', now()->subHours(max($cacheHours, 1)))
                ->latest()
                ->first();

            if ($recent) {
                return $recent;
            }
        }

        $enabled = collect($this->providers())->filter(fn (CourierProvider $p) => $p->isEnabled());

        if ($enabled->isEmpty()) {
            return CourierFraudCheck::create([
                'phone' => $normalized,
                'order_id' => $orderId,
                'checked_by' => auth()->id(),
                'provider' => '',
                'success' => false,
                'error' => 'No courier provider is configured yet. Add at least one under Admin > Settings > Fraud Checker.',
                'total_orders' => 0,
                'total_delivered' => 0,
                'total_cancelled' => 0,
                'risk_level' => 'unknown',
            ]);
        }

        [$couriers, $rawByProvider, $errorsByProvider, $anySucceeded] = $this->queryProviders($enabled, $normalized);

        $totalDelivered = (int) collect($couriers)->sum('delivered');
        $totalCancelled = (int) collect($couriers)->sum('cancelled');
        $totalOrders = $totalDelivered + $totalCancelled;
        $successRate = $totalOrders > 0 ? round(($totalDelivered / $totalOrders) * 100, 2) : null;

        return CourierFraudCheck::create([
            'phone'           => $normalized,
            'order_id'        => $orderId,
            'checked_by'      => auth()->id(),
            'provider'        => $enabled->map(fn (CourierProvider $p) => $p->key())->implode(','),
            'success'         => $anySucceeded,
            'error'           => $anySucceeded ? null : $this->summarizeErrors($errorsByProvider),
            'total_orders'    => $totalOrders,
            'total_delivered' => $totalDelivered,
            'total_cancelled' => $totalCancelled,
            'success_rate'    => $successRate,
            'risk_level'      => $this->computeRiskLevel($totalOrders, $successRate),
            'breakdown'       => $couriers,
            'raw_response'    => ['providers' => $rawByProvider, 'errors' => $errorsByProvider],
        ]);
    }

    /**
     * Direct (single-courier) providers are queried first and their numbers take priority;
     * the bdcourier.com aggregator (if enabled) is queried last and only contributes couriers
     * nothing direct already covered, so an admin who configures both their own Steadfast key
     * AND bdcourier.com never gets Steadfast counted twice.
     */
    private function queryProviders(Collection $enabled, string $phone): array
    {
        $couriers = [];
        $rawByProvider = [];
        $errorsByProvider = [];
        $anySucceeded = false;

        $direct = $enabled->filter(fn (CourierProvider $p) => $p->key() !== 'bdcourier');
        $aggregator = $enabled->first(fn (CourierProvider $p) => $p->key() === 'bdcourier');

        foreach ($direct as $provider) {
            $result = $provider->checkPhone($phone);
            $rawByProvider[$provider->key()] = $result['raw'];

            if ($result['success']) {
                $anySucceeded = true;
                foreach ($result['couriers'] as $courierKey => $counts) {
                    $couriers[$courierKey] = $counts;
                }
            } else {
                $errorsByProvider[$provider->key()] = $result['error'];
            }
        }

        if ($aggregator) {
            $result = $aggregator->checkPhone($phone);
            $rawByProvider[$aggregator->key()] = $result['raw'];

            if ($result['success']) {
                $anySucceeded = true;
                foreach ($result['couriers'] as $courierKey => $counts) {
                    if (! array_key_exists($courierKey, $couriers)) {
                        $couriers[$courierKey] = $counts;
                    }
                }
            } else {
                $errorsByProvider[$aggregator->key()] = $result['error'];
            }
        }

        return [$couriers, $rawByProvider, $errorsByProvider, $anySucceeded];
    }

    private function summarizeErrors(array $errorsByProvider): string
    {
        if (empty($errorsByProvider)) {
            return 'All configured providers failed for an unknown reason.';
        }

        return collect($errorsByProvider)
            ->map(fn ($error, $providerKey) => "{$providerKey}: {$error}")
            ->implode(' | ');
    }

    /**
     * The last check on file for this phone, if any — for showing a cached badge (e.g. on
     * the order page) without triggering new API calls.
     */
    public function lastCheck(string $phone): ?CourierFraudCheck
    {
        return CourierFraudCheck::where('phone', self::normalizePhone($phone))->latest()->first();
    }

    public function computeRiskLevel(int $totalOrders, ?float $successRate): string
    {
        $minOrders = (int) setting('courier_fraud_min_orders', 2);

        if ($totalOrders < $minOrders || $successRate === null) {
            return 'unknown';
        }

        $highRiskBelow = (float) setting('courier_fraud_high_risk_below', 50);
        $mediumRiskBelow = (float) setting('courier_fraud_medium_risk_below', 75);

        return match (true) {
            $successRate < $highRiskBelow => 'high',
            $successRate < $mediumRiskBelow => 'medium',
            default => 'low',
        };
    }
}
