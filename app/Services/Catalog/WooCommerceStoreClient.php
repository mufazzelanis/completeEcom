<?php

namespace App\Services\Catalog;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Reads a WooCommerce store's public Store API (/wp-json/wc/store/v1) — products with
 * prices, categories, images, stock and descriptions, no API keys needed. Pages are fetched
 * politely (100 per request, a pause between them, retries on hiccups) since this runs
 * against a partner's live shop.
 */
class WooCommerceStoreClient
{
    public function __construct(private string $baseUrl, private int $pauseMs = 700)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /** @return array<int, array> every published product */
    public function products(?callable $progress = null): array
    {
        return $this->paginate('/wp-json/wc/store/v1/products', $progress);
    }

    /** @return array<int, array> every product category (id, name, slug, parent, count) */
    public function categories(): array
    {
        return $this->paginate('/wp-json/wc/store/v1/products/categories');
    }

    private function paginate(string $path, ?callable $progress = null): array
    {
        $all = [];
        $page = 1;
        $totalPages = 1;
        do {
            $response = Http::withHeaders(['User-Agent' => 'Mitavin catalog sync (partner import)', 'Accept' => 'application/json'])
                ->timeout(90)->retry(3, 2000)
                ->get($this->baseUrl . $path, ['per_page' => 100, 'page' => $page]);

            if (!$response->successful()) {
                throw new RuntimeException("Store API {$path} page {$page} returned HTTP {$response->status()}");
            }
            $items = $response->json();
            if (!is_array($items)) {
                throw new RuntimeException("Store API {$path} page {$page} did not return JSON");
            }

            $all = array_merge($all, $items);
            $totalPages = max(1, (int) $response->header('X-WP-TotalPages'));
            if ($progress) {
                $progress($page, $totalPages, count($all));
            }
            $page++;
            if ($page <= $totalPages) {
                usleep($this->pauseMs * 1000);
            }
        } while ($page <= $totalPages && $items !== []);

        return $all;
    }
}
