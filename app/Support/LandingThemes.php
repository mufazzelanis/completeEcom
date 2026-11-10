<?php

namespace App\Support;

use App\Models\LandingPage;

/**
 * Landing page designs. Every theme renders the same saved content (hero, benefits, reviews,
 * FAQ, order form …), so switching theme never means re-typing anything — only the look and
 * the order of the sections change. "classic" is the original single-column page.
 *
 * Each theme's palette is a handful of CSS variables (landing/themes/_styles); tints are
 * mixed from them in CSS, so a page's own brand colour (Custom Brand Color) re-colours the
 * whole theme from one value.
 */
class LandingThemes
{
    public const DEFAULT = 'classic';

    /** Admin picker: name, what it is for, the swatch it is shown with. */
    public static function all(): array
    {
        return [
            'classic' => [
                'name' => 'Classic',
                'tagline' => 'The original one-column page',
                'best' => 'Any product',
                'swatch' => ['#ffffff', '#ea580c', '#f3f4f6'],
            ],
            'clinical' => [
                'name' => 'Clinical',
                'tagline' => 'Clean pharmacy look, trust first',
                'best' => 'Skincare: CeraVe, The Ordinary, sunscreen',
                'swatch' => ['#f4f9fb', '#0e7490', '#10b981'],
            ],
            'offer' => [
                'name' => 'Mega Offer',
                'tagline' => 'Big price, countdown, order form near the top',
                'best' => 'Facebook ad sales, combos, COD impulse buys',
                'swatch' => ['#7f1d1d', '#dc2626', '#facc15'],
            ],
            'story' => [
                'name' => 'Story',
                'tagline' => 'Problem → solution → proof, long read',
                'best' => 'Supplements: fish oil, biotin, vitamins',
                'swatch' => ['#fbf7f0', '#9a3412', '#15803d'],
            ],
            'care' => [
                'name' => 'Soft Care',
                'tagline' => 'Gentle pastel look, safety and mothers\' reviews',
                'best' => 'Baby care: Aveeno Baby, lotion, formula',
                'swatch' => ['#fff7f5', '#e0567f', '#6cc3ad'],
            ],
            'premium' => [
                'name' => 'Premium Dark',
                'tagline' => 'Dark luxury look with gold accents',
                'best' => 'Premium products, gift boxes, combo packs',
                'swatch' => ['#0d1210', '#c9a45c', '#1b2420'],
            ],
        ];
    }

    public static function keys(): array
    {
        return array_keys(self::all());
    }

    public static function key(?string $theme): string
    {
        return in_array($theme, self::keys(), true) ? $theme : self::DEFAULT;
    }

    public static function of(LandingPage $page): string
    {
        return self::key($page->theme);
    }

    /** CSS custom properties for a theme (not used by classic). */
    public static function tokens(string $theme, ?string $brandColor = null): array
    {
        $tokens = match ($theme) {
            'offer' => ['bg' => '#fff8f1', 'surface' => '#ffffff', 'ink' => '#1f0f08', 'muted' => '#6e5244', 'line' => '#f4dccb', 'brand' => '#dc2626', 'accent' => '#facc15', 'hero' => '#7f1d1d', 'radius' => '14px'],
            'story' => ['bg' => '#fbf7f0', 'surface' => '#ffffff', 'ink' => '#2a2118', 'muted' => '#6f6255', 'line' => '#eadfcf', 'brand' => '#9a3412', 'accent' => '#15803d', 'hero' => '#f5ecdf', 'radius' => '12px'],
            'care' => ['bg' => '#fff7f5', 'surface' => '#ffffff', 'ink' => '#3b2a33', 'muted' => '#86707b', 'line' => '#f5dfe5', 'brand' => '#e0567f', 'accent' => '#6cc3ad', 'hero' => '#ffe9ee', 'radius' => '26px'],
            'premium' => ['bg' => '#0d1210', 'surface' => '#151c19', 'ink' => '#f3efe6', 'muted' => '#a9ae9f', 'line' => '#27312c', 'brand' => '#c9a45c', 'accent' => '#7fb89b', 'hero' => '#111815', 'radius' => '16px'],
            default => ['bg' => '#f4f9fb', 'surface' => '#ffffff', 'ink' => '#0f2530', 'muted' => '#52707e', 'line' => '#dbe8ee', 'brand' => '#0e7490', 'accent' => '#10b981', 'hero' => '#e7f5f8', 'radius' => '18px'],
        };
        if ($brandColor && preg_match('/^#[0-9a-fA-F]{6}$/', $brandColor)) {
            $tokens['brand'] = $brandColor;
        }
        $tokens['brand-2'] = hex_shade($tokens['brand'], -0.2);
        $tokens['on-brand'] = self::readableOn($tokens['brand']);

        return $tokens;
    }

