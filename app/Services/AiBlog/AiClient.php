<?php

namespace App\Services\AiBlog;

use App\Models\AiBlogRun;
use App\Models\AiBlogTopic;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Asks Claude or ChatGPT for a JSON answer that matches a schema, and logs what it cost.
 *
 * Plain HTTP (Laravel's client) instead of the vendors' PHP SDKs on purpose: deploys here are
 * `git pull` without `composer install`, so a new Composer package would break the live site.
 * Both APIs are called exactly as their docs describe, and tests fake them with Http::fake().
 */
class AiClient
{
    public function __construct(
        public readonly string $provider,
        public readonly string $model,
        private readonly string $key,
    ) {
    }

    /** Client for the configured provider: the writing model, or the cheap one ($light). */
    public static function make(bool $light = false): self
    {
        $key = Settings::apiKey();
        if ($key === '') {
            throw new AiBlogException('No API key saved. Add one in AI Blog → Settings.');
        }

        return new self(Settings::provider(), $light ? Settings::lightModel() : Settings::model(), $key);
    }

    /**
     * @return array the decoded JSON object
     * @throws AiBlogException
     */
    public function json(string $system, string $prompt, array $schema, int $maxTokens, string $purpose, ?AiBlogTopic $topic = null, string $effort = 'medium'): array
    {
        $started = microtime(true);
        $run = ['ai_blog_topic_id' => $topic?->id, 'purpose' => $purpose, 'provider' => $this->provider, 'model' => $this->model];

        try {
            [$text, $in, $out, $model] = $this->provider === 'openai'
                ? $this->openai($system, $prompt, $schema, $maxTokens)
                : $this->anthropic($system, $prompt, $schema, $maxTokens, $effort);

            [$pIn, $pOut] = Settings::prices($this->provider, $model);
            AiBlogRun::create($run + [
                'model' => $model, 'input_tokens' => $in, 'output_tokens' => $out,
                'cost_usd' => round(($in * $pIn + $out * $pOut) / 1_000_000, 5),
                'duration_ms' => (int) ((microtime(true) - $started) * 1000),
            ]);

            $data = json_decode($text, true);
            if (!is_array($data)) {
                throw new AiBlogException('The AI answer was not valid JSON.');
            }

            return $data;
        } catch (AiBlogException $e) {
            if (!AiBlogRun::where($run)->where('created_at', '>=', now()->subSeconds(2))->exists()) {
                AiBlogRun::create($run + ['ok' => false, 'error' => $e->getMessage(), 'duration_ms' => (int) ((microtime(true) - $started) * 1000)]);
            }
            throw $e;
        }
    }

    /** Claude Messages API with structured output (output_config.format). */
    private function anthropic(string $system, string $prompt, array $schema, int $maxTokens, string $effort): array
    {
        $body = [
            'model'         => $this->model,
            'max_tokens'    => $maxTokens,
            'system'        => $system,
            'messages'      => [['role' => 'user', 'content' => $prompt]],
            'output_config' => ['effort' => $effort, 'format' => ['type' => 'json_schema', 'schema' => $schema]],
        ];
        $headers = ['x-api-key' => $this->key, 'anthropic-version' => '2026-10-21'];
        // Opus/Sonnet: if a safety check declines, the API retries on a fallback model in the same call
        if ($this->model !== 'claude-haiku-5-5') {
            $body['fallbacks'] = 'default';
            $headers['anthropic-beta'] = 'server-side-fallback-2026-07-01';
        }

        $res = $this->send('https://api.anthropic.com/v1/messages', $headers, $body);
        $stop = $res['stop_reason'] ?? null;
        if ($stop === 'refusal') {
            throw new AiBlogException('The AI declined to write this topic. Try rewording it.');
        }
        if ($stop === 'max_tokens') {
            throw new AiBlogException('The article was cut off (too long). Lower "Article length" in Settings.');
        }
        $text = collect($res['content'] ?? [])->where('type', 'text')->pluck('text')->implode('');

        return [$text, (int) ($res['usage']['input_tokens'] ?? 0) + (int) ($res['usage']['cache_read_input_tokens'] ?? 0) + (int) ($res['usage']['cache_creation_input_tokens'] ?? 0),
            (int) ($res['usage']['output_tokens'] ?? 0), (string) ($res['model'] ?? $this->model)];
    }

    /** OpenAI Chat Completions with a strict JSON schema response format. */
    private function openai(string $system, string $prompt, array $schema, int $maxTokens): array
    {
        $res = $this->send('https://api.openai.com/v1/chat/completions', ['Authorization' => 'Bearer ' . $this->key], [
            'model'    => $this->model,
            'messages' => [['role' => 'system', 'content' => $system], ['role' => 'user', 'content' => $prompt]],
            'max_completion_tokens' => $maxTokens,
            'response_format' => ['type' => 'json_schema', 'json_schema' => ['name' => 'answer', 'strict' => true, 'schema' => $schema]],
        ]);
        $choice = $res['choices'][0] ?? [];
        if (!empty($choice['message']['refusal'])) {
            throw new AiBlogException('The AI declined to write this topic. Try rewording it.');
        }
        if (($choice['finish_reason'] ?? null) === 'length') {
            throw new AiBlogException('The article was cut off (too long). Lower "Article length" in Settings.');
        }

        return [(string) ($choice['message']['content'] ?? ''), (int) ($res['usage']['prompt_tokens'] ?? 0),
            (int) ($res['usage']['completion_tokens'] ?? 0), (string) ($res['model'] ?? $this->model)];
    }

    private function send(string $url, array $headers, array $body): array
    {
        try {
            $response = Http::withHeaders($headers)->acceptJson()->asJson()
                ->connectTimeout(20)->timeout(600)
                // overloaded / rate limited / network blip → try again a little later
                ->retry(3, 8000, fn ($e) => $e instanceof ConnectionException
                    || in_array(optional($e->response ?? null)->status(), [408, 409, 429, 500, 502, 503, 504, 529], true), throw: false)
                ->post($url, $body);
        } catch (ConnectionException $e) {
            throw new AiBlogException('Could not reach the AI service: ' . $e->getMessage());
        }

        if ($response->failed()) {
            $msg = $response->json('error.message') ?? $response->json('error') ?? $response->body();
            $msg = is_string($msg) ? $msg : json_encode($msg);
            throw new AiBlogException(match ($response->status()) {
                401, 403 => 'The API key was rejected — check it in AI Blog → Settings.',
                402      => 'The AI account has no credit left — add credit on the provider\'s billing page.',
                429      => 'The AI service is busy or the account hit its rate/spend limit. It will try again later.',
                default  => 'AI service error (' . $response->status() . '): ' . mb_strimwidth((string) $msg, 0, 300, '…'),
            });
        }

        return (array) $response->json();
    }
}
