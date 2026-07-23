<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ImportProductsCsvJob;
use App\Models\BulkImport;
use App\Models\Brand;
use App\Models\Category;
use Illuminate\Http\Request;

class BulkProductController extends Controller
{
    public function index()
    {
        $categories = Category::whereNull('parent_id')->with(['children' => fn ($q) => $q->orderBy('name')])->orderBy('name')->get(['id', 'name', 'parent_id']);
        $brands = Brand::orderBy('name')->get(['id', 'name']);
        return view('admin.products.bulk_upload', compact('categories', 'brands'));
    }

    public function template()
    {
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="product_import_template.csv"',
        ];

        $rows = [
            [
                'name', 'sku', 'category_name', 'subcategory_name', 'brand_name',
                'price', 'sale_price', 'stock', 'weight', 'barcode', 'low_stock_threshold',
                'image_filename', 'short_description', 'description',
                'meta_title', 'meta_description', 'is_active', 'is_featured',
            ],
            [
                'Wireless Headphones', 'SKU001', 'Electronics', 'Headphones', 'Sony',
                '2999.00', '2499.00', '50', '0.35', '8901234567890', '5',
                'wireless-headphones.jpg', 'Noise-cancelling wireless headphones', 'Full description here',
                'Buy Wireless Headphones Online', 'Best noise-cancelling headphones at a great price', '1', '0',
            ],
            [
                'Cotton T-Shirt', 'SKU002', 'Fashion', '', '',
                '799.00', '', '100', '0.2', '', '',
                '', '', '',
                '', '', '1', '1',
            ],
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
            // 512000 KB = 500MB — actually enforceable now that upload_max_filesize/post_max_size
            // are raised to 512M/520M (see public/.user.ini and the server's php.ini).
            'csv_file'    => 'required|file|mimes:csv,txt|max:512000',
            'images_zip'  => 'nullable|file|mimes:zip|max:512000',
        ]);

        $file       = $request->file('csv_file');
        $storedPath = $file->store('imports', 'local');

        $imagesZipPath = null;
        if ($request->hasFile('images_zip')) {
            $imagesZipPath = $request->file('images_zip')->store('imports/images', 'local');
        }

        $import = BulkImport::create([
            'type'              => 'products',
            'original_filename' => $file->getClientOriginalName(),
            'stored_path'       => $storedPath,
            'images_zip_path'   => $imagesZipPath,
            'status'            => 'queued',
            'user_id'           => auth()->id(),
        ]);

        ImportProductsCsvJob::dispatch($import->id);

        return redirect()->route('admin.products.bulk-upload.status', $import);
    }

    public function status(BulkImport $bulkImport)
    {
        return view('admin.products.bulk_upload_status', ['import' => $bulkImport]);
    }

    public function statusData(BulkImport $bulkImport)
    {
        return response()->json([
            'status'               => $bulkImport->status,
            'total_rows'           => $bulkImport->total_rows,
            'processed_rows'       => $bulkImport->processed_rows,
            'created_count'        => $bulkImport->created_count,
            'skipped_count'        => $bulkImport->skipped_count,
            'images_matched_count' => $bulkImport->images_matched_count,
            'images_missing_count' => $bulkImport->images_missing_count,
            'progress_percent'     => $bulkImport->progressPercent(),
            'errors'               => $bulkImport->errors ?? [],
        ]);
    }
}
