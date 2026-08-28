<?php

namespace App\Jobs;

use App\Models\FacebookConversionLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Delivers one event to Meta's Conversions API (see App\Services\Facebook\ConversionsApi,
 * which builds $event and dispatches this). Queued rather than sent inline so a slow or down
 * graph.facebook.com never adds latency to the checkout/product-page/add-to-cart request the
 * event rode in on — the customer's response doesn't wait on Meta at all.
 */
class SendFacebookConversionEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 15;

    public function __construct(
        private readonly string $graphVersion,
        private readonly string $pixelId,
        private readonly string $accessToken,
        private readonly ?string $testEventCode,
        private readonly array $event,
    ) {
    }

    /** Exponential-ish backoff — a transient Graph API hiccup shouldn't burn all 3 tries in the same second. */
    public function backoff(): array
    {
        return [10, 60];
    }

    public function handle(): void
    {
        $payload = [
            'data' => [$this->event],
            'access_token' => $this->accessToken,
        ];
        if ($this->testEventCode) {
            $payload['test_event_code'] = $this->testEventCode;
        }

        $response = Http::timeout(10)
            ->post("https://graph.facebook.com/{$this->graphVersion}/{$this->pixelId}/events", $payload);

        FacebookConversionLog::create([
            'event_name'  => $this->event['event_name'],
            'event_id'    => $this->event['event_id'],
            'pixel_id'    => $this->pixelId,
            'status'      => $response->successful() ? 'sent' : 'failed',
            'http_status' => $response->status(),
            'response'    => Str::limit($response->body(), 2000, ''),
        ]);

        if ($response->failed()) {
            // Meta's error payload (invalid/expired token, disabled pixel, etc.) is already
            // captured in the log row above — this is just so a persistently failing setup
            // also surfaces in the app's normal error log without needing to check that table.
            Log::warning('Facebook Conversions API event failed', [
                'event_name' => $this->event['event_name'],
                'status'     => $response->status(),
                'body'       => $response->body(),
            ]);
            // Throwing (rather than returning) is what lets ShouldQueue's tries/backoff retry
            // this — worth doing for a transient failure (5xx, connection timeout/status 0),
            // but a 4xx from Meta (bad token, malformed payload) would just fail identically on
            // every retry, so return normally instead and let the logged row above be the
            // record — no point burning the remaining attempts on a guaranteed repeat failure.
            if ($response->serverError() || $response->status() === 0) {
                throw new \RuntimeException('Facebook CAPI request failed: HTTP ' . $response->status());
            }
        }
    }
}
