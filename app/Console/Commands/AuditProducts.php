<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * Written after a bulk batch of new products went live and the admin wanted to know,
 * before Google ever crawls them, whether any look like accidental duplicates or are
 * missing the on-page SEO basics (meta title/description, image, a real description) —
 * all four are either "two URLs Google will flag as duplicate content" or "a thinner page
 * than it should be" problems, neither visible just from scrolling the product list.
 */
class AuditProducts extends Command
{
    protected $signature = 'products:audit
        {--recent= : Only check products created in the last N days}
        {--limit=15 : Max example rows to print per section}';

    protected $description = 'Check for duplicate products and common SEO issues (missing/duplicate titles, descriptions, images).';

    public function handle(): int
    {
        $query = Product::query();
        if ($days = $this->option('recent')) {
            $query->where('created_at', '>=', now()->subDays((int) $days));
        }
        $products = $query->get();
        $limit = (int) $this->option('limit');

        $this->info("Checking {$products->count()} product(s)" . ($days ? " created in the last {$days} day(s)" : '') . '...');
        $this->newLine();

        // Grouped against the WHOLE catalog (not just --recent) for anything comparing one
        // product's field against another's — a brand-new product can "duplicate" an old
        // one just as easily as duplicating another new one, and only checking within the
        // recent batch would miss that half of the comparison.
        $allProducts = $days ? Product::all() : $products;
        $recentIds = $products->pluck('id')->flip();
        $touchesRecent = fn (Collection $group) => $recentIds->isEmpty() || $group->contains(fn ($p) => $recentIds->has($p->id));

        $this->section(
            'Duplicate product names (likely accidental re-imports)',
            $allProducts->groupBy(fn ($p) => mb_strtolower(trim((string) $p->name)))->filter(fn ($g) => $g->count() > 1 && $touchesRecent($g)),
            fn ($g) => $g->map(fn ($p) => "#{$p->id} {$p->name} ({$p->slug})")->implode("\n      "),
            $limit
        );

        $this->section(
            'Duplicate meta titles (Google treats these as duplicate pages)',
            $allProducts->filter(fn ($p) => filled($p->meta_title))->groupBy(fn ($p) => mb_strtolower(trim($p->meta_title)))->filter(fn ($g) => $g->count() > 1 && $touchesRecent($g)),
            fn ($g) => $g->map(fn ($p) => "#{$p->id} {$p->name}")->implode("\n      "),
            $limit
        );

        $this->section(
            'Duplicate meta descriptions',
            $allProducts->filter(fn ($p) => filled($p->meta_description))->groupBy(fn ($p) => mb_strtolower(trim($p->meta_description)))->filter(fn ($g) => $g->count() > 1 && $touchesRecent($g)),
            fn ($g) => $g->map(fn ($p) => "#{$p->id} {$p->name}")->implode("\n      "),
            $limit
        );

        $this->listIssue('Missing meta title', $products->filter(fn ($p) => blank($p->meta_title)), $limit);
        $this->listIssue('Missing meta description', $products->filter(fn ($p) => blank($p->meta_description)), $limit);
        $this->listIssue('No image', $products->filter(fn ($p) => blank($p->image)), $limit);
        $this->listIssue(
            'Thin description (under 50 characters once HTML/tags are stripped)',
            $products->filter(fn ($p) => mb_strlen(trim(strip_tags((string) $p->description))) < 50),
            $limit
        );
        $this->listIssue(
            'Marked noindex (hidden from Google on purpose, or by mistake?)',
            $products->where('noindex', true),
            $limit
        );

        $this->newLine();
        $this->info('Done. "Duplicate ___" sections are the ones that actually risk a Google penalty; the rest are on-page gaps worth filling in but not urgent.');

        return self::SUCCESS;
    }

    /** @param \Illuminate\Support\Collection<int, \Illuminate\Support\Collection<int, Product>> $groups */
    private function section(string $title, Collection $groups, \Closure $format, int $limit): void
    {
        if ($groups->isEmpty()) {
            $this->line("<fg=green>✓</> {$title}: none found");
            return;
        }

        $this->line("<fg=red>✗</> {$title}: " . $groups->count() . ' group(s)');
        foreach ($groups->take($limit) as $group) {
            $this->line('   - ' . $format($group));
        }
        if ($groups->count() > $limit) {
            $this->line('   ... and ' . ($groups->count() - $limit) . ' more group(s)');
        }
        $this->newLine();
    }

    /** @param \Illuminate\Support\Collection<int, Product> $items */
    private function listIssue(string $title, Collection $items, int $limit): void
    {
        if ($items->isEmpty()) {
            $this->line("<fg=green>✓</> {$title}: none found");
            return;
        }

        $this->line("<fg=yellow>!</> {$title}: " . $items->count() . ' product(s)');
        foreach ($items->take($limit) as $p) {
            $this->line("   - #{$p->id} {$p->name} ({$p->slug})");
        }
        if ($items->count() > $limit) {
            $this->line('   ... and ' . ($items->count() - $limit) . ' more');
        }
        $this->newLine();
    }
}
