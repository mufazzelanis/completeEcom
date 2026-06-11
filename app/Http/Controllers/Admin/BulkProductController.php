<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BulkProductController extends Controller
{
    public function index()
    {
        $categories = Category::whereNull('parent_id')->orderBy('name')->get(['id', 'name']);
        return view('admin.products.bulk_upload', compact('categories'));
    }

    public function template()
    {
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="product_import_template.csv"',
        ];

        $rows = [
            ['name', 'sku', 'category_name', 'price', 'sale_price', 'stock', 'short_description', 'description', 'is_active', 'is_featured'],
            ['Sample Product', 'SKU001', 'Electronics', '999.00', '799.00', '50', 'Short desc here', 'Full description here', '1', '0'],
            ['Another Product', 'SKU002', 'Fashion', '299.00', '', '100', '', '', '1', '1'],
        ];

        $callback = function () use ($rows) {
            $handle = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function import(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $file   = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');

        $header  = fgetcsv($handle); // skip header row
        $results = ['created' => 0, 'skipped' => 0, 'errors' => []];
        $row     = 1;

        // Cache category name → id map
        $catMap = Category::whereNull('parent_id')
            ->get(['id', 'name'])
            ->keyBy(fn($c) => strtolower(trim($c->name)))
            ->map(fn($c) => $c->id)
            ->toArray();

        while (($data = fgetcsv($handle)) !== false) {
            $row++;
            if (count($data) < 4) {
                $results['errors'][] = "Row {$row}: too few columns.";
                $results['skipped']++;
                continue;
            }

            [$name, $sku, $catName, $price, $salePrice, $stock, $shortDesc, $desc, $isActive, $isFeatured]
                = array_pad($data, 10, '');

            $name  = trim($name);
            $price = trim($price);

            if (empty($name) || !is_numeric($price)) {
                $results['errors'][] = "Row {$row}: name or price missing/invalid.";
                $results['skipped']++;
                continue;
            }

            $catId = $catMap[strtolower(trim($catName))] ?? null;
            if (!$catId) {
                $results['errors'][] = "Row {$row}: category '{$catName}' not found — skipped.";
                $results['skipped']++;
                continue;
            }

            $slug = Str::slug($name);
            $base = $slug;
            $i    = 1;
            while (Product::where('slug', $slug)->exists()) {
                $slug = $base . '-' . $i++;
            }

            Product::create([
                'name'              => $name,
                'slug'              => $slug,
                'sku'               => $sku ?: null,
                'category_id'       => $catId,
                'price'             => $price,
                'sale_price'        => is_numeric(trim($salePrice)) && trim($salePrice) !== '' ? trim($salePrice) : null,
                'stock'             => is_numeric(trim($stock)) ? (int) trim($stock) : 0,
                'short_description' => trim($shortDesc) ?: null,
                'description'       => trim($desc) ?: null,
                'is_active'         => trim($isActive) !== '0',
                'is_featured'       => trim($isFeatured) === '1',
            ]);

            $results['created']++;
        }

        fclose($handle);

        $msg = "Import complete: {$results['created']} created, {$results['skipped']} skipped.";
        if ($results['errors']) {
            return redirect()->route('admin.products.bulk-upload')
                ->with('warning', $msg)
                ->with('import_errors', $results['errors']);
        }

        return redirect()->route('admin.products.index')->with('success', $msg);
    }
}
