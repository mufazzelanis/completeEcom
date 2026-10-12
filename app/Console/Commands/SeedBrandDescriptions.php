<?php

namespace App\Console\Commands;

use App\Models\Brand;
use Illuminate\Console\Command;

/**
 * Fills in descriptions for the store's main brands — shown as intro copy on each brand page
 * (/brand/{slug}) and used for its meta description, which is what "CeraVe price in
 * Bangladesh"-style searches land on.
 *
 * Only brands whose description is still empty or meaningless (e.g. Aveeno's was set to the
 * category name "Baby's Skin Care") are filled — anything an admin actually wrote is left
 * alone unless --force is passed. Text is factual brand background only (no medical or
 * "cures" claims). Infant-formula brands (Enfamil, Similac, Aptamil…) are deliberately not
 * included: Bangladesh's Breast-milk Substitutes Act 2013 restricts promotional copy for them.
 *
 *   php artisan brands:seed-descriptions --dry-run   # preview, changes nothing
 *   php artisan brands:seed-descriptions             # fill empty/placeholder ones
 */
class SeedBrandDescriptions extends Command
{
    protected $signature = 'brands:seed-descriptions {--dry-run : Show what would change without saving} {--force : Also overwrite descriptions an admin already wrote}';

    protected $description = 'Fill empty brand descriptions for the main brands (SEO intro copy on brand pages)';

    /** slug => description. First sentence doubles as the meta description (~155 chars). */
    private const DESCRIPTIONS = [
        'cerave' => "Buy original CeraVe skincare in Bangladesh — moisturizing creams, lotions and cleansers developed with dermatologists. "
            . "CeraVe formulas contain three essential ceramides and hyaluronic acid to help restore and maintain the skin's natural barrier, "
            . "suitable for dry, sensitive and acne-prone skin. অরিজিনাল CeraVe পণ্য পাচ্ছেন ক্যাশ অন ডেলিভারিতে, সারা বাংলাদেশে।",

        'aveeno' => "Buy original Aveeno skin care in Bangladesh — gentle daily moisturizers, body washes and the Aveeno Baby range. "
            . "Aveeno is known for formulas made with oat, created for dry and sensitive skin of the whole family, including babies. "
            . "অরিজিনাল Aveeno ও Aveeno Baby পণ্য পাচ্ছেন ক্যাশ অন ডেলিভারিতে, সারা বাংলাদেশে।",

        'natures-bounty' => "Buy original Nature's Bounty vitamins and supplements in Bangladesh — fish oil, biotin, vitamin D3, B-complex, minerals and more. "
            . "Nature's Bounty is an American supplement brand with a wide range of daily vitamins for adults. "
            . "সাপ্লিমেন্ট শুরু করার আগে প্রয়োজনে চিকিৎসকের পরামর্শ নিন। অরিজিনাল পণ্য, ক্যাশ অন ডেলিভারি।",

        'boots' => "Buy original Boots products in Bangladesh — skincare, body care, cucumber range, baby care and everyday health essentials. "
            . "Boots is a long-established British pharmacy and beauty brand known for affordable, trusted products. "
            . "অরিজিনাল UK Boots পণ্য পাচ্ছেন ক্যাশ অন ডেলিভারিতে, সারা বাংলাদেশে।",

        'himalaya' => "Buy original Himalaya products in Bangladesh — herbal face washes, skin care, baby care and wellness products. "
            . "Himalaya is a well-known herbal wellness brand offering everyday personal care for the whole family. "
            . "অরিজিনাল Himalaya পণ্য পাচ্ছেন ক্যাশ অন ডেলিভারিতে, সারা বাংলাদেশে।",

        'vitabiotics' => "Buy original Vitabiotics vitamins in Bangladesh — Pregnacare, Wellman, Wellwoman, Perfectil and other targeted supplements. "
            . "Vitabiotics is a British vitamin company with formulas designed for different life stages and needs. "
            . "সাপ্লিমেন্ট শুরু করার আগে প্রয়োজনে চিকিৎসকের পরামর্শ নিন। অরিজিনাল পণ্য, ক্যাশ অন ডেলিভারি।",

        'loreal' => "Buy original L'Oréal Paris products in Bangladesh — skincare, haircare and makeup from the French beauty brand. "
            . "Shop popular L'Oréal Paris ranges for face, hair and everyday beauty, all 100% original. "
            . "অরিজিনাল L'Oréal Paris পণ্য পাচ্ছেন ক্যাশ অন ডেলিভারিতে, সারা বাংলাদেশে।",

        'isis-pharma' => "Buy original ISIS Pharma skincare in Bangladesh — dermatological care for sensitive, oily and acne-prone skin. "
            . "ISIS Pharma is a French dermo-cosmetic brand offering targeted cleansers, creams and sun care. "
            . "অরিজিনাল ISIS Pharma পণ্য পাচ্ছেন ক্যাশ অন ডেলিভারিতে, সারা বাংলাদেশে।",

        'neocell' => "Buy original NeoCell supplements in Bangladesh — collagen and beauty supplements for skin, hair, nails and joints. "
            . "NeoCell is an American brand best known for its collagen range, including Super Collagen. "
            . "সাপ্লিমেন্ট শুরু করার আগে প্রয়োজনে চিকিৎসকের পরামর্শ নিন। অরিজিনাল পণ্য, ক্যাশ অন ডেলিভারি।",
    ];

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $rows = [];

        foreach (self::DESCRIPTIONS as $slug => $text) {
            $brand = Brand::where('slug', $slug)->first();
            if (!$brand) {
                $rows[] = [$slug, 'not found — skipped', ''];
                continue;
            }

            // "Real" = what the brand page itself would use as intro copy (see shop/index).
            $hasReal = meaningful_seo($brand->description, [$brand->name], 25) !== null;
            if ($hasReal && !$this->option('force')) {
                $rows[] = [$brand->name, 'kept (admin-written)', mb_strimwidth((string) $brand->description, 0, 50, '…')];
                continue;
            }

            $was = trim((string) $brand->description) !== '' ? mb_strimwidth($brand->description, 0, 40, '…') : '(empty)';
            if (!$dry) {
                $brand->update(['description' => $text]); // model event refreshes the homepage cache
            }
            $rows[] = [$brand->name, $dry ? 'WOULD fill' : 'filled', $was];
        }

        $this->table(['Brand', 'Result', 'Was'], $rows);
        $this->info($dry ? 'Dry run — nothing saved. Run again without --dry-run to apply.' : 'Done.');

        return self::SUCCESS;
    }
}
