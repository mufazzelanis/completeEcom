<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * One-off data-cleanup command: a category name like "Women's Care" was found stored in the
 * live database as the literal text "Women&#039;s Care" — the apostrophe already HTML-entity-
 * encoded in the data itself, rather than being a real apostrophe character. Blade's {{ }}
 * (used for <title> and friends) escapes on output as normal, so that already-encoded "&"
 * gets escaped a second time into "&amp;", and what a visitor's browser tab actually shows is
 * the literal text "Women&#039;s Care" — exactly the bug reported. The dev database doesn't
 * have this (checked), so it's isolated to data already on the live database, most likely
 * introduced by whatever produced the original bulk import.
 *
 * Decodes any HTML entities found in Category/Brand/Product name fields (plus Category and
 * Product short_description — both plain text, rendered via Blade's auto-escaping {{ }})
 * back to the real characters they represent. Safe to run on already-clean data —
 * html_entity_decode() on text with no entities in it is a no-op, so this is idempotent and
 * safe to re-run after future imports.
 *
 * Deliberately excludes Product/Category description: that field is real HTML rendered raw
 * ({!! !!}, from the rich-text editor), where something like "&amp;" is the CORRECT, properly
 * escaped way to represent a literal "&" inside markup — confirmed by testing this command
 * against it locally, where it "fixed" several descriptions by turning a correctly-escaped
 * "&amp;" back into a bare "&", which would have been actively wrong to ship.
 */
class FixDoubleEncodedNames extends Command
{
    protected $signature = 'catalog:fix-html-entities {--dry-run : Show matches without writing}';
    protected $description = 'Decode any literal HTML entities (e.g. &#039;) found in catalog name/short_description fields';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $targets = [
            Category::class => ['name'],
            Brand::class => ['name'],
            Product::class => ['name', 'short_description'],
        ];

        $totalFixed = 0;

        foreach ($targets as $modelClass => $fields) {
            $modelName = class_basename($modelClass);
            foreach ($modelClass::query()->cursor() as $row) {
                $changes = [];
                foreach ($fields as $field) {
                    $original = $row->{$field};
                    if (!$original) {
                        continue;
                    }
                    $decoded = html_entity_decode($original, ENT_QUOTES | ENT_HTML5);
                    if ($decoded !== $original) {
                        $changes[$field] = $decoded;
                    }
                }

                if (empty($changes)) {
                    continue;
                }

                $totalFixed++;
                $preview = $changes['name'] ?? $changes['short_description'] ?? reset($changes);
                $preview = Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($preview))), 80);
                $this->line(($dryRun ? '[DRY RUN] ' : '') . "{$modelName} #{$row->id}: {$preview}");

                if (!$dryRun) {
                    $row->update($changes);
                }
            }
        }

        $this->info(($dryRun ? '[DRY RUN] Would fix' : 'Fixed') . ": {$totalFixed} record(s)");

        return self::SUCCESS;
    }
}
