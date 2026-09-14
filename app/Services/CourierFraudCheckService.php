<?php

namespace App\Services;

use App\Models\CourierFraudCheck;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Checks a phone number's delivery history across Bangladesh's courier services —
 * complements FraudDetectionService (which scores an ORDER's own behavior: value, account
 * age, rapid re-ordering, etc.) with the thing that actually matters most for COD in
 * Bangladesh: has THIS customer historically accepted or refused delivery elsewhere?
 *
 * Backed by bdcourier.com's aggregator API, which checks one phone number against several
 * couriers (Pathao, Steadfast, RedX, Paperfly, eCourier, and others they cover) in a single
 * call, rather than needing a separate merchant account + API key per courier. Admin
 * supplies their own API key under Admin > Settings > Fraud Checker — get one at
 * https://bdcourier.com.
 *
 * IMPORTANT — the exact response shape below (parseResponse()) is written from bdcourier.com's
 * publicly documented format as of this writing, but I have not been able to test it against
 * a real API key/live response myself. The full raw response is always stored on the
 * CourierFraudCheck row specifically so that if a real check comes back with $0 for
 * everything or an obviously-wrong total, the raw JSON is right there to compare against
 * parseResponse()'s assumptions and adjust the few lines that map field names, without
 * having to re-run anything.
 */
class CourierFraudCheckService
{
    public static function isEnabled(): bool
    {
        return (string) setting('courier_fraud_api_key', '') !== '';
    }

    /**
     * Bangladeshi numbers arrive in every shape imaginable (+8801..., 8801..., 01...,
     * spaces/dashes, Bangla digits from a phone typed in Bangla keyboard mode) — couriers'
     * own systems and this API both expect the plain 11-digit local form.
     */
    public static function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/[^0-9]/', '', normalize_digits($phone) ?? '') ?? '';

        if (str_starts_with($digits, '880') && strlen($digits) === 13) {
            $digits = '0' . substr($digits, 3);
        } elseif (strlen($digits) === 10 && $digits[0] !== '0') {
            // Someone typed it without the leading 0 (1XXXXXXXXX) — restore it.
            $digits = '0' . $digits;
        }

        return $digits;
    }

    /**
     * @param bool $forceRefresh Skip the cache and hit the API again even if this phone
     *                           was checked recently — used by the explicit "Re-check"
     *                           button; normal lookups reuse a recent cached result so
     *                           opening the same order twice doesn't burn two API calls.
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

        $result = $this->callApi($normalized);

        return CourierFraudCheck::create([
            'phone'           => $normalized,
            'order_id'        => $orderId,
            'checked_by'      => auth()->id(),
            'provider'        => 'bdcourier',
            'success'         => $result['success'],
            'error'           => $result['error'] ?? null,
            'total_orders'    => $result['total_orders'] ?? 0,
            'total_delivered' => $result['total_delivered'] ?? 0,
            'total_cancelled' => $result['total_cancelled'] ?? 0,
            'success_rate'    => $result['success_rate'] ?? null,
            'risk_level'      => $result['risk_level'] ?? 'unknown',
            'breakdown'       => $result['breakdown'] ?? null,
            'raw_response'    => $result['raw'] ?? null,
        ]);
    }

    /**
     * The last check on file for this phone, if any — for showing a cached badge (e.g. on
     * the order list) without triggering a new API call.
     */
    public function lastCheck(string $phone): ?CourierFraudCheck
    {
        return CourierFraudCheck::where('phone', self::normalizePhone($phone))->latest()->first();
    }

    private function callApi(string $phone): array
    {
        $apiKey = setting('courier_fraud_api_key', '');
        $apiUrl = setting('courier_fraud_api_url', 'https://bdcourier.com/api/courier-check');

        if (! $apiKey) {
            return ['success' => false, 'error' => 'No API key configured. Add one under Admin > Settings > Fraud Checker.'];
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders(['Authorization' => 'Bearer ' . $apiKey])
                ->get($apiUrl, ['phone' => $phone]);

            if (! $response->successful()) {
                return [
                    'success' => false,
                    'error' => "Provider returned HTTP {$response->status()}.",
                    'raw' => $response->json() ?? ['body' => $response->body()],
                ];
            }

            return $this->parseResponse($response->json());
        } catch (\Throwable $e) {
            Log::warning('Courier fraud check request failed', ['phone' => $phone, 'error' => $e->getMessage()]);

            return ['success' => false, 'error' => 'Could not reach the fraud-check provider: ' . $e->getMessage()];
        }
    }

    /**
     * Expected shape (bdcourier.com "courier-check" response):
     *   { "status": "success", "data": {
     *       "pathao":    {"success": 4, "cancel": 1},
     *       "steadfast": {"success": 2, "cancel": 0},
     *       ...
     *   } }
     * Parses defensively (a few key-name variants per courier entry) precisely because this
     * hasn't been verified against a live key yet — see the class docblock.
     */
    private function parseResponse(?array $data): array
    {
        $couriers = $data['data'] ?? $data['couriers'] ?? null;

        if (! is_array($couriers)) {
            return [
                'success' => false,
                'error' => 'Unrecognized response shape from the provider (no courier breakdown found).',
                'raw' => $data,
            ];
        }

        $breakdown = [];
        $totalDelivered = 0;
        $totalCancelled = 0;

        foreach ($couriers as $courierName => $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $delivered = (int) ($entry['success'] ?? $entry['delivered'] ?? $entry['success_parcel'] ?? 0);
            $cancelled = (int) ($entry['cancel'] ?? $entry['cancelled'] ?? $entry['cancel_parcel'] ?? 0);

            if ($delivered === 0 && $cancelled === 0) {
                continue; // this courier has no record for the number — nothing to show
            }

            $breakdown[$courierName] = ['delivered' => $delivered, 'cancelled' => $cancelled];
            $totalDelivered += $delivered;
            $totalCancelled += $cancelled;
        }

        $totalOrders = $totalDelivered + $totalCancelled;
        $successRate = $totalOrders > 0 ? round(($totalDelivered / $totalOrders) * 100, 2) : null;

        return [
            'success'         => true,
            'total_orders'    => $totalOrders,
            'total_delivered' => $totalDelivered,
            'total_cancelled' => $totalCancelled,
            'success_rate'    => $successRate,
            'risk_level'      => $this->computeRiskLevel($totalOrders, $successRate),
            'breakdown'       => $breakdown,
            'raw'             => $data,
        ];
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
