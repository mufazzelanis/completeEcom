<?php

namespace App\Console\Commands;

use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Setting;
use App\Support\ImageOptimizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Retroactive counterpart to ImageOptimizer (which only covers uploads made *after* it
 * existed) — resizes/recompresses every image already sitting in storage from before this
 * pipeline was added. A single unoptimized ~1.9MB hero banner was directly responsible for
 * a 22s Largest Contentful Paint in production; this is the one-time cleanup for that.
 */
class OptimizeImages extends Command
{
    protected $signature = 'images:optimize
        {--dry-run : Report what would change without writing anything}
        {--min-kb=150 : Skip files already smaller than this}
        {--limit=0 : Stop after N files (0 = no limit) — useful for a first, cautious pass}';

    protected $description = 'Resize/recompress already-uploaded banner, product, category, brand, and branding images in place.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $minBytes = (int) $this->option('min-kb') * 1024;
        $limit = (int) $this->option('limit');

        $disk = Storage::disk('public');
        $processed = 0;
        $failed = 0;
        $savedBytes = 0;

        foreach ($this->jobs() as $job) {
            if ($limit && $processed >= $limit) {
                $this->line("Stopping — hit --limit={$limit}.");
                break;
            }

            $path = $job['path'];
            if (! $path || ! $disk->exists($path)) {
                continue;
            }

            $originalSize = $disk->size($path);
            if ($originalSize < $minBytes) {
                continue; // already small enough, not worth the CPU time to re-touch it
            }

            try {
                $mime = $disk->mimeType($path) ?: null;
                $result = ImageOptimizer::optimize($disk->path($path), $mime, $job['max'], 82);
            } catch (\Throwable $e) {
                $this->warn("  ✗ {$path}: {$e->getMessage()}");
                $failed++;
                continue;
            }

            if (! $result || strlen($result['bytes']) >= $originalSize) {
                continue; // GD couldn't touch it, or it genuinely wasn't improvable — leave as-is
            }

            $newSize = strlen($result['bytes']);
            $savedKb = round(($originalSize - $newSize) / 1024);
            $pct = round((1 - $newSize / $originalSize) * 100);
            $this->line(sprintf('  %s %s: %dKB → %dKB (-%d%%)', $dryRun ? '[dry-run]' : '✓', $path, round($originalSize / 1024), round($newSize / 1024), $pct));

            if (! $dryRun) {
                $oldExt = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                if ($result['extension'] !== $oldExt) {
                    $newPath = preg_replace('/\.' . preg_quote($oldExt, '/') . '$/i', '.' . $result['extension'], $path);
                    $disk->put($newPath, $result['bytes']);
                    $disk->delete($path);
                    ($job['onRename'])($newPath);
                } else {
                    $disk->put($path, $result['bytes']);
                }
            }

            $processed++;
            $savedBytes += ($originalSize - $newSize);
        }

        $this->info(sprintf(
            '%s%d file(s) processed, %.1fMB saved%s.',
            $dryRun ? '[dry-run] ' : '',
            $processed,
            $savedBytes / 1024 / 1024,
            $failed ? ", {$failed} failed (see warnings above)" : ''
        ));

        return self::SUCCESS;
    }

    /**
     * @return iterable<array{path: ?string, max: int, onRename: callable(string): void}>
     */
    private function jobs(): iterable
    {
        foreach (Banner::whereNotNull('image')->cursor() as $banner) {
            yield ['path' => $banner->image, 'max' => 1920, 'onRename' => fn ($p) => $banner->update(['image' => $p])];
        }

        foreach (Product::whereNotNull('image')->cursor() as $product) {
            yield ['path' => $product->image, 'max' => 1200, 'onRename' => fn ($p) => $product->update(['image' => $p])];
        }

        foreach (ProductImage::whereNotNull('image')->cursor() as $image) {
            yield ['path' => $image->image, 'max' => 1200, 'onRename' => fn ($p) => $image->update(['image' => $p])];
        }

        foreach (Category::whereNotNull('image')->cursor() as $category) {
            yield ['path' => $category->image, 'max' => 800, 'onRename' => fn ($p) => $category->update(['image' => $p])];
        }

        foreach (Category::whereNotNull('og_image')->cursor() as $category) {
            yield ['path' => $category->og_image, 'max' => 1200, 'onRename' => fn ($p) => $category->update(['og_image' => $p])];
        }

        foreach (Brand::whereNotNull('logo')->cursor() as $brand) {
            yield ['path' => $brand->logo, 'max' => 600, 'onRename' => fn ($p) => $brand->update(['logo' => $p])];
        }

        // Branding/theme settings store their uploaded file path directly as the setting value.
        foreach (Setting::where('value', 'like', 'settings/%')->cursor() as $setting) {
            yield ['path' => $setting->value, 'max' => 1600, 'onRename' => fn ($p) => Setting::set($setting->key, $p, $setting->group)];
        }
    }
}
