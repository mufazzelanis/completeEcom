<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

/**
 * Retires a duplicate product without losing anything: its URL 301-redirects to the product
 * being kept (so Google transfers it, and old links/bookmarks still land on a real page) and
 * it's deactivated, which drops it from the shop, the sitemap and the Merchant feed. Nothing
 * is deleted — orders that reference it keep working, and it can be restored by clearing
 * "301 Redirect URL" and re-activating it in the admin.
 *
 *   php artisan products:redirect-duplicate 1053 1017   (1053 = duplicate, 1017 = keep)
 */
class RedirectDuplicateProduct extends Command
{
    protected $signature = 'products:redirect-duplicate
        {duplicate : ID of the product to retire}
        {keep : ID of the product it redirects to}
        {--force : Skip the confirmation question}';

    protected $description = 'Deactivate a duplicate product and 301-redirect its URL to the product being kept';

    public function handle(): int
    {
        $duplicate = Product::find($this->argument('duplicate'));
        $keep = Product::find($this->argument('keep'));

        if (!$duplicate || !$keep) {
            $this->error('Product not found: #' . ($duplicate ? $this->argument('keep') : $this->argument('duplicate')));

            return self::FAILURE;
        }
        if ($duplicate->is($keep)) {
            $this->error('The duplicate and the product to keep are the same product.');

            return self::FAILURE;
        }
        if ($keep->redirect_url || !$keep->is_active) {
            $this->error("#{$keep->id} is itself inactive or redirected — pick a live product to keep.");

            return self::FAILURE;
        }

        $target = route('products.show', $keep);
        $this->table(['', 'ID', 'Name', 'Price'], [
            ['retire', $duplicate->id, $duplicate->name, $duplicate->sale_price ?? $duplicate->price],
            ['keep', $keep->id, $keep->name, $keep->sale_price ?? $keep->price],
        ]);
        $this->line("Redirect: " . route('products.show', $duplicate) . "  →  {$target}");

        if (!$this->option('force') && !$this->confirm('Apply?', true)) {
            return self::SUCCESS;
        }

        $duplicate->update(['redirect_url' => $target, 'is_active' => false]);
        $this->info("Done — #{$duplicate->id} is inactive and redirects to #{$keep->id}.");

        return self::SUCCESS;
    }
}
