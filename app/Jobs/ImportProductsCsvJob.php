<?php

namespace App\Jobs;

use App\Http\Controllers\HomeController;
use App\Models\BulkImport;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\Catalog\ProductMatcher;
use App\Support\ImageOptimizer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class ImportProductsCsvJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;

    public int $tries = 1;

    private const BATCH_SIZE = 500;

    /** Columns a row may not exist without — everything else is optional with sane defaults. */
    private const REQUIRED_COLUMNS = ['name', 'category_name', 'price'];

    public function __construct(private readonly int $bulkImportId)
    {
    }

    public function handle(): void
    {
        $import = BulkImport::findOrFail($this->bulkImportId);
        $import->update(['status' => 'processing', 'started_at' => now()]);

        $absolutePath = Storage::disk('local')->path($import->stored_path);
        $extractDir = null;

        try {
            $imageMap = $import->images_zip_path
                ? $this->extractImagesZip($import, $extractDir)
                : [];

            $import->update(['total_rows' => $this->countDataRows($absolutePath)]);
            $this->processFile($absolutePath, $import, $imageMap);

            $import->update([
                'status'      => 'completed',
                'finished_at' => now(),
            ]);

            // Product::insert() fires no model events, so the homepage cache (normally
            // invalidated on Product::created) is refreshed here, once for the whole file.
            if ($import->fresh()->created_count > 0) {
                Cache::forever(HomeController::CACHE_VERSION_KEY, (int) Cache::get(HomeController::CACHE_VERSION_KEY, 1) + 1);
            }
        } catch (\Throwable $e) {
            $import->update([
                'status'      => 'failed',
                'finished_at' => now(),
                'errors'      => array_merge($import->errors ?? [], ['Import failed: '.$e->getMessage()]),
            ]);

            throw $e;
        } finally {
            Storage::disk('local')->delete($import->stored_path);
            if ($import->images_zip_path) {
                Storage::disk('local')->delete($import->images_zip_path);
            }
            if ($extractDir && is_dir($extractDir)) {
                $this->deleteDirectory($extractDir);
            }
        }
    }

    /**
     * Unzips the uploaded images archive into a scratch folder and returns a
     * lowercase-basename => absolute-path map, so row lookups are a simple
     * case-insensitive array hit regardless of how deep the zip nests folders.
     */
    private function extractImagesZip(BulkImport $import, ?string &$extractDir): array
    {
        $zipAbsolutePath = Storage::disk('local')->path($import->images_zip_path);
        $extractDir = Storage::disk('local')->path('imports/images/extracted-'.$import->id);

        if (!is_dir($extractDir)) {
            mkdir($extractDir, 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipAbsolutePath) !== true) {
            $import->update(['errors' => array_merge($import->errors ?? [], ['Could not open the images ZIP file — product images were skipped.'])]);
            return [];
        }

        $zip->extractTo($extractDir);
        $zip->close();

        $map = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($extractDir, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isFile()) {
                $map[strtolower($fileInfo->getFilename())] = $fileInfo->getPathname();
            }
        }

        return $map;
    }

    private function deleteDirectory(string $dir): void
    {
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($dir);
    }

    private function countDataRows(string $path): int
    {
        $handle = fopen($path, 'r');
        $count  = -1; // header row doesn't count
        while (fgets($handle) !== false) {
            $count++;
        }
        fclose($handle);

        return max(0, $count);
    }

    private function processFile(string $path, BulkImport $import, array $imageMap): void
    {
        $handle = fopen($path, 'r');
        $header = fgetcsv($handle, escape: '\\');

        if ($header === false) {
            throw new \RuntimeException('CSV file is empty.');
        }

        // Column lookup is by header NAME, not position — so the columns can be in
        // any order and new optional columns never shift/break existing ones.
        $columns = [];
        foreach ($header as $i => $col) {
            $columns[strtolower(trim($col))] = $i;
        }

        $missingRequired = array_diff(self::REQUIRED_COLUMNS, array_keys($columns));
        if (!empty($missingRequired)) {
            throw new \RuntimeException('CSV is missing required column(s): '.implode(', ', $missingRequired));
        }

        $get = function (array $data, string $column) use ($columns): string {
            $index = $columns[$column] ?? null;
            return $index !== null ? trim((string) ($data[$index] ?? '')) : '';
        };

        $topCategories = Category::whereNull('parent_id')
            ->get(['id', 'name'])
            ->keyBy(fn ($c) => strtolower(trim($c->name)))
            ->map(fn ($c) => $c->id)
            ->toArray();

        $subcategories = Category::whereNotNull('parent_id')
            ->get(['id', 'name', 'parent_id'])
            ->groupBy('parent_id')
            ->map(fn ($group) => $group->keyBy(fn ($c) => strtolower(trim($c->name)))->map(fn ($c) => $c->id))
            ->toArray();

        $brands = Brand::get(['id', 'name'])
            ->keyBy(fn ($b) => strtolower(trim($b->name)))
            ->map(fn ($b) => $b->id)
            ->toArray();

        // Duplicate guard: a row is skipped when its SKU or its name+size already exists —
        // in the store or earlier in this same file — so uploading a CSV twice (or one that
        // repeats a product) never creates the same product again.
        $matcher = new ProductMatcher();
        $knownSkus = [];
        $knownNames = []; // sizes|numbers signature => [[fingerprint, label], …]
        $remember = function (string $name, ?string $sku, string $label) use ($matcher, &$knownSkus, &$knownNames) {
            if ($sku !== null && $sku !== '') {
                $knownSkus[mb_strtolower($sku)] = $label;
            }
            $fp = $matcher->fingerprint($name);
            $knownNames[implode(',', $fp['sizes']) . '|' . implode(',', $fp['numbers'])][] = [$fp, $label];
        };
        foreach (Product::query()->get(['id', 'name', 'sku']) as $p) {
            $remember($p->name, $p->sku, "#{$p->id} {$p->name}");
        }
        $findDuplicate = function (string $name, string $sku) use ($matcher, &$knownSkus, &$knownNames): ?string {
            if ($sku !== '' && isset($knownSkus[mb_strtolower($sku)])) {
                return $knownSkus[mb_strtolower($sku)] . ' (same SKU)';
            }
            $fp = $matcher->fingerprint($name);
            // only same-size/same-number products can match, so compare within that group
            foreach ($knownNames[implode(',', $fp['sizes']) . '|' . implode(',', $fp['numbers'])] ?? [] as [$other, $label]) {
                if ($matcher->isSame($fp, $other)) {
                    return $label;
                }
            }

            return null;
        };

        $usedSlugs           = [];
        $pending             = [];
        $errors              = [];
        $createdCount        = 0;
        $skippedCount        = 0;
        $imagesMatchedCount  = 0;
        $imagesMissingCount  = 0;
        $processedRows       = 0;
        $row                 = 1;

        while (($data = fgetcsv($handle, escape: '\\')) !== false) {
            $row++;
            $processedRows++;

            $name = $get($data, 'name');
            $price = $get($data, 'price');

            if ($name === '' || !is_numeric($price)) {
                $errors[] = "Row {$row}: name or price missing/invalid.";
                $skippedCount++;
                continue;
            }

            $sku = $get($data, 'sku');
            if ($duplicateOf = $findDuplicate($name, $sku)) {
                $errors[] = "Row {$row}: '{$name}' already exists as {$duplicateOf} — skipped.";
                $skippedCount++;
                continue;
            }

            $catName = $get($data, 'category_name');
            $catId = $topCategories[strtolower($catName)] ?? null;
            if (!$catId) {
                $errors[] = "Row {$row}: category '{$catName}' not found — skipped.";
                $skippedCount++;
                continue;
            }

            $subcatId = null;
            $subcatName = $get($data, 'subcategory_name');
            if ($subcatName !== '') {
                $subcatId = $subcategories[$catId][strtolower($subcatName)] ?? null;
                if (!$subcatId) {
                    $errors[] = "Row {$row}: subcategory '{$subcatName}' not found under '{$catName}' — product created without a subcategory.";
                }
            }

            $brandId = null;
            $brandName = $get($data, 'brand_name');
            if ($brandName !== '') {
                $brandId = $brands[strtolower($brandName)] ?? null;
                if (!$brandId) {
                    $errors[] = "Row {$row}: brand '{$brandName}' not found — product created without a brand.";
                }
            }

            $imagePath = null;
            $imageFilename = $get($data, 'image_filename');
            if ($imageFilename !== '') {
                // A full URL needs no ZIP at all — this lets a CSV that already
                // carries hosted image links (e.g. a WooCommerce-style export) be
                // uploaded on its own, with images fetched directly per row.
                if (preg_match('/^https?:\/\//i', $imageFilename)) {
                    $imagePath = $this->storeImageFromUrl($imageFilename);
                    if ($imagePath) {
                        $imagesMatchedCount++;
                    } else {
                        $errors[] = "Row {$row}: could not download image from '{$imageFilename}' — product created without an image.";
                        $imagesMissingCount++;
                    }
                } else {
                    $sourcePath = $imageMap[strtolower(basename($imageFilename))] ?? null;
                    if ($sourcePath) {
                        $imagePath = $this->storeImage($sourcePath);
                        if ($imagePath) {
                            $imagesMatchedCount++;
                        } else {
                            $errors[] = "Row {$row}: '{$imageFilename}' is not a valid image — product created without an image.";
                            $imagesMissingCount++;
                        }
                    } else {
                        $errors[] = "Row {$row}: image '{$imageFilename}' not found in the uploaded ZIP — product created without an image.";
                        $imagesMissingCount++;
                    }
                }
            }

            $salePrice = $get($data, 'sale_price');
            $stock = $get($data, 'stock');
            $weight = $get($data, 'weight');
            $lowStock = $get($data, 'low_stock_threshold');

            $remember($name, $sku, "row {$row} of this file");
            $description = $get($data, 'description');

            $pending[] = [
                'slug_base'           => Str::slug($name),
                'name'                => $name,
                'sku'                 => $sku ?: null,
                'barcode'             => $get($data, 'barcode') ?: null,
                'category_id'         => $catId,
                'subcategory_id'      => $subcatId,
                'brand_id'            => $brandId,
                'price'               => $price,
                'sale_price'          => is_numeric($salePrice) ? $salePrice : null,
                'stock'               => is_numeric($stock) ? (int) $stock : 0,
                'low_stock_threshold' => is_numeric($lowStock) ? (int) $lowStock : 5,
                'weight'              => is_numeric($weight) ? $weight : null,
                'image'               => $imagePath,
                'short_description'   => $get($data, 'short_description') ?: null,
                // Rows are bulk-inserted, bypassing Product::setDescriptionAttribute(), so the
                // same sanitizing is applied here — the description is rendered unescaped.
                'description'         => $description !== '' ? clean($description, 'rich_content') : null,
                'meta_title'          => $get($data, 'meta_title') ?: null,
                'meta_description'    => $get($data, 'meta_description') ?: null,
                'is_active'           => $get($data, 'is_active') !== '0' ? 1 : 0,
                'is_featured'         => $get($data, 'is_featured') === '1' ? 1 : 0,
            ];

            if (count($pending) >= self::BATCH_SIZE) {
                $createdCount += $this->flushBatch($pending, $usedSlugs);
                $pending = [];
                $this->flushProgress($import, $processedRows, $createdCount, $skippedCount, $imagesMatchedCount, $imagesMissingCount, $errors);
            }
        }

        if (!empty($pending)) {
            $createdCount += $this->flushBatch($pending, $usedSlugs);
        }
        fclose($handle);

        $this->flushProgress($import, $processedRows, $createdCount, $skippedCount, $imagesMatchedCount, $imagesMissingCount, $errors);
    }

    /**
     * Copies a matched image out of the extracted ZIP into public product storage.
     * Returns null (instead of storing anything) if the file isn't actually a valid
     * image — trusting the ZIP entry's filename extension would let an attacker ship
     * e.g. "photo.jpg.php" and have it land executable on the public disk.
     */
    private function storeImage(string $sourceAbsolutePath): ?string
    {
        $info = @getimagesize($sourceAbsolutePath);
        if ($info === false) {
            return null;
        }

        $extension = match ($info['mime'] ?? null) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
            'image/bmp'  => 'bmp',
            default      => null,
        };
        if ($extension === null) {
            return null;
        }

        // Resized + WebP like every other product image upload (ImageOptimizer), falling
        // back to the original bytes when GD can't handle the file.
        $optimized = ImageOptimizer::optimize($sourceAbsolutePath, $info['mime'], 1600, 82);

        $storedName = 'products/'.Str::random(32).'.'.($optimized['extension'] ?? $extension);
        Storage::disk('public')->put($storedName, $optimized['bytes'] ?? file_get_contents($sourceAbsolutePath));

        return $storedName;
    }

    /**
     * Same validate-then-store contract as storeImage(), but the source is a
     * remote URL instead of a ZIP entry — a plain User-Agent is needed since
     * several hosts (e.g. WordPress media behind some CDNs/WAFs) 403/500 a
     * bare PHP HTTP client. Failures here are non-fatal to the row: a bad or
     * unreachable image just leaves the product without one.
     */
    private function storeImageFromUrl(string $url): ?string
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36',
            ])->timeout(20)->retry(2, 500)->get($url);
        } catch (\Throwable) {
            return null;
        }

        if (!$response->successful()) {
            return null;
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'bulk_import_img');
        file_put_contents($tmpFile, $response->body());
        try {
            return $this->storeImage($tmpFile);
        } finally {
            @unlink($tmpFile);
        }
    }

    /**
     * Insert one batch, resolving slug collisions with a single lookup query per batch
     * instead of one query per row — essential for CSVs with hundreds of thousands of rows.
     */
    private function flushBatch(array $pending, array &$usedSlugs): int
    {
        $bases = array_values(array_unique(array_column($pending, 'slug_base')));

        $existing = Product::where(function ($q) use ($bases) {
            foreach ($bases as $base) {
                $q->orWhere('slug', $base)->orWhere('slug', 'like', $base.'-%');
            }
        })->pluck('slug');

        foreach ($existing as $slug) {
            $usedSlugs[$slug] = true;
        }

        $now  = now();
        $rows = [];
        foreach ($pending as $item) {
            $slug = $this->resolveSlug($item['slug_base'], $usedSlugs);
            $usedSlugs[$slug] = true;

            unset($item['slug_base']);
            $rows[] = array_merge($item, [
                'slug'       => $slug,
                'type'       => 'simple',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        Product::insert($rows);

        return count($rows);
    }

    private function resolveSlug(string $base, array $usedSlugs): string
    {
        $slug = $base;
        $i    = 1;

        while (isset($usedSlugs[$slug])) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    private function flushProgress(
        BulkImport $import,
        int $processedRows,
        int $createdCount,
        int $skippedCount,
        int $imagesMatchedCount,
        int $imagesMissingCount,
        array $errors
    ): void {
        $import->update([
            'processed_rows'       => $processedRows,
            'created_count'        => $createdCount,
            'skipped_count'        => $skippedCount,
            'images_matched_count' => $imagesMatchedCount,
            'images_missing_count' => $imagesMissingCount,
            'errors'               => array_slice($errors, 0, 200),
        ]);
    }
}
