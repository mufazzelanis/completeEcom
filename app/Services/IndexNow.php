<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * IndexNow: tells Bing, Yandex, Seznam, Naver… the moment a product page is added or changed,
 * instead of waiting for their next crawl (Bing also feeds ChatGPT search and Copilot). Google
 * doesn't take part — it reads sitemap.xml's lastmod. Free, no account: the key is generated on
 * first use and served at /indexnow.txt (keyLocation) to prove the site is ours.
 *
 * Sent after the response (never slows the admin down), only in production, and each URL at
 * most once an hour however many times it's saved.
 */
class IndexNow
{
    public static function key(): string
    {
        $key = (string) setting('indexnow_key', '');
        if ($key === '') {
            $key = Str::lower(Str::random(32));
            Setting::set('indexnow_key', $key, 'seo');
        }

        return $key;
    }

    public static function submit(string $url): void
    {
        if (! app()->isProduction() || setting('indexnow_enabled', '1') !== '1') {
            return;
        }
        if (! Cache::add('indexnow:' . md5($url), 1, 3600)) {
            return; // already sent within the hour
        }

        after_response(function () use ($url) {
            Http::timeout(8)->acceptJson()->post('https://api.indexnow.org/indexnow', [
                'host' => parse_url(config('app.url'), PHP_URL_HOST),
                'key' => self::key(),
                'keyLocation' => route('indexnow.key'),
                'urlList' => [$url],
            ]);
        });
    }
}
