<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\SearchConsoleRow;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Google Search Console → search_console_rows, for Admin → SEO Insights.
 *
 * Read-only access through a Google Cloud service account: its JSON key is uploaded in the admin
 * (kept on the private disk, never web-reachable) and its email is added as a user of the Search
 * Console property. Requests are signed here (RS256 JWT → OAuth token) — no Google SDK needed,
 * which keeps it working on plain shared hosting.
 *
 * Search Console data lags ~2 days and keeps settling for a few more, so each sync re-reads the
 * last few days (rows for those days are replaced, never duplicated).
 */
class SearchConsole
{
    public const KEY_FILE = 'search-console/credentials.json';
    private const SCOPE = 'https://www.googleapis.com/auth/webmasters.readonly';
    private const API = 'https://www.googleapis.com/webmasters/v3';

    public static function connected(): bool
    {
        return Storage::disk('private')->exists(self::KEY_FILE);
    }

    public static function credentials(): array
    {
        $json = json_decode((string) Storage::disk('private')->get(self::KEY_FILE), true);
        if (! is_array($json) || empty($json['client_email']) || empty($json['private_key'])) {
            throw new RuntimeException('The uploaded key file is not a Google service-account JSON key.');
        }

        return $json;
    }

    public static function siteUrl(): string
    {
        return (string) setting('gsc_site_url', 'sc-domain:' . parse_url(config('app.url'), PHP_URL_HOST));
    }

    private static function token(): string
    {
        return Cache::remember('gsc:token:' . md5(self::credentials()['client_email']), 3000, function () {
            $c = self::credentials();
            $b64 = fn ($v) => rtrim(strtr(base64_encode(is_string($v) ? $v : json_encode($v)), '+/', '-_'), '=');
            $now = time();
            $unsigned = $b64(['alg' => 'RS256', 'typ' => 'JWT']) . '.' . $b64([
                'iss' => $c['client_email'], 'scope' => self::SCOPE,
                'aud' => $c['token_uri'] ?? 'https://oauth2.googleapis.com/token',
                'iat' => $now, 'exp' => $now + 3600,
            ]);
            if (! openssl_sign($unsigned, $signature, $c['private_key'], OPENSSL_ALGO_SHA256)) {
                throw new RuntimeException('Could not sign with the service-account key (is the file complete?).');
            }
            $r = Http::asForm()->timeout(20)->post($c['token_uri'] ?? 'https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $unsigned . '.' . $b64($signature),
            ]);
            if (! $r->ok() || ! $r->json('access_token')) {
                throw new RuntimeException('Google refused the key: ' . ($r->json('error_description') ?: $r->body()));
            }

            return $r->json('access_token');
        });
    }

    private static function api(string $method, string $path, array $body = [])
    {
        $http = Http::withToken(self::token())->acceptJson()->timeout(60);
        $r = $method === 'get' ? $http->get(self::API . $path) : $http->post(self::API . $path, $body);
        if (! $r->successful()) {
            $msg = $r->json('error.message') ?: $r->body();
            throw new RuntimeException($r->status() === 403
                ? 'No access to ' . self::siteUrl() . ' — add ' . self::credentials()['client_email'] . ' as a user in Search Console (Settings → Users and permissions). Google said: ' . $msg
                : 'Search Console API error (' . $r->status() . '): ' . $msg);
        }

        return $r->json();
    }

    /** Properties this service account can read — for the "Test connection" button. */
    public static function sites(): array
    {
        return collect(self::api('get', '/sites')['siteEntry'] ?? [])->pluck('permissionLevel', 'siteUrl')->all();
    }

    /** Pull [from..to] (inclusive) and replace those days' rows. Returns the number of rows stored. */
    public static function sync(Carbon $from, Carbon $to): int
    {
        $site = rawurlencode(self::siteUrl());
        $rows = [];
        $start = 0;
        do {
            $batch = self::api('post', "/sites/{$site}/searchAnalytics/query", [
                'startDate' => $from->toDateString(),
                'endDate' => $to->toDateString(),
                'dimensions' => ['date', 'query', 'page'],
                'type' => 'web',
                'dataState' => 'all',
                'rowLimit' => 25000,
                'startRow' => $start,
            ])['rows'] ?? [];
            array_push($rows, ...$batch);
            $start += count($batch);
        } while (count($batch) === 25000 && $start < 250000);

        $resolve = self::pageResolver();
        $insert = [];
        foreach ($rows as $r) {
            [$date, $query, $page] = $r['keys'];
            [$type, $productId] = $resolve($page);
            $insert[] = [
                'date' => $date,
                'query' => mb_substr($query, 0, 500),
                'query_hash' => md5(mb_strtolower($query)),
                'page' => mb_substr($page, 0, 700),
                'page_hash' => md5($page),
                'page_type' => $type,
                'product_id' => $productId,
                'clicks' => (int) $r['clicks'],
                'impressions' => (int) $r['impressions'],
                'position' => round((float) $r['position'], 2),
            ];
        }

        DB::transaction(function () use ($from, $to, $insert) {
            SearchConsoleRow::whereBetween('date', [$from->toDateString(), $to->toDateString()])->delete();
            foreach (array_chunk($insert, 1000) as $chunk) {
                SearchConsoleRow::insertOrIgnore($chunk);
            }
        });

        \App\Models\Setting::set('gsc_last_sync', now()->toDateTimeString(), 'seo');

        return count($insert);
    }

    /** URL → [page type, product id] using this site's own routes. */
    private static function pageResolver(): \Closure
    {
        $products = Product::pluck('id', 'slug')->mapWithKeys(fn ($id, $slug) => [mb_strtolower($slug) => $id]);

        return function (string $url) use ($products) {
            $path = trim((string) parse_url($url, PHP_URL_PATH), '/');
            return match (true) {
                $path === '' => ['home', null],
                (bool) preg_match('#^products/([^/]+)#', $path, $m) => ['product', $products[mb_strtolower(urldecode($m[1]))] ?? null],
                str_starts_with($path, 'shop/category/') => ['category', null],
                str_starts_with($path, 'brand/') => ['brand', null],
                str_starts_with($path, 'blog') => ['blog', null],
                $path === 'shop' => ['shop', null],
                default => ['other', null],
            };
        };
    }
}
