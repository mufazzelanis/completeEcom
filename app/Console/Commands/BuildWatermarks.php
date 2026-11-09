<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Support\Watermark;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Prepares every photo for a newly saved watermark in the background, a slice per run, and switches
 * the site over when done, so no customer's page waits on image processing. Does nothing (and costs
 * nothing) when there's no new look to prepare. Safe to stop anywhere: it continues where it left off.
 */
class BuildWatermarks extends Command
{
    protected $signature = 'watermark:build {--seconds=45 : Stop after about this long; the next run continues}';

    protected $description = 'Prepare watermarked product photos in the background, then put them live';

    public function handle(): int
    {
        $desired = Watermark::desired();
        $live = Watermark::live();

        if (!$desired || (int) ($live['v'] ?? 0) === (int) $desired['v']) {
            Watermark::cleanup();
            $this->info('Nothing to prepare.');

            return self::SUCCESS;
        }

        $deadline = microtime(true) + max(5, (int) $this->option('seconds'));
        $key = 'watermark:cursor:' . $desired['v'];
        $cursor = (int) Cache::get($key, 0);
        $made = 0;

        while (microtime(true) < $deadline) {
            $products = Product::where('is_active', true)->whereNotNull('image')->where('id', '>', $cursor)
                ->orderBy('id')->limit(25)->pluck('image', 'id');

            if ($products->isEmpty()) {
                Watermark::publish();
                $this->removeUnmarkedLargeCopies();
                Watermark::cleanup();
                Cache::forget($key);
                $this->info("Done: version {$desired['v']} is live ({$made} files in this run).");

                return self::SUCCESS;
            }

            $ids = $products->keys();
            $gallery = DB::table('product_images')->whereIn('product_id', $ids)->pluck('image');
            $colors = DB::table('product_colors')->whereIn('product_id', $ids)->whereNotNull('image')->pluck('image');
            $excluded = Watermark::excluded();

            foreach ($products->values()->merge($gallery)->merge($colors)->unique() as $path) {
                if (!Watermark::covers($path) || isset($excluded[$path])) {
                    continue;
                }
                foreach (Watermark::WIDTHS as $width) {
                    Watermark::write($path, $width, 'webp', $desired);
                    $made++;
                }
            }

            $cursor = (int) $ids->last();
            Cache::forever($key, $cursor);
        }

        $this->info("Paused after {$made} files; continues on the next run.");

        return self::SUCCESS;
    }

    /** Large thumbnails made before the watermark existed would otherwise stay reachable, unmarked. */
    private function removeUnmarkedLargeCopies(): void
    {
        foreach (Watermark::WIDTHS as $width) {
            Storage::disk('public')->deleteDirectory("thumbs/{$width}/products");
        }
    }
}
