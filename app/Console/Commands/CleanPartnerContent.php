<?php

namespace App\Console\Commands;

use App\Http\Controllers\HomeController;
use App\Models\Product;
use App\Services\Catalog\PartnerContentCleaner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * One-off/repeatable fix for products already imported from a partner store: removes the
 * partner's promotional lines ("order on our Facebook page …", "visit our website …"), unwraps
 * links to the partner and replaces its name with this store's — see PartnerContentCleaner.
 * Safe to run any number of times; products without partner content are left untouched.
 *
 *   php artisan catalog:clean-partner-content cosmeclassy --dry-run
 *   php artisan catalog:clean-partner-content cosmeclassy
 */
class CleanPartnerContent extends Command
{
    protected $signature = 'catalog:clean-partner-content
        {partner=cosmeclassy : The partner name as it appears in the text/links}
        {--dry-run : Show what would change, save nothing}';

    protected $description = "Remove a partner store's promotion, links and name from product descriptions";

    public function handle(): int
    {
        $storeName = setting('site_name', config('app.name'));
        $cleaner = new PartnerContentCleaner($this->argument('partner'), $storeName);
        $dry = (bool) $this->option('dry-run');
        $changed = 0;
        $example = null;

        Product::query()->select(['id', 'name', 'short_description', 'description'])->chunkById(200, function ($products) use ($cleaner, $dry, &$changed, &$example) {
            foreach ($products as $product) {
                $updates = [];
                foreach (['description', 'short_description'] as $field) {
                    if ($cleaner->mentionsPartner($product->$field)) {
                        $clean = $cleaner->clean($product->$field);
                        if ($clean !== $product->$field) {
                            $updates[$field] = $clean;
                        }
                    }
                }
                if (!$updates) {
                    continue;
                }
                $changed++;
                $example ??= [$product->id, $product->name];
                if (!$dry) {
                    // quietly: one homepage-cache refresh at the end instead of one per product
                    $product->forceFill($updates)->saveQuietly();
                }
            }
        });

        if (!$dry && $changed) {
            Cache::forever(HomeController::CACHE_VERSION_KEY, (int) Cache::get(HomeController::CACHE_VERSION_KEY, 1) + 1);
        }

        $this->info(($dry ? 'Would clean' : 'Cleaned') . " {$changed} product(s); partner name replaced with \"{$storeName}\".");
        if ($example) {
            $this->line("e.g. #{$example[0]} {$example[1]}");
        }

        return self::SUCCESS;
    }
}
