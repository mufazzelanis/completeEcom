<?php

namespace App\Console\Commands;

use App\Models\Banner;
use App\Support\ImageOptimizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * One-time catch-up for ImageOptimizer::storeCropped (added alongside this command, see
 * BannerController::storeBannerImage) — banners uploaded before that existed are still sitting
 * at whatever ratio the admin's original file happened to be, so the browser's object-cover was
 * cropping the mismatch unpredictably on the live site (reported: the hero photo's right edge
 * getting sliced off). This re-crops every existing hero/top banner image to its container's
 * exact size in place, the same transform new uploads already go through at save time, so an
 * admin doesn't have to manually re-upload each banner just to pick up the fix.
 */
class RecropBanners extends Command
{
    protected $signature = 'banners:recrop
        {--dry-run : Report what would change without writing anything}';

    protected $description = 'Re-crop existing hero/top banner images to their exact display size (1920x600 / 1200x600).';

    private const TARGETS = [
        'hero' => [1920, 600],
        'top'  => [1200, 600],
    ];

    public function handle(): int
    {
        @ini_set('memory_limit', '512M');
        @set_time_limit(300);

        $dryRun = (bool) $this->option('dry-run');
        $disk = Storage::disk('public');
        $processed = 0;
        $failed = 0;

        foreach (Banner::whereNotNull('image')->whereIn('position', array_keys(self::TARGETS))->cursor() as $banner) {
            [$targetWidth, $targetHeight] = self::TARGETS[$banner->position];
            $path = $banner->image;

            if (! $disk->exists($path)) {
                continue;
            }

            // Already exactly the target size — nothing to do, re-encoding would just
            // waste a (lossy) re-compression pass for zero visual benefit.
            $currentSize = @getimagesize($disk->path($path));
            if ($currentSize && $currentSize[0] === $targetWidth && $currentSize[1] === $targetHeight) {
                continue;
            }

            $mime = $disk->mimeType($path) ?: null;
            $result = ImageOptimizer::optimizeCropped($disk->path($path), $mime, $targetWidth, $targetHeight, 82);

            if (! $result) {
                if (ImageOptimizer::$lastError) {
                    $this->warn("  \xE2\x9C\x97 banner #{$banner->id} ({$path}): " . ImageOptimizer::$lastError);
                    $failed++;
                }
                continue;
            }

            $this->line(sprintf(
                '  %s banner #%d (%s): %s -> %dx%d',
                $dryRun ? '[dry-run]' : "\xE2\x9C\x93",
                $banner->id,
                $banner->position,
                $path,
                $targetWidth,
                $targetHeight
            ));

            if (! $dryRun) {
                $oldExt = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                $newPath = $result['extension'] !== $oldExt
                    ? preg_replace('/\.' . preg_quote($oldExt, '/') . '$/i', '.' . $result['extension'], $path)
                    : $path;

                $disk->put($newPath, $result['bytes']);
                if ($newPath !== $path) {
                    $disk->delete($path);
                }
                $banner->update(['image' => $newPath]);
            }

            $processed++;
        }

        $this->info(sprintf(
            '%s%d banner(s) re-cropped%s.',
            $dryRun ? '[dry-run] ' : '',
            $processed,
            $failed ? ", {$failed} failed (see warnings above)" : ''
        ));

        return self::SUCCESS;
    }
}
