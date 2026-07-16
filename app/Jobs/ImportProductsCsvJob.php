<?php

namespace App\Jobs;

use App\Models\BulkImport;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportProductsCsvJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;

    public int $tries = 1;

    private const BATCH_SIZE = 500;

    public function __construct(private readonly int $bulkImportId)
    {
    }

    public function handle(): void
    {
        $import = BulkImport::findOrFail($this->bulkImportId);
        $import->update(['status' => 'processing', 'started_at' => now()]);

        $absolutePath = Storage::disk('local')->path($import->stored_path);

        try {
            $import->update(['total_rows' => $this->countDataRows($absolutePath)]);
            $this->processFile($absolutePath, $import);

            $import->update([
                'status'      => 'completed',
                'finished_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $import->update([
                'status'      => 'failed',
                'finished_at' => now(),
                'errors'      => array_merge($import->errors ?? [], ['Import failed: '.$e->getMessage()]),
            ]);

            throw $e;
        } finally {
            Storage::disk('local')->delete($import->stored_path);
        }
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

    private function processFile(string $path, BulkImport $import): void
    {
        $handle = fopen($path, 'r');
        fgetcsv($handle); // skip header row

        $catMap = Category::whereNull('parent_id')
            ->get(['id', 'name'])
            ->keyBy(fn ($c) => strtolower(trim($c->name)))
            ->map(fn ($c) => $c->id)
            ->toArray();

        $usedSlugs     = [];
        $pending       = [];
        $errors        = [];
        $createdCount  = 0;
        $skippedCount  = 0;
        $processedRows = 0;
        $row           = 1;

        while (($data = fgetcsv($handle)) !== false) {
            $row++;
            $processedRows++;

            if (count($data) < 4) {
                $errors[] = "Row {$row}: too few columns.";
                $skippedCount++;
                continue;
            }

            [$name, $sku, $catName, $price, $salePrice, $stock, $shortDesc, $desc, $isActive, $isFeatured]
                = array_pad($data, 10, '');

            $name  = trim($name);
            $price = trim($price);

            if ($name === '' || ! is_numeric($price)) {
                $errors[] = "Row {$row}: name or price missing/invalid.";
                $skippedCount++;
                continue;
            }

            $catId = $catMap[strtolower(trim($catName))] ?? null;
            if (! $catId) {
                $errors[] = "Row {$row}: category '{$catName}' not found — skipped.";
                $skippedCount++;
                continue;
            }

            $pending[] = [
                'slug_base'         => Str::slug($name),
                'name'              => $name,
                'sku'               => $sku ?: null,
                'category_id'       => $catId,
                'price'             => $price,
                'sale_price'        => is_numeric(trim($salePrice)) && trim($salePrice) !== '' ? trim($salePrice) : null,
                'stock'             => is_numeric(trim($stock)) ? (int) trim($stock) : 0,
                'short_description' => trim($shortDesc) ?: null,
                'description'       => trim($desc) ?: null,
                'is_active'         => trim($isActive) !== '0' ? 1 : 0,
                'is_featured'       => trim($isFeatured) === '1' ? 1 : 0,
            ];

            if (count($pending) >= self::BATCH_SIZE) {
                $createdCount += $this->flushBatch($pending, $usedSlugs);
                $pending = [];
                $this->flushProgress($import, $processedRows, $createdCount, $skippedCount, $errors);
            }
        }

        if (! empty($pending)) {
            $createdCount += $this->flushBatch($pending, $usedSlugs);
        }
        fclose($handle);

        $this->flushProgress($import, $processedRows, $createdCount, $skippedCount, $errors);
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

    private function flushProgress(BulkImport $import, int $processedRows, int $createdCount, int $skippedCount, array $errors): void
    {
        $import->update([
            'processed_rows' => $processedRows,
            'created_count'  => $createdCount,
            'skipped_count'  => $skippedCount,
            'errors'         => array_slice($errors, 0, 200),
        ]);
    }
}
