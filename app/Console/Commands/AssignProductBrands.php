<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * One-off data-cleanup command: the bulk CSV import that created most of the catalog
 * never populated brand_id, even though almost every product name states its brand
 * up front ("CeraVe...", "Aveeno...", "L'Oréal..."). Google Merchant Center flags
 * these as "No global identifier provided (e.g. gtin, brand)" since brand is one of
 * the few identifiers we can realistically supply (no GTIN/barcode data exists for
 * this catalog). Idempotent — only ever touches products with a null brand_id, so
 * it's safe to run again after importing new products.
 */
class AssignProductBrands extends Command
{
    protected $signature = 'products:assign-brands {--dry-run : Show matches without writing}';
    protected $description = 'Infer and assign brand_id for active products from their name, where missing';

    private const PATTERNS = [
        "Nature's Bounty" => ["nature's bounty", "natures bounty"],
        'Isis Pharma' => ['isis pharma', 'isispharma', 'isis'],
        'Axe Brand' => ['axe brand'],
        'On Call Plus' => ['on call plus'],
        'Ceylon Naturals' => ['ceylon naturals'],
        '21st Century' => ['21st century'],
        'Kirkland Signature' => ['kirkland signature', 'kirkland'],
        'CeraVe' => ['cerave', 'creave'],
        "L'Oreal" => ["l'oreal", "l'oréal", 'loreal'],
        'NeoCell' => ['neocell', 'neo cell'],
        'NeoCare' => ['neocare'],
        'Dr Rashel' => ['dr rashel'],
        'Bio Slim' => ['bio slim'],
        'Aveeno' => ['aveeno'],
        'Himalaya' => ['himalaya'],
        'Vitabiotics' => ['vitabiotics'],
        'Boots' => ['boots'],
        'Enfamil' => ['enfamil'],
        'Aptamil' => ['aptamil'],
        'Similac' => ['similac'],
        'Giggles' => ['giggles'],
        'Panadol' => ['panadol'],
        'Nestle' => ['nestle'],
        'Davidoff' => ['davidoff'],
        'Bragg' => ['bragg'],
        'Gerber' => ['gerber'],
        'Tender' => ['tender adult diaper'],
        'Lavazza' => ['lavazza'],
        'Milna' => ['milna'],
        "Munchy's" => ["munchy's", 'munchys'],
        'Nescafe' => ['nescafe'],
        'Evian' => ['evian'],
        'VivaChek' => ['vivachek'],
        'Omnitest' => ['omnitest'],
        'Alcon' => ['alcon'],
        'Dicare' => ['dicare'],
        'Advil' => ['advil'],
        'Bengay' => ['bengay'],
        'Beuslim' => ['beuslim'],
        'Carebeau' => ['carebeau'],
        'Abzorb' => ['abzorb'],
        'Candid' => ['candid dusting'],
        'Bivatracin' => ['bivatracin'],
        'Kaminomoto' => ['kaminomoto'],
        'Rogaine' => ['rogaine'],
        'Finaldealz' => ['finaldealz'],
        'Mushroom' => ['mushroom oro care', 'mushroom dento care'],
        'Avamys' => ['avamys'],
    ];

    // Leftover starter-kit seed rows, never real catalog items — never guess a brand for these.
    private const DEMO_NAMES = [
        'iPhone 15 Pro', 'Samsung Galaxy S24', 'Sony WH-1000XM5 Headphones', 'MacBook Pro M3',
        "Men's Casual T-Shirt", "Women's Summer Dress", 'Running Shoes Nike', 'Wooden Bookshelf',
        'Premium Yoga Mat', 'JavaScript: The Good Parts', 'Face Moisturizer SPF50', 'Wireless Earbuds Pro',
    ];

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $products = Product::active()->whereNull('brand_id')->get();
        $updated = 0;
        $skippedGeneric = [];
        $brandCache = [];

        foreach ($products as $product) {
            if (in_array($product->name, self::DEMO_NAMES, true)) {
                continue;
            }

            $lower = strtolower($product->name);
            $found = null;
            foreach (self::PATTERNS as $canonical => $needles) {
                foreach ($needles as $needle) {
                    if (str_contains($lower, $needle)) {
                        $found = $canonical;
                        break 2;
                    }
                }
            }

            if (!$found) {
                $skippedGeneric[] = $product->name;
                continue;
            }

            if ($dryRun) {
                $this->line("[DRY RUN] {$product->name} -> {$found}");
                $updated++;
                continue;
            }

            if (!isset($brandCache[$found])) {
                $brandCache[$found] = Brand::firstOrCreate(
                    ['name' => $found],
                    ['slug' => Str::slug($found), 'is_active' => true]
                );
            }

            $product->update(['brand_id' => $brandCache[$found]->id]);
            $updated++;
        }

        $this->info(($dryRun ? '[DRY RUN] Would update' : 'Updated') . ": {$updated} products");
        $this->warn('No clear brand in name (' . count($skippedGeneric) . '):');
        foreach ($skippedGeneric as $n) {
            $this->line("  - {$n}");
        }

        return self::SUCCESS;
    }
}
