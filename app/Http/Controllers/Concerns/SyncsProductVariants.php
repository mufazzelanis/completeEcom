<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductSize;
use App\Models\ProductVariantCombination;
use Illuminate\Http\Request;

/**
 * Shared by Admin\ProductController and Seller\ProductController: builds the
 * color/size attribute lists and the color x size combination matrix that
 * actually carries stock/price/sku for a variable product.
 */
trait SyncsProductVariants
{
    /**
     * @return array<int,int> submitted row index => saved ProductColor id
     */
    protected function syncProductColors(Product $product, array $rows, Request $request): array
    {
        $idsByIndex = [];
        $keptIds = [];

        foreach ($rows as $i => $row) {
            if (empty(trim($row['name'] ?? ''))) continue;

            $attrs = [
                'name'       => trim($row['name']),
                'hex_code'   => $row['hex_code'] ?? null ?: null,
                'sort_order' => $i,
                'is_active'  => !empty($row['is_active']),
            ];
            if ($request->hasFile("color_images.{$i}")) {
                $attrs['image'] = $request->file("color_images.{$i}")->store('products/colors', 'public');
            }

            if (!empty($row['id'])) {
                $color = ProductColor::where('id', $row['id'])->where('product_id', $product->id)->first();
                if ($color) {
                    if (empty($attrs['image'])) unset($attrs['image']);
                    $color->update($attrs);
                    $idsByIndex[$i] = $color->id;
                    $keptIds[] = $color->id;
                    continue;
                }
            }

            $color = ProductColor::create(array_merge($attrs, ['product_id' => $product->id]));
            $idsByIndex[$i] = $color->id;
            $keptIds[] = $color->id;
        }

        $product->colors()->whereNotIn('id', $keptIds)->delete();

        return $idsByIndex;
    }

    /**
     * @return array<int,int> submitted row index => saved ProductSize id
     */
    protected function syncProductSizes(Product $product, array $rows): array
    {
        $idsByIndex = [];
        $keptIds = [];

        foreach ($rows as $i => $row) {
            if (empty(trim($row['name'] ?? ''))) continue;

            $attrs = [
                'name'       => trim($row['name']),
                'sort_order' => $i,
                'is_active'  => !empty($row['is_active']),
            ];

            if (!empty($row['id'])) {
                $size = ProductSize::where('id', $row['id'])->where('product_id', $product->id)->first();
                if ($size) {
                    $size->update($attrs);
                    $idsByIndex[$i] = $size->id;
                    $keptIds[] = $size->id;
                    continue;
                }
            }

            $size = ProductSize::create(array_merge($attrs, ['product_id' => $product->id]));
            $idsByIndex[$i] = $size->id;
            $keptIds[] = $size->id;
        }

        $product->sizes()->whereNotIn('id', $keptIds)->delete();

        return $idsByIndex;
    }

    /**
     * Each row references its color/size by the index it was submitted at
     * (color_index/size_index), resolved here to the real ids syncProductColors/
     * syncProductSizes just saved. Also keeps products.stock in sync as the sum
     * of active combination stock, so every other stock-status query in the app
     * (filters, reports, low-stock notices) keeps working unchanged.
     */
    protected function syncProductCombinations(Product $product, array $rows, array $colorIdsByIndex, array $sizeIdsByIndex): void
    {
        $keptIds = [];

        foreach ($rows as $i => $row) {
            $colorIndex = $row['color_index'] ?? null;
            $sizeIndex = $row['size_index'] ?? null;
            $colorId = ($colorIndex !== null && $colorIndex !== '') ? ($colorIdsByIndex[(int) $colorIndex] ?? null) : null;
            $sizeId = ($sizeIndex !== null && $sizeIndex !== '') ? ($sizeIdsByIndex[(int) $sizeIndex] ?? null) : null;

            // Row's color and/or size got dropped server-side (e.g. blank name) — skip it.
            if ($colorId === null && $sizeId === null) continue;

            $attrs = [
                'product_color_id' => $colorId,
                'product_size_id'  => $sizeId,
                'sku'        => $row['sku'] ?? null ?: null,
                'price'      => $row['price'] ?? null ?: null,
                'stock'      => (int) ($row['stock'] ?? 0),
                'sort_order' => $i,
                'is_active'  => !empty($row['is_active']),
            ];

            if (!empty($row['id'])) {
                $combo = ProductVariantCombination::where('id', $row['id'])->where('product_id', $product->id)->first();
                if ($combo) {
                    $combo->update($attrs);
                    $keptIds[] = $combo->id;
                    continue;
                }
            }

            $keptIds[] = ProductVariantCombination::create(array_merge($attrs, ['product_id' => $product->id]))->id;
        }

        $product->combinations()->whereNotIn('id', $keptIds)->delete();

        if ($product->isVariable()) {
            $product->update(['stock' => (int) $product->combinations()->where('is_active', true)->sum('stock')]);
        }
    }

    /**
     * colors/sizes/combinations arrays ready for Js::from() on an edit form —
     * combinations reference color_index/size_index by position, matching what
     * the matrix builder JS and syncProductCombinations() above expect.
     */
    protected function variantMatrixJsData(Product $product): array
    {
        $colors = $product->colors()->get(['id', 'name', 'hex_code', 'is_active']);
        $sizes = $product->sizes()->get(['id', 'name', 'is_active']);

        $colorIndexById = $colors->values()->mapWithKeys(fn ($c, $i) => [$c->id => $i]);
        $sizeIndexById = $sizes->values()->mapWithKeys(fn ($s, $i) => [$s->id => $i]);

        $combinations = $product->combinations()->get()->map(fn ($combo) => [
            'id' => $combo->id,
            'color_index' => $combo->product_color_id !== null ? ($colorIndexById[$combo->product_color_id] ?? null) : null,
            'size_index' => $combo->product_size_id !== null ? ($sizeIndexById[$combo->product_size_id] ?? null) : null,
            'sku' => $combo->sku,
            'price' => $combo->price,
            'stock' => $combo->stock,
            'is_active' => $combo->is_active,
        ])->values();

        return [
            'colors' => $colors->map(fn ($c) => [
                'id' => $c->id, 'name' => $c->name, 'hex_code' => $c->hex_code, 'is_active' => $c->is_active,
            ])->values(),
            'sizes' => $sizes->map(fn ($s) => [
                'id' => $s->id, 'name' => $s->name, 'is_active' => $s->is_active,
            ])->values(),
            'combinations' => $combinations,
        ];
    }
}