    /** Sections top to bottom. "offer" shows its price in the hero, so it has no offer box. */
    public static function sections(string $theme): array
    {
        return match ($theme) {
            'offer' => ['hero', 'trust', 'order', 'benefits', 'testimonials', 'video', 'who_for', 'content', 'certificates', 'faq'],
            'story' => ['hero', 'who_for', 'content', 'benefits', 'video', 'testimonials', 'certificates', 'offer', 'faq', 'order'],
            'care' => ['hero', 'trust', 'who_for', 'benefits', 'testimonials', 'video', 'content', 'certificates', 'offer', 'faq', 'order'],
            'premium' => ['hero', 'trust', 'content', 'benefits', 'video', 'testimonials', 'who_for', 'certificates', 'offer', 'faq', 'order'],
            default => ['hero', 'trust', 'benefits', 'content', 'certificates', 'video', 'who_for', 'testimonials', 'offer', 'faq', 'order'],
        };
    }

    /** Headings a theme uses when the page leaves them blank. */
    public static function heading(string $theme, string $section): string
    {
        $own = [
            'clinical' => ['benefits' => 'কেন এটি আপনার ত্বকের জন্য সেরা', 'who_for' => 'কাদের জন্য উপযুক্ত', 'certificates' => '১০০% অরিজিনাল — প্রমাণসহ'],
            'offer' => ['benefits' => 'এই অফারে যা পাচ্ছেন', 'testimonials' => 'যারা ইতিমধ্যে নিয়েছেন'],
            'story' => ['who_for' => 'আপনার কি এই সমস্যাগুলো হচ্ছে?', 'benefits' => 'সমাধান — যেভাবে কাজ করে', 'testimonials' => 'যাদের জীবন বদলেছে'],
            'care' => ['benefits' => 'আপনার শিশুর জন্য কেন নিরাপদ', 'testimonials' => 'মায়েরা যা বলছেন', 'who_for' => 'কাদের জন্য'],
            'premium' => ['benefits' => 'বিশেষত্ব', 'testimonials' => 'ক্রেতাদের অভিজ্ঞতা'],
        ];
        $common = [
            'benefits' => 'উপকারিতা', 'who_for' => 'কাদের জন্য এটি', 'testimonials' => 'গ্রাহকরা কি বলছেন?',
            'video' => 'এটি কিভাবে কাজ করে?', 'faq' => 'সচরাচর জিজ্ঞাসা', 'certificates' => 'সার্টিফিকেট ও প্রমাণ',
        ];

        return $own[$theme][$section] ?? $common[$section] ?? '';
    }

    /** Black or white text, whichever reads better on the colour. */
    public static function readableOn(string $hex): string
    {
        [$r, $g, $b] = array_map(fn ($c) => hexdec($c) / 255, str_split(ltrim($hex, '#'), 2));
        $lin = fn ($c) => $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        $l = 0.2126 * $lin($r) + 0.7152 * $lin($g) + 0.0722 * $lin($b);

        return $l > 0.45 ? '#14110a' : '#ffffff';
    }
}
