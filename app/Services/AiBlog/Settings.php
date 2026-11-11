<?php

namespace App\Services\AiBlog;

use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;

/**
 * AI Blog settings (Admin → AI Blog → Settings), one place for their keys and defaults.
 * API keys are stored encrypted with the app key and never sent back to the browser.
 */
class Settings
{
    /** Claude models offered in the admin, with Anthropic's price per 1M tokens (USD): [label, input, output]. */
    public const ANTHROPIC_MODELS = [
        'claude-opus-5-5'   => ['Claude Opus 5.5 — best writing', 4.00, 20.00],
        'claude-sonnet-5-5' => ['Claude Sonnet 5.5 — very good, half the price', 2.00, 10.00],
        'claude-haiku-5-5'  => ['Claude Haiku 5.5 — cheapest (topic ideas)', 0.10, 0.50],
    ];

    public const PROVIDERS = ['anthropic' => 'Claude (Anthropic)', 'openai' => 'ChatGPT (OpenAI)'];

    /** Never more than this many autopilot posts a day, whatever is configured (scaled-content safety). */
    public const HARD_DAILY_CAP = 5;

    public static function enabled(): bool
    {
        return setting('ai_blog_enabled', '0') === '1';
    }

    public static function provider(): string
    {
        $p = (string) setting('ai_blog_provider', 'anthropic');

        return array_key_exists($p, self::PROVIDERS) ? $p : 'anthropic';
    }

    /** Model that writes the articles. */
    public static function model(): string
    {
        if (self::provider() === 'openai') {
            return trim((string) setting('ai_blog_openai_model', '')) ?: 'gpt-5.5';
        }
        $m = (string) setting('ai_blog_model', 'claude-opus-5-5');

        return array_key_exists($m, self::ANTHROPIC_MODELS) ? $m : 'claude-opus-5-5';
    }

    /** Cheaper model for small jobs (topic ideas, connection test). */
    public static function lightModel(): string
    {
        return self::provider() === 'openai' ? self::model() : 'claude-haiku-5-5';
    }

    public static function apiKey(?string $provider = null): string
    {
        $raw = (string) setting('ai_blog_' . ($provider ?? self::provider()) . '_key', '');
        if ($raw === '') {
            return '';
        }
        try {
            return Crypt::decryptString($raw);
        } catch (\Throwable) {
            return ''; // app key changed since it was saved — admin re-enters it
        }
    }

    public static function saveApiKey(string $provider, string $plain): void
    {
        Setting::set('ai_blog_' . $provider . '_key', $plain === '' ? '' : Crypt::encryptString($plain), 'ai_blog');
    }

    /** "sk-ant-…a1b2" style hint so the admin can see which key is saved without revealing it. */
    public static function keyHint(string $provider): ?string
    {
        $key = self::apiKey($provider);

        return $key === '' ? null : substr($key, 0, 7) . '…' . substr($key, -4);
    }

    /** USD per 1M tokens [input, output] for a model. */
    public static function prices(string $provider, string $model): array
    {
        if ($provider === 'anthropic') {
            $row = self::ANTHROPIC_MODELS[$model] ?? null;

            return $row ? [$row[1], $row[2]] : [4.00, 20.00];
        }

        return [(float) setting('ai_blog_openai_price_in', '5'), (float) setting('ai_blog_openai_price_out', '30')];
    }

    public static function postsPerDay(): int
    {
        return max(0, min(self::HARD_DAILY_CAP, (int) setting('ai_blog_posts_per_day', '1')));
    }

    /** Posting times ("HH:MM", site timezone), sorted — only the first postsPerDay() are used. */
    public static function slots(): array
    {
        $slots = collect(preg_split('/[\s,]+/', (string) setting('ai_blog_slots', '10:00')))
            ->map(fn ($s) => trim($s))
            ->filter(fn ($s) => preg_match('/^([01]?\d|2[0-3]):[0-5]\d$/', $s))
            ->map(fn ($s) => str_pad($s, 5, '0', STR_PAD_LEFT))
            ->unique()->sort()->values();

        return $slots->take(self::postsPerDay())->all();
    }

    public static function defaultLang(): string
    {
        return setting('ai_blog_default_lang', 'bn') === 'en' ? 'en' : 'bn';
    }

    public static function bilingual(): bool
    {
        return setting('ai_blog_bilingual', '1') === '1';
    }

    /** 'draft' = every post waits for a person; 'auto' = publish when every quality check passes. */
    public static function publishMode(): string
    {
        return setting('ai_blog_publish_mode', 'draft') === 'auto' ? 'auto' : 'draft';
    }

    public static function monthlyBudget(): float
    {
        return max(0, (float) setting('ai_blog_monthly_budget', '15'));
    }

    public static function words(): int
    {
        return max(500, min(2500, (int) setting('ai_blog_words', '1100')));
    }

    public static function tone(): string
    {
        return trim((string) setting('ai_blog_tone', '')) ?: 'Friendly and trustworthy, like an experienced shop assistant who knows the products well — warm, practical, never pushy.';
    }

    public static function styleGuide(): string
    {
        return trim((string) setting('ai_blog_style_guide', ''));
    }

    public static function disclaimer(string $lang): string
    {
        $default = $lang === 'bn'
            ? 'এই লেখাটি শুধু সাধারণ তথ্যের জন্য। ত্বক বা স্বাস্থ্যের কোনো সমস্যা থাকলে নতুন কিছু ব্যবহারের আগে ডাক্তার বা চর্মরোগ বিশেষজ্ঞের পরামর্শ নিন।'
            : 'This article is for general information only. If you have a skin or health condition, talk to a doctor or dermatologist before trying a new product.';
        $value = setting('ai_blog_disclaimer_' . $lang);

        return $value === null ? $default : trim((string) $value);
    }

    public static function authorId(): ?int
    {
        $id = (int) setting('ai_blog_author_id', '0');

        return $id > 0 ? $id : null;
    }

    public static function blogCategoryId(): ?int
    {
        $id = (int) setting('ai_blog_category_id', '0');

        return $id > 0 ? $id : null;
    }

    public static function timezone(): string
    {
        return (string) setting('timezone', 'Asia/Dhaka');
    }

    public static function ready(): bool
    {
        return self::apiKey() !== '';
    }
}
