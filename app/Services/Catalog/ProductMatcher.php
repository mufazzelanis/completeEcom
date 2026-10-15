<?php

namespace App\Services\Catalog;

/**
 * Decides whether two product names describe the same item, for keeping imports from
 * creating duplicates. Names from different stores rarely match character for character
 * ("CeraVe Moisturising Lotion -236ml" vs "Cerave Moisturizing Lotion 236 mL"), so both are
 * normalized first — case, punctuation, British/US spelling, unit spellings
 * ("caps"/"capsules", "gm"/"g") — and then compared as:
 *
 *   - size/count: must be identical (100ml ≠ 200ml, 60 capsules ≠ 120 capsules — those are
 *     separate products, never duplicates);
 *   - other numbers in the name must be identical too (Vitamin B-2 ≠ Vitamin B-6);
 *   - wording: Jaccard overlap of the remaining words ≥ $threshold.
 *
 * Deliberately strict: a looser rule tested against the real catalogs also merged Shampoo
 * with Conditioner and Retinol with Vitamin C serum. Near matches below the threshold are
 * for the caller to treat as "possible duplicate" (the importer creates those as drafts).
 */
class ProductMatcher
{
    private const UNITS = [
        'millilitre' => 'ml', 'milliliter' => 'ml', 'mls' => 'ml', 'ml' => 'ml',
        'litre' => 'l', 'liter' => 'l', 'ltr' => 'l', 'l' => 'l',
        'grams' => 'g', 'gram' => 'g', 'gms' => 'g', 'gm' => 'g', 'gr' => 'g', 'g' => 'g',
        'kilogram' => 'kg', 'kgs' => 'kg', 'kg' => 'kg',
        'milligram' => 'mg', 'mg' => 'mg', 'mcg' => 'mcg', 'µg' => 'mcg', 'iu' => 'iu',
        'oz' => 'oz', 'fl' => 'oz',
        'capsules' => 'capsule', 'capsule' => 'capsule', 'caps' => 'capsule', 'cap' => 'capsule',
        'vegicaps' => 'capsule', 'veggie' => 'capsule',
        'tablets' => 'tablet', 'tablet' => 'tablet', 'tabs' => 'tablet', 'tab' => 'tablet',
        'softgels' => 'softgel', 'softgel' => 'softgel', 'gummies' => 'gummy', 'gummy' => 'gummy',
        'pcs' => 'pc', 'pc' => 'pc', 'pieces' => 'pc', 'sachets' => 'sachet', 'sachet' => 'sachet',
    ];

    private const STOP = ['the', 'and', 'for', 'with', 'of', 'a', 'an', 'in', 'to', 'by', 'new', 'original', 'imported', 'uk', 'usa', 'us'];

    public function __construct(private float $threshold = 0.8) {}

    /** ['words' => [...], 'sizes' => [...], 'key' => exact-match key] for one product name. */
    public function fingerprint(string $name): array
    {
        $s = mb_strtolower(html_entity_decode(strip_tags($name), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $s = str_replace(['&', '’', "'"], [' and ', '', ''], $s);
        $s = str_replace(['moisturising', 'moisturiser', 'colour', 'flavour', 'centre'], ['moisturizing', 'moisturizer', 'color', 'flavor', 'center'], $s);
        // "60caps", "100-ml", "3.4 fl oz" → number + unit token
        $s = preg_replace('/(\d)\s*[-x×]?\s*(?=[a-zµ])/u', '$1 ', $s);
        $tokens = preg_split('/[^\p{L}\p{N}.µ]+/u', $s, -1, PREG_SPLIT_NO_EMPTY);

        $sizes = [];
        $numbers = [];
        $words = [];
        $first = null;
        for ($i = 0; $i < count($tokens); $i++) {
            $t = trim($tokens[$i], '.');
            if ($t === '') continue;
            if (is_numeric($t) && isset($tokens[$i + 1], self::UNITS[trim($tokens[$i + 1], '.')])) {
                $sizes[] = (float) $t . self::UNITS[trim($tokens[$i + 1], '.')];
                $i++;
                continue;
            }
            if (in_array($t, self::STOP, true) || isset(self::UNITS[$t])) continue;
            if (is_numeric($t)) {
                $numbers[] = (string) (float) $t;
                continue;
            }
            $first ??= $t;
            $words[] = $t;
        }
        $sizes = array_values(array_unique($sizes));
        sort($sizes);
        $words = array_values(array_unique($words));
        sort($words);

        sort($numbers);

        return ['words' => $words, 'sizes' => $sizes, 'numbers' => $numbers, 'first' => $first,
            'key' => implode(' ', $words) . '|' . implode(',', $sizes) . '|' . implode(',', $numbers)];
    }

    /** Similarity 0..1, or 0 when the sizes/counts differ. */
    public function similarity(array $a, array $b): float
    {
        if ($a['sizes'] !== $b['sizes'] || ($a['numbers'] ?? []) !== ($b['numbers'] ?? [])) {
            return 0.0;
        }
        $inter = count(array_intersect($a['words'], $b['words']));
        $union = count(array_unique(array_merge($a['words'], $b['words'])));

        return $union ? $inter / $union : 0.0;
    }

    public function isSame(array $a, array $b): bool
    {
        return $a['key'] === $b['key'] || $this->similarity($a, $b) >= $this->threshold;
    }
}
