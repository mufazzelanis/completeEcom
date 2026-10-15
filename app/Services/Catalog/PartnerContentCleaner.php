<?php

namespace App\Services\Catalog;

/**
 * Strips a partner store's self-promotion out of product descriptions imported from it, so
 * the product pages here never send customers (or link equity) back to the partner:
 *
 *   - paragraphs/list items that are pure promotion ("You can also order this product on our
 *     Facebook page CosmeClassy", "For more product like that, visit our website Cosme Classy",
 *     a bare "www.cosmeclassy.com") are removed;
 *   - any link pointing at the partner — its site or its Facebook page — is unwrapped (text kept);
 *   - remaining mentions in running text ("While cosmeclassy strives to ensure the accuracy…")
 *     are replaced with this store's name. Only text is touched, never tag attributes.
 */
class PartnerContentCleaner
{
    private const PROMO_PATTERNS = [
        '/order this product on our fa\w*book page/i',
        '/visit our (?:web\s*site|facebook|page)/i',
    ];

    private string $namePattern;
    private string $exactNamePattern;

    /** @param string $partner e.g. "cosmeclassy" — also matches "Cosme Classy", "cosme-classy", "cosmeclassy.com" */
    public function __construct(string $partner, private string $storeName)
    {
        $letters = preg_split('//u', mb_strtolower(preg_replace('/[^\p{L}\p{N}]+/u', '', $partner)), -1, PREG_SPLIT_NO_EMPTY);
        $core = '(?:www\.)?' . implode('[\s\-]?', array_map(fn ($c) => preg_quote($c, '/'), $letters)) . '(?:\.com(?:\.bd)?)?';
        $this->namePattern = "/{$core}/iu";
        $this->exactNamePattern = "/^{$core}$/iu";
    }

    public function clean(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return $html;
        }

        // 1. promotional blocks
        $html = preg_replace_callback('#<(p|li|h[1-6])\b[^>]*>(.*?)</\1>#is', function ($m) {
            $text = trim(html_entity_decode(strip_tags($m[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            foreach (self::PROMO_PATTERNS as $pattern) {
                if (preg_match($pattern, $text)) {
                    return '';
                }
            }

            return preg_match($this->exactNamePattern, $text) ? '' : $m[0];
        }, $html);

        // 2. links to the partner (site or social page) → plain text
        $html = preg_replace_callback('#<a\b([^>]*)>(.*?)</a>#is', function ($m) {
            return preg_match('/href\s*=\s*["\'][^"\']*/i', $m[1], $href) && preg_match($this->namePattern, $href[0])
                ? $m[2]
                : $m[0];
        }, $html);

        // 3. the partner's name in running text → this store's name (text nodes only)
        $parts = preg_split('/(<[^>]*>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        foreach ($parts as $i => $part) {
            if ($part !== '' && $part[0] !== '<') {
                $parts[$i] = preg_replace($this->namePattern, $this->storeName, $part);
            }
        }
        $html = implode('', $parts);

        // leftovers: empty paragraphs/headings the removals produced
        $html = preg_replace('#<(p|h[1-6])\b[^>]*>(?:\s|&nbsp;|<br\s*/?>)*</\1>#i', '', $html);

        return trim($html) === '' ? null : trim($html);
    }

    public function mentionsPartner(?string $html): bool
    {
        return $html !== null && (preg_match($this->namePattern, $html) || preg_match(self::PROMO_PATTERNS[0], $html));
    }
}
