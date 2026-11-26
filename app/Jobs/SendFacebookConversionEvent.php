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
        private readonly ?int $landingPageId = null,
    ) {
    }

    /** Exponential-ish backoff — a transient Graph API hiccup shouldn't burn all 3 tries in the same second. */
    public function backoff(): array
    {
        return [10, 60];
    }

    public function handle(): void
    {
        // a guest's city / country from their IP, looked up here off the visitor's request (see ConversionsApi::addIpLocation)
        $event = $this->event;
        // no city yet and the IP not looked up: try once more in ~75 s, after the every-minute
        // IP resolver has had its turn (Meta takes events up to 7 days old)
        if ($this->job && $this->attempts() < 2 && isset($event['user_data'])
            && \App\Services\Facebook\ConversionsApi::waitingForIpLocation((array) $event['user_data'])) {
            $this->release(75);

            return;
        }
        if (isset($event['user_data'])) {
            $event['user_data'] = \App\Services\Facebook\ConversionsApi::addIpLocation((array) $event['user_data']);
        }

        $payload = [
            'data' => [$event],
            'access_token' => $this->accessToken,
        ];
        if ($this->testEventCode) {
            $payload['test_event_code'] = $this->testEventCode;
        }

        $response = Http::timeout(10)
            ->post("https://graph.facebook.com/{$this->graphVersion}/{$this->pixelId}/events", $payload);

        // what the event was about — shown (and linked) in Admin → Live & Traffic
        $ids = array_values((array) ($event['custom_data']['content_ids'] ?? []));
        $productId = isset($ids[0]) && ctype_digit((string) $ids[0]) ? (int) $ids[0] : null; // a landing page without a product sends its slug
        $value = $event['custom_data']['value'] ?? null;

        FacebookConversionLog::create([
            'event_name'      => $event['event_name'],
            'event_id'        => $event['event_id'],
            'pixel_id'        => $this->pixelId,
            'landing_page_id' => $this->landingPageId,
            'product_id'      => $productId,
            'items'           => $ids ? min(count($ids), 65535) : null,
            'value'           => is_numeric($value) ? (float) $value : null,
            'source_url'      => isset($event['event_source_url']) ? Str::limit((string) $event['event_source_url'], 500, '') : null,
            // the visitor behind it (already in user_data for Meta's matching) — the Visitor column
            'ip_address'      => isset($event['user_data']['client_ip_address']) ? Str::limit((string) $event['user_data']['client_ip_address'], 45, '') : null,
            'user_agent'      => isset($event['user_data']['client_user_agent']) ? Str::limit((string) $event['user_data']['client_user_agent'], 255, '') : null,
            // which matching keys went with it (names only, never the hashed values)
            'match_keys'      => FacebookConversionLog::matchKeysFrom((array) ($event['user_data'] ?? [])),
            'status'          => $response->successful() ? 'sent' : 'failed',
            'http_status'     => $response->status(),
            'response'        => Str::limit($response->body(), 2000, ''),
        ]);

        if ($response->failed()) {
            // Meta's error payload (invalid/expired token, disabled pixel, etc.) is already
            // captured in the log row above — this is just so a persistently failing setup
            // also surfaces in the app's normal error log without needing to check that table.
            Log::warning('Facebook Conversions API event failed', [
                'event_name' => $event['event_name'],
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
