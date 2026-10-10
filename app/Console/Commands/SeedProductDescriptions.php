<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

/**
 * One-off data-cleanup command, the same shape as products:assign-brands: 393 of this
 * catalog's products had no short/full description at all. Written by hand (with real
 * web research backing the specifics for less-familiar product lines) rather than
 * generated, then exported from the dev database into database/data/product_descriptions.php
 * so this command can apply the same text to the live database — a `git pull` only ever
 * carries code, never row data, so this is the other half of that deploy.
 *
 * Matches by exact product name rather than id: dev and live databases can diverge (live
 * has products added after the dev snapshot was taken), so name matching is the safer of
 * the two and costs nothing here since names in this data set are effectively unique.
 * Idempotent — only ever touches a product whose short_description is currently empty.
 */
class SeedProductDescriptions extends Command
{
    protected $signature = 'products:seed-descriptions {--dry-run : Show matches without writing}';
    protected $description = 'Apply the hand-written short/full descriptions to matching products, where missing';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $data = require database_path('data/product_descriptions.php');

        $updated = 0;
        $skippedAlreadySet = 0;
        $notFound = [];

        foreach ($data as $name => [$short, $full]) {
            $product = Product::where('name', $name)->first();

            if (!$product) {
                $notFound[] = $name;
                continue;
            }

            if (!empty($product->short_description)) {
                $skippedAlreadySet++;
                continue;
            }

            if ($dryRun) {
                $this->line("[DRY RUN] Would update: {$name}");
                $updated++;
                continue;
            }

            $product->update([
                'short_description' => $short,
                'description' => $full,
            ]);
            $updated++;
        }

        $this->info(($dryRun ? '[DRY RUN] Would update' : 'Updated') . ": {$updated} products");
        $this->line("Already had a description, skipped: {$skippedAlreadySet}");
        if ($notFound) {
            $this->warn('No matching product found for (' . count($notFound) . '):');
            foreach ($notFound as $n) {
                $this->line("  - {$n}");
            }
        }

        return self::SUCCESS;
    }
}
