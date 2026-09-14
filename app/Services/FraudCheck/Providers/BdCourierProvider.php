<?php

namespace App\Services\FraudCheck\Providers;

use App\Services\FraudCheck\Contracts\CourierProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * bdcourier.com's aggregator API — an OPTIONAL alternative to configuring each courier's own
 * API individually: one call, one paid/free key from bdcourier.com, checks several couriers
 * at once. Useful as a quick stopgap or a fallback for couriers you haven't set up your own
 * direct API for yet; CourierFraudCheckService gives any directly-configured provider (e.g.
 * SteadfastProvider) priority over this for the same courier, and only uses this aggregator's
 * numbers to fill in couriers nothing else reported on.
 *
 * IMPORTANT — same caveat as when this lived directly in CourierFraudCheckService: the exact
 * response shape below (parseResponse()) is written from bdcourier.com's documented format,
 * not tested against a live key. What IS verified live: hitting this exact endpoint with an
 * invalid key returned a real HTTP 401 "Invalid Bearer token" from bdcourier.com's actual
 * server, confirming the endpoint + Bearer-auth scheme are correct — only the successful-
 * response field names are still unverified. The full raw response is always stored on the
 * check row so that's a quick fix once a real key is added, not a re-run.
 */
class BdCourierProvider implements CourierProvider
{
    public function key(): string
    {
        return 'bdcourier';
    }

    public function label(): string
    {
        return 'bdcourier.com (aggregator)';
    }

    public function isEnabled(): bool
    {
        return (string) setting('courier_bdcourier_api_key', '') !== '';
    }

    public function checkPhone(string $phone): array
    {
        $apiKey = setting('courier_bdcourier_api_key', '');
        $apiUrl = setting('courier_bdcourier_api_url', 'https://bdcourier.com/api/courier-check');

        try {
            $response = Http::timeout(15)
                ->withHeaders(['Authorization' => 'Bearer ' . $apiKey])
                ->get($apiUrl, ['phone' => $phone]);

            if (! $response->successful()) {
                return [
                    'success' => false,
                    'error' => "bdcourier.com returned HTTP {$response->status()}.",
                    'couriers' => [],
                    'raw' => $response->json() ?? ['body' => $response->body()],
                ];
            }

            return $this->parseResponse($response->json());
        } catch (\Throwable $e) {
            Log::warning('bdcourier.com fraud check request failed', ['phone' => $phone, 'error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => 'Could not reach bdcourier.com: ' . $e->getMessage(),
                'couriers' => [],
                'raw' => null,
            ];
        }
    }

    /**
     * Expected shape (bdcourier.com "courier-check" response):
     *   { "status": "success", "data": {
     *       "pathao":    {"success": 4, "cancel": 1},
     *       "steadfast": {"success": 2, "cancel": 0},
     *       ...
     *   } }
     * Parsed defensively (a few key-name variants per courier entry) — see class docblock.
     */
    private function parseResponse(?array $data): array
    {
        $entries = $data['data'] ?? $data['couriers'] ?? null;

        if (! is_array($entries)) {
            return [
                'success' => false,
                'error' => 'Unrecognized response shape from bdcourier.com (no courier breakdown found).',
                'couriers' => [],
                'raw' => $data,
            ];
        }

        $couriers = [];
        foreach ($entries as $courierName => $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $delivered = (int) ($entry['success'] ?? $entry['delivered'] ?? $entry['success_parcel'] ?? 0);
            $cancelled = (int) ($entry['cancel'] ?? $entry['cancelled'] ?? $entry['cancel_parcel'] ?? 0);

            if ($delivered === 0 && $cancelled === 0) {
                continue;
            }

            $couriers[$courierName] = ['delivered' => $delivered, 'cancelled' => $cancelled];
        }

        return [
            'success' => true,
            'error' => null,
            'couriers' => $couriers,
            'raw' => $data,
        ];
    }
}
