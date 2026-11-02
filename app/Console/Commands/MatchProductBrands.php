<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Product;
use Illuminate\Console\Command;

/**
 * Products without a brand lose it from their Product structured data and the Google
 * Merchant feed. Most product names start with the brand ("Nature's Bounty Biotin…",
 * "Natures Bounty Fish Oil…") — this matches each brand-less product to the brand whose
 * name its own name starts with (longest match wins, punctuation and case ignored).
 * Dry run by default: shows what it would do; --apply saves it.
 */
class MatchProductBrands extends Command
{
    protected $signature = 'products:match-brands
        {--apply : Save the matches (default only lists them)}
        {--suggest : List the words brand-less product names most often start with — likely brands not created yet}
        {--create= : Comma-separated brand names to create first (only ones that don\'t exist), e.g. "Skino,Dabur,3W Clinic"}';

    protected $description = 'Give brand-less products the brand their name starts with';

    public function handle(): int
    {
        $norm = fn (string $s) => trim(preg_replace('/\s+/', ' ', preg_replace('/[^a-z0-9 ]/', '', str_replace(['&', '’', "'"], ['and', '', ''], mb_strtolower($s)))));

        if ($this->option('suggest')) {
            // one- and two-word name starts, most common first — a person decides which are brands
            $names = Product::whereNull('brand_id')->pluck('name')->map($norm);
            $starts = $names->flatMap(function ($n) {
                $w = explode(' ', $n);

                return array_filter([$w[0] ?? null, isset($w[1]) ? $w[0] . ' ' . $w[1] : null]);
            })->countBy()->filter(fn ($c) => $c >= 3)->sortDesc()->take(40);
            $this->table(['name starts with', 'products'], $starts->map(fn ($c, $k) => [$k, $c])->values()->all());
            $this->comment('Pick the real brands and run: php artisan products:match-brands --create="Brand One,Brand Two" (then add --apply)');

            return self::SUCCESS;
        }

        $planned = []; // brands to create — matched in a dry run too, so it shows exactly what --apply will do
        foreach (array_filter(array_map('trim', explode(',', (string) $this->option('create')))) as $brandName) {
            if (Brand::whereRaw('LOWER(name) = ?', [mb_strtolower($brandName)])->exists()) {
                continue;
            }
            if ($this->option('apply')) {
                $slug = \Illuminate\Support\Str::slug($brandName);
                $slug = Brand::where('slug', $slug)->exists() ? $slug . '-brand' : $slug;
                Brand::create(['name' => $brandName, 'slug' => $slug, 'is_active' => true]);
                $this->line("Created brand: {$brandName}");
            } else {
                $planned[] = ['id' => 0, 'name' => $brandName . ' (new)', 'key' => $norm($brandName)];
                $this->line("Would create brand: {$brandName}");
            }
        }

        // longest names first, so "Nature's Bounty" wins over a brand called "Nature"
        $brands = Brand::where('is_active', true)->get(['id', 'name'])
            ->map(fn ($b) => ['id' => $b->id, 'name' => $b->name, 'key' => $norm($b->name)])
            ->concat($planned)
            ->filter(fn ($b) => mb_strlen($b['key']) >= 3)
            ->sortByDesc(fn ($b) => mb_strlen($b['key']))->values();

        $matched = [];
        Product::whereNull('brand_id')->orderBy('id')->chunkById(500, function ($products) use ($brands, $norm, &$matched) {
            foreach ($products as $p) {
                $name = $norm($p->name) . ' ';
                $b = $brands->first(fn ($b) => str_starts_with($name, $b['key'] . ' '));
                if ($b) {
                    $matched[] = [$p->id, $p->name, $b['id'], $b['name']];
                }
            }
        });

        $total = Product::whereNull('brand_id')->count();
        $this->table(['product', 'brand'], collect($matched)->take(25)->map(fn ($m) => [mb_strimwidth($m[1], 0, 60, '…'), $m[3]])->all());
        $this->line(count($matched) . " of {$total} brand-less products match a brand by name" . (count($matched) > 25 ? ' (first 25 shown)' : '') . '.');

        if (!$this->option('apply')) {
            $this->comment('Nothing saved — run again with --apply to save these.');

            return self::SUCCESS;
        }
        foreach (collect($matched)->groupBy(2) as $brandId => $rows) {
            // query update: no per-product events/cache resets for a bulk fix
            Product::whereIn('id', $rows->pluck(0))->whereNull('brand_id')->update(['brand_id' => $brandId]);
        }
        \App\Support\StorefrontCache::bump();
        $this->info('Saved ' . count($matched) . ' brands.');

        return self::SUCCESS;
    }
}
