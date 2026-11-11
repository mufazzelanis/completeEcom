<?php

namespace App\Services\AiBlog;

use App\Models\AiBlogTopic;
use App\Models\BlogPost;
use Illuminate\Support\Str;

/**
 * Checks an AI-written article before it can go live. Every problem becomes a line in the
 * post's "Check before publishing" box, and any problem at all keeps the post a draft — even
 * in auto-publish mode. The guard against Google's scaled-content and medical-claim policies.
 */
class QualityGate
{
    /** Claims a shop must not make (Google Ads/AdSense health policy, consumer law, plain honesty). */
    private const RISKY = [
        '100% guarantee', 'guaranteed result', 'guaranteed to', 'miracle', 'cures ', 'cure your', 'cure for',
        'permanent result', 'permanently remove', 'no side effect', 'zero side effect', 'fda approved',
        'doctor recommended', 'dermatologist recommended', 'clinically proven',
        'রোগ সারায়', 'রোগ সারাবে', 'সম্পূর্ণ সেরে', 'চিরতরে', 'স্থায়ীভাবে দূর', '১০০% গ্যারান্টি', '100% গ্যারান্টি',
        'কোনো পার্শ্বপ্রতিক্রিয়া নেই', 'কোন পার্শ্বপ্রতিক্রিয়া নেই', 'অলৌকিক', 'ডাক্তার রেকমেন্ডেড',
    ];

    /** @return string[] problems; empty = fine to publish */
    public static function check(AiBlogTopic $topic, array $main, string $content, ?array $alt, ?string $altContent, array $productIds): array
    {
        $notes = [];

        $words = self::words($content);
        $min = (int) round(Settings::words() * 0.6);
        if ($words < $min) {
            $notes[] = "Short article: about {$words} words (expected at least {$min}). Thin pages hurt the whole blog in Google — add more useful detail.";
        }

        $text = mb_strtolower(strip_tags(($main['title'] ?? '') . ' ' . $content . ' ' . ($alt['title'] ?? '') . ' ' . $altContent));
        foreach (self::RISKY as $phrase) {
            if (str_contains($text, mb_strtolower($phrase))) {
                $notes[] = "Risky claim \"" . trim($phrase) . "\" — reword it (no cure/guarantee/medical promises).";
            }
        }

        if (trim((string) $topic->note) === '') {
            $notes[] = 'No Mitavin Note on this topic — add the shop\'s own tip or experience to the article. It is what makes the post different from every other site.';
        }

        if (!preg_match('/\bbp-ids-/', $content)) {
            $notes[] = 'No product card was placed in the article — add one with the editor\'s Product button.';
        }

        if ($similar = self::similarTitle((string) ($main['title'] ?? ''))) {
            $notes[] = "Very similar to an existing post: \"{$similar}\" — merge them or change the angle, so the blog has no near-duplicates.";
        }

        if (Settings::bilingual() && $alt !== null && self::words((string) $altContent) < (int) round($words * 0.5)) {
            $notes[] = 'The second-language version is much shorter than the main one — check it is complete.';
        }

        return array_values(array_unique($notes));
    }

    public static function words(string $html): int
    {
        return count(preg_split('/\s+/u', trim(strip_tags($html)), -1, PREG_SPLIT_NO_EMPTY));
    }

    /** Title of an existing post that reads almost the same, or null. */
    public static function similarTitle(string $title): ?string
    {
        $norm = fn ($s) => trim(preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', mb_strtolower((string) $s)));
        $mine = $norm($title);
        if ($mine === '') {
            return null;
        }

        foreach (BlogPost::latest('id')->limit(500)->get(['title', 'alt_title']) as $post) {
            foreach ([$post->title, $post->alt_title] as $other) {
                $theirs = $norm($other);
                if ($theirs === '') {
                    continue;
                }
                similar_text($mine, $theirs, $pct);
                if ($mine === $theirs || $pct >= 85) {
                    return Str::limit((string) $other, 80);
                }
            }
        }

        return null;
    }
}
