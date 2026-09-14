<?php

namespace App\Services\FraudCheck\Providers;

use App\Services\FraudCheck\Contracts\CourierProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Steadfast Courier's own merchant API (platform: portal.packzy.com) — a direct integration
 * with Steadfast itself, not through any third-party aggregator. Get an Api Key + Secret Key
 * from Steadfast's merchant panel (Settings → API Support / "Access API") once your merchant
 * account is approved.
 *
 * Of every courier in this system, this is the one I'm confident enough about to ship fully
 * wired rather than as a placeholder — GET /api/v1/fraud_check/{phone} with Api-Key/Secret-Key
 * headers, returning success_parcel/cancelled_parcel counts, is a long-standing, widely used,
 * independently-documented Steadfast endpoint. Still: I don't have a live key myself, so this
 * hasn't been tested against a real response from inside this app — the raw response is
 * always stored on the row specifically so the first real check can be sanity-checked against
 * the two lines below that read success_parcel/cancelled_parcel, and corrected in one place if
 * Steadfast's actual field names differ even slightly.
 */
class SteadfastProvider implements CourierProvider
{
    public function key(): string
    {
        return 'steadfast';
    }

    public function label(): string
    {
        return 'Steadfast Courier';
    }

    public function isEnabled(): bool
    {
        return (string) setting('courier_steadfast_api_key', '') !== ''
            && (string) setting('courier_steadfast_secret_key', '') !== '';
    }

    public function checkPhone(string $phone): array
    {
        $apiKey = setting('courier_steadfast_api_key', '');
        $secretKey = setting('courier_steadfast_secret_key', '');

        try {
            $response = Http::timeout(15)
                ->withHeaders(['Api-Key' => $apiKey, 'Secret-Key' => $secretKey])
                ->get("https://portal.packzy.com/api/v1/fraud_check/{$phone}");

            if (! $response->successful()) {
                return [
                    'success' => false,
                    'error' => "Steadfast returned HTTP {$response->status()}.",
                    'couriers' => [],
                    'raw' => $response->json() ?? ['body' => $response->body()],
                ];
            }

            $data = $response->json();
            $delivered = (int) ($data['success_parcel'] ?? $data['delivered'] ?? 0);
            $cancelled = (int) ($data['cancelled_parcel'] ?? $data['cancelled'] ?? 0);

            return [
                'success' => true,
                'error' => null,
                'couriers' => ['steadfast' => ['delivered' => $delivered, 'cancelled' => $cancelled]],
                'raw' => $data,
            ];
        } catch (\Throwable $e) {
            Log::warning('Steadfast fraud check request failed', ['phone' => $phone, 'error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => 'Could not reach Steadfast: ' . $e->getMessage(),
                'couriers' => [],
                'raw' => null,
            ];
        }
    }
}
