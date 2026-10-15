<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\Catalog\ProductMatcher;
use App\Services\Catalog\WooCommerceStoreClient;
use App\Support\ImageOptimizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Imports a partner WooCommerce store's catalog (name, price, sale price, stock, categories,
 * brand, short description, description, image) into this store — without duplicates:
 *
 *   1+2. same SKU as an existing product  → skipped; that's also how an earlier import is
 *                                             recognised (the source's SKUs are kept), and
 *                                             --sync then refreshes its price/stock
 *   3. same product by name + size          → skipped (ProductMatcher: spelling/unit-agnostic,
 *                                             different size or count is never a duplicate)
 *   4. duplicate inside the source itself   → only the first copy is imported
 *   5. possible duplicate (same size/numbers,
 *      similar but not certain name)        → created as a DRAFT (hidden from the shop) and
 *                                             flagged in the report, so nothing can appear
 *                                             twice on the live site and no genuinely new
 *                                             product is lost — activate or delete after a look
 *   6. otherwise                            → created and live
 *
 * Products with a ৳0 price are created inactive (draft) so they can't be ordered for free.
 * Every run writes a CSV report (storage/app/imports/…) listing each product's outcome and,
 * for skips, which existing product it matched. Re-running is always safe.
 *
 *   php artisan catalog:import-woocommerce https://cosmeclassy.com --source=cosmeclassy --dry-run
 *   php artisan catalog:import-woocommerce https://cosmeclassy.com --source=cosmeclassy
 *   php artisan catalog:import-woocommerce https://cosmeclassy.com --source=cosmeclassy --sync   (later: refresh price/stock)
 */
class ImportWooCommerceCatalog extends Command
{
    protected $signature = 'catalog:import-woocommerce
        {url : The WooCommerce store, e.g. https://cosmeclassy.com}
        {--source= : Short name used for the cached catalog and report files (default: the domain)}
        {--dry-run : Decide and report everything, but save nothing}
        {--limit=0 : Create at most this many products (0 = all) — handy for a first trial}
        {--stock=20 : Stock to set for in-stock products when the source does not publish a quantity}
        {--sync : For products already imported from this source, refresh price, sale price and stock}
        {--threshold=0.8 : Name-similarity (0–1) at which two same-size products count as duplicates}
        {--review=0.5 : Name-similarity at which a product is still imported, but as a draft to review}
        {--cache : Reuse the catalog downloaded by the previous run instead of fetching it again}';

    protected $description = 'Import a partner WooCommerce catalog (prices, categories, brands, descriptions, images) without creating duplicates';

    /** Source top-level category (normalized name) → existing store category slug. */
    private const CATEGORY_ALIASES = [
        'food supplements' => 'food-supplements',
        'derma care'       => 'medicated-care',
        'baby care'        => 'baby-store',
        'makeup'           => 'womens-makeup',
    ];

    private const IGNORED_SOURCE_CATEGORIES = ['default', 'uncategorized', 'uncategorised'];

    private ProductMatcher $matcher;
    private string $source;
    private string $host;
    private array $catsById = [];
    private array $categoryCache = [];
    private array $brandCache = [];
    private array $usedSlugs = [];
    /** Normalized brand name => display name, longest first — for products the source left unbranded. */
    private array $knownBrands = [];

    public function handle(): int
    {
        $url = rtrim($this->argument('url'), '/');
        $this->host = parse_url($url, PHP_URL_HOST) ?: $url;
        $this->source = $this->option('source') ?: Str::before(preg_replace('/^www\./', '', $this->host), '.');
        $this->matcher = new ProductMatcher((float) $this->option('threshold'));
        $dry = (bool) $this->option('dry-run');
        $limit = (int) $this->option('limit');

        [$products, $categories] = $this->loadCatalog($url);
        $this->catsById = collect($categories)->keyBy('id')->all();
        $this->knownBrands = $this->collectKnownBrands($products);
        $this->info(sprintf('Source: %s — %d products, %d categories', $this->host, count($products), count($categories)));

        // ---- index of what this store already has
        $existing = Product::query()->get(['id', 'name', 'slug', 'sku']);
        $bySku = $existing->filter(fn ($p) => filled($p->sku))->keyBy(fn ($p) => mb_strtolower(trim($p->sku)));
        $fingerprints = $existing->map(fn ($p) => ['product' => $p, 'fp' => $this->matcher->fingerprint($p->name)])->all();
        $this->usedSlugs = $existing->pluck('slug')->flip()->all();
        $preexistingCount = count($fingerprints); // "possible duplicate" only ever refers to these
        $seenInSource = [];

        $report = [];
        $counts = [];
        $created = 0;
        $bar = $this->output->createProgressBar(count($products));
        $bar->start();

        foreach ($products as $item) {
            $product = null;
            $bar->advance();
            $name = $this->text($item['name'] ?? '');
            $fp = $this->matcher->fingerprint($name);
            [$price, $sale] = $this->prices($item);
            $row = ['action' => '', 'source_id' => $item['id'], 'name' => $name, 'price' => $price, 'sale_price' => $sale,
                'category' => '', 'brand' => $this->brandName($item) ?? '', 'matched_product' => '', 'note' => ''];

            try {
                // 1. same SKU — imported before (the source's SKUs are kept as-is), or an
                //    existing product with that SKU. --sync refreshes price/stock only when the
                //    name still matches too, so an unrelated product that happens to share a
                //    SKU is never overwritten.
                $sku = trim((string) ($item['sku'] ?? ''));
                if ($sku !== '' && ($skuMatch = $bySku->get(mb_strtolower($sku)))) {
                    $row['matched_product'] = $this->describe($skuMatch);
                    $sameName = $this->matcher->isSame($fp, $this->matcher->fingerprint($skuMatch->name));
                    if ($this->option('sync') && $sameName) {
                        $row['action'] = 'synced';
                        if (!$dry) {
                            $skuMatch->update(['price' => $price, 'sale_price' => $sale, 'stock' => $this->stock($item)] + ($price > 0 ? [] : ['is_active' => false]));
                        }
                    } else {
                        $row['action'] = $sameName ? 'already-imported' : 'skipped-duplicate';
                        $row['note'] = $sameName ? '' : 'same SKU, different name — not touched';
                    }
                    $this->tally($counts, $report, $row);
                    continue;
                }

                // 3. same product already in this store / 4. repeated inside the source
                [$best, $score] = $this->bestMatch($fp, $fingerprints);
                if ($best && $this->matcher->isSame($fp, $best['fp'])) {
                    $row['action'] = 'skipped-duplicate';
                    $row['note'] = sprintf('same name & size (%.0f%% match)', $score * 100);
                    $row['matched_product'] = $this->describe($best['product']);
                    $this->tally($counts, $report, $row);
                    continue;
                }
                if (isset($seenInSource[$fp['key']])) {
                    $row['action'] = 'skipped-duplicate';
                    $row['note'] = 'listed twice in the source store — first copy imported';
                    $row['matched_product'] = 'source #' . $seenInSource[$fp['key']];
                    $this->tally($counts, $report, $row);
                    continue;
                }
                $seenInSource[$fp['key']] = $item['id'];

                if ($limit && $created >= $limit) {
                    $row['action'] = 'not-processed (limit)';
                    $this->tally($counts, $report, $row);
                    continue;
                }

                // 5. create
                [$categoryId, $subcategoryId, $path] = $this->resolveCategory($item, $dry);
                $row['category'] = $path;
                // Near-match check against products that were in the store BEFORE this run —
                // two new sibling products from the source (a shampoo and its conditioner) are
                // both genuinely new and must not draft each other.
                [$near, $nearScore] = $this->bestMatch($fp, array_slice($fingerprints, 0, $preexistingCount));
                $possibleDuplicate = $near && $nearScore >= (float) $this->option('review');
                $live = $price > 0 && !$possibleDuplicate;
                $row['action'] = match (true) {
                    $possibleDuplicate => 'created-draft (possible duplicate — review)',
                    $price <= 0        => 'created-draft (price 0)',
                    default            => 'created',
                };
                if ($possibleDuplicate) {
                    $row['note'] = sprintf('similar to an existing product (%.0f%%) — activate if it is different, delete if it is the same', $nearScore * 100);
                    $row['matched_product'] = $this->describe($near['product']);
                }

                if (!$dry) {
                    $product = DB::transaction(fn () => Product::create([
                        'type'              => 'simple',
                        'name'              => $name,
                        'slug'              => $this->uniqueSlug($item['slug'] ?? $name),
                        'sku'               => $sku !== '' ? $sku : null,
                        'category_id'       => $categoryId,
                        'subcategory_id'    => $subcategoryId,
                        'brand_id'          => $this->resolveBrand($item),
                        'price'             => $price,
                        'sale_price'        => $sale,
                        'stock'             => $this->stock($item),
                        'short_description' => $this->text($item['short_description'] ?? '') ?: null,
                        'description'       => $this->cleanDescription($item['description'] ?? ''),
                        'image'             => $this->downloadImage($item['images'][0]['src'] ?? null),
                        'is_active'         => $live,
                        'is_featured'       => false,
                        'approval_status'   => 'approved',
                    ]));
                    if (!$product->image) {
                        $row['note'] = trim($row['note'] . '; image could not be downloaded', '; ');
                    }
                }
                // Later rows in this same run are checked against what was just added too
                // (also in a dry run, with an unsaved stand-in) — catches near-duplicates
                // inside the source and keeps SKUs unique.
                $added = $product ?? new Product(['name' => $name]);
                $fingerprints[] = ['product' => $added, 'fp' => $fp];
                if ($sku !== '') {
                    $bySku->put(mb_strtolower($sku), $added);
                }
                $product = null;
                $created++;
            } catch (\Throwable $e) {
                $row['action'] = 'error';
                $row['note'] = Str::limit($e->getMessage(), 200);
            }
            $this->tally($counts, $report, $row);
        }
        $bar->finish();
        $this->newLine(2);

        $reportPath = $this->writeReport($report, $dry);
        ksort($counts);
        $this->table(['Result', 'Products'], collect($counts)->map(fn ($n, $k) => [$k, $n])->values()->all());
        $this->line('Full report (every product, with matched duplicates): ' . Storage::disk('local')->path($reportPath));
        $this->info($dry ? 'Dry run — nothing was saved.' : 'Import finished.');

        return self::SUCCESS;
    }

    private function loadCatalog(string $url): array
    {
        $cacheFile = "imports/{$this->source}-catalog.json";
        if ($this->option('cache') && Storage::disk('local')->exists($cacheFile)) {
            $this->line('Using the cached catalog from the previous run.');
            $data = json_decode(Storage::disk('local')->get($cacheFile), true);

            return [$data['products'], $data['categories']];
        }

        $client = new WooCommerceStoreClient($url);
        $this->line('Downloading catalog…');
        $categories = $client->categories();
        $products = $client->products(fn ($page, $pages, $n) => $this->output->write("\r  page {$page}/{$pages} — {$n} products"));
        $this->newLine();
        Storage::disk('local')->put($cacheFile, json_encode(['products' => $products, 'categories' => $categories]));

        return [$products, $categories];
    }

    /** [price, sale price] in taka — the Store API reports minor units (paisa). */
    private function prices(array $item): array
    {
        $p = $item['prices'] ?? [];
        $div = 10 ** (int) ($p['currency_minor_unit'] ?? 2);
        $regular = round(((int) ($p['regular_price'] ?? $p['price'] ?? 0)) / $div, 2);
        $sale = round(((int) ($p['sale_price'] ?? 0)) / $div, 2);

        return [$regular, ($item['on_sale'] ?? false) && $sale > 0 && $sale < $regular ? $sale : null];
    }

    private function stock(array $item): int
    {
        if (!($item['is_in_stock'] ?? false)) {
            return 0;
        }

        return (int) ($item['low_stock_remaining'] ?? 0) ?: (int) $this->option('stock');
    }

    /** Decoded plain text (names, short descriptions). */
    private function text(?string $html): string
    {
        return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }

    /** Source description with links back into the source store unwrapped (text kept). */
    private function cleanDescription(?string $html): ?string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return null;
        }
        $host = preg_quote(preg_replace('/^www\./', '', $this->host), '#');

        return preg_replace('#<a\b[^>]*href=["\']https?://(?:www\.)?' . $host . '[^"\']*["\'][^>]*>(.*?)</a>#is', '$1', $html);
    }

    private function bestMatch(array $fp, array $fingerprints): array
    {
        $best = null;
        $score = 0.0;
        foreach ($fingerprints as $candidate) {
            if ($fp['key'] === $candidate['fp']['key']) {
                return [$candidate, 1.0];
            }
            $s = $this->matcher->similarity($fp, $candidate['fp']);
            if ($s > $score) {
                [$best, $score] = [$candidate, $s];
            }
        }

        return [$best, $score];
    }

    /** [category_id, subcategory_id, "Top > Sub"] — creating store categories the first time a source category is needed. */
    private function resolveCategory(array $item, bool $dry): array
    {
        $chosen = null;
        $depthOf = function (array $cat): int {
            $d = 0;
            while (($cat['parent'] ?? 0) && isset($this->catsById[$cat['parent']])) {
                $cat = $this->catsById[$cat['parent']];
                $d++;
            }

            return $d;
        };
        foreach ($item['categories'] ?? [] as $c) {
            $full = $this->catsById[$c['id']] ?? $c + ['parent' => 0];
            if (in_array(mb_strtolower($this->text($full['slug'] ?? $full['name'])), self::IGNORED_SOURCE_CATEGORIES, true)
                || in_array(mb_strtolower($this->text($full['name'])), self::IGNORED_SOURCE_CATEGORIES, true)) {
                continue;
            }
            if (!$chosen || $depthOf($full) > $depthOf($chosen)) {
                $chosen = $full;
            }
        }

        if (!$chosen) {
            return [$this->storeCategory('Others', null, $dry), null, 'Others'];
        }

        // ancestry: [top, level-1, (level-2…)] — the store keeps two levels
        $chain = [$chosen];
        while (($chain[0]['parent'] ?? 0) && isset($this->catsById[$chain[0]['parent']])) {
            array_unshift($chain, $this->catsById[$chain[0]['parent']]);
        }
        $topName = $this->text($chain[0]['name']);
        $subName = isset($chain[1]) ? $this->text($chain[1]['name']) : null;

        $topId = $this->storeCategory($topName, null, $dry);
        $subId = $subName ? $this->storeCategory($subName, $topId, $dry) : null;

        return [$topId, $subId, $topName . ($subName ? " > {$subName}" : '')];
    }

    private function storeCategory(string $name, ?int $parentId, bool $dry): ?int
    {
        $norm = fn ($s) => preg_replace('/[^\p{L}\p{N}]+/u', '', mb_strtolower($s));
        $key = ($parentId ?? 'top') . '|' . $norm($name);
        if (array_key_exists($key, $this->categoryCache)) {
            return $this->categoryCache[$key];
        }

        $query = Category::query()->where('parent_id', $parentId);
        if (!$parentId && isset(self::CATEGORY_ALIASES[mb_strtolower($name)])) {
            $alias = Category::whereNull('parent_id')->where('slug', self::CATEGORY_ALIASES[mb_strtolower($name)])->first();
            if ($alias) {
                return $this->categoryCache[$key] = $alias->id;
            }
        }
        $match = $query->get(['id', 'name'])->first(fn ($c) => $norm($c->name) === $norm($name));
        if ($match) {
            return $this->categoryCache[$key] = $match->id;
        }
        if ($dry) {
            return $this->categoryCache[$key] = null; // would be created
        }

        $slug = Str::slug($name) ?: 'category';
        $base = $slug;
        for ($i = 2; Category::where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }
        // New categories go after the existing ones, so the current header menu stays as it is.
        $category = Category::create([
            'name' => $name, 'slug' => $slug, 'parent_id' => $parentId, 'is_active' => true,
            'approval_status' => 'approved',
            'sort_order' => (int) Category::where('parent_id', $parentId)->max('sort_order') + 1,
        ]);

        return $this->categoryCache[$key] = $category->id;
    }

    private function brandName(array $item, bool $inferFromName = true): ?string
    {
        foreach ($item['brands'] ?? [] as $b) {
            return $this->text($b['name'] ?? '');
        }
        foreach ($item['attributes'] ?? [] as $attr) {
            if (mb_strtolower($attr['name'] ?? '') === 'brand' && !empty($attr['terms'][0]['name'])) {
                return $this->text($attr['terms'][0]['name']);
            }
        }
        if (!$inferFromName) {
            return null;
        }

        // Unbranded in the source: use a known brand the product name starts with
        // ("Horbaach Glutathione…" → Horbaach), longest name first so "Nature's Bounty"
        // wins over a hypothetical "Nature".
        // Whole words only: brand "Bio" must not claim "Biotin…".
        $name = $this->normalizeBrand($this->text($item['name'] ?? '')) . ' ';
        foreach ($this->knownBrands as $norm => $display) {
            if ($norm !== '' && str_starts_with($name, $norm . ' ')) {
                return $display;
            }
        }

        return null;
    }

    private function normalizeBrand(string $s): string
    {
        return trim(preg_replace('/[^\p{L}\p{N}]+/u', ' ', mb_strtolower(str_replace(['’', "'"], '', $s))));
    }

    /** Brands already in this store plus every brand the source names on any product. */
    private function collectKnownBrands(array $products): array
    {
        $names = Brand::pluck('name')->all();
        foreach ($products as $p) {
            if ($n = $this->brandName($p, false)) {
                $names[] = $n;
            }
        }
        $known = [];
        foreach ($names as $n) {
            $norm = $this->normalizeBrand($n);
            if (mb_strlen($norm) >= 3) {
                $known[$norm] ??= $n;
            }
        }
        uksort($known, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        return $known;
    }

    private function resolveBrand(array $item): ?int
    {
        $name = $this->brandName($item);
        if (!$name) {
            return null;
        }
        $norm = fn ($s) => preg_replace('/[^\p{L}\p{N}]+/u', '', mb_strtolower($s));
        if (array_key_exists($norm($name), $this->brandCache)) {
            return $this->brandCache[$norm($name)];
        }
        $brand = Brand::all(['id', 'name'])->first(fn ($b) => $norm($b->name) === $norm($name))
            ?? Brand::create(['name' => $name, 'slug' => $this->uniqueBrandSlug($name), 'is_active' => true]);

        return $this->brandCache[$norm($name)] = $brand->id;
    }

    private function uniqueBrandSlug(string $name): string
    {
        $slug = Str::slug($name) ?: 'brand';
        $base = $slug;
        for ($i = 2; Brand::where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }

    private function uniqueSlug(string $wanted): string
    {
        $slug = Str::slug($this->text($wanted)) ?: 'product';
        $base = $slug;
        for ($i = 2; isset($this->usedSlugs[$slug]); $i++) {
            $slug = "{$base}-{$i}";
        }
        $this->usedSlugs[$slug] = true;

        return $slug;
    }

    private function downloadImage(?string $url): ?string
    {
        if (!$url) {
            return null;
        }
        try {
            $response = Http::withHeaders(['User-Agent' => 'Mitavin catalog sync (partner import)'])->timeout(40)->retry(2, 1000)->get($url);
        } catch (\Throwable) {
            return null;
        }
        if (!$response->successful()) {
            return null;
        }
        $tmp = tempnam(sys_get_temp_dir(), 'catimg');
        file_put_contents($tmp, $response->body());
        try {
            $info = @getimagesize($tmp);
            if ($info === false) {
                return null;
            }
            $optimized = ImageOptimizer::optimize($tmp, $info['mime'], 1200, 82);
            $extension = $optimized['extension'] ?? match ($info['mime']) { 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif', default => 'jpg' };
            $path = 'products/' . Str::random(40) . '.' . $extension;
            Storage::disk('public')->put($path, $optimized['bytes'] ?? file_get_contents($tmp));

            return $path;
        } finally {
            @unlink($tmp);
        }
    }

    private function describe(Product $p): string
    {
        return "#{$p->id} {$p->name}";
    }

    private function tally(array &$counts, array &$report, array $row): void
    {
        $key = Str::before($row['action'], ' (');
        $counts[$key] = ($counts[$key] ?? 0) + 1;
        $report[] = $row;
    }

    private function writeReport(array $rows, bool $dry): string
    {
        $path = sprintf('imports/%s-%s%s.csv', $this->source, now()->format('Y-m-d_His'), $dry ? '-dry-run' : '');
        $fh = fopen('php://temp', 'w+');
        fwrite($fh, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel shows Bangla/symbols correctly
        fputcsv($fh, array_keys($rows[0] ?? ['action' => '']));
        foreach ($rows as $r) {
            fputcsv($fh, $r);
        }
        rewind($fh);
        Storage::disk('local')->put($path, stream_get_contents($fh));
        fclose($fh);

        return $path;
    }
}
