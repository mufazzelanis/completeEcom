<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ImportProductsCsvJob;
use App\Models\BulkImport;
use App\Models\Category;
use Illuminate\Http\Request;

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
            // 512000 KB = 500MB — actually enforceable now that upload_max_filesize/post_max_size
            // are raised to 512M/520M (see public/.user.ini and the server's php.ini).
            'csv_file' => 'required|file|mimes:csv,txt|max:512000',
        ]);

        $file       = $request->file('csv_file');
        $storedPath = $file->store('imports', 'local');

        $import = BulkImport::create([
            'type'              => 'products',
            'original_filename' => $file->getClientOriginalName(),
            'stored_path'       => $storedPath,
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
            'status'          => $bulkImport->status,
            'total_rows'      => $bulkImport->total_rows,
            'processed_rows'  => $bulkImport->processed_rows,
            'created_count'   => $bulkImport->created_count,
            'skipped_count'   => $bulkImport->skipped_count,
            'progress_percent'=> $bulkImport->progressPercent(),
            'errors'          => $bulkImport->errors ?? [],
        ]);
    }
}
