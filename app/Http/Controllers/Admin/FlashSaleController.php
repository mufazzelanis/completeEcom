<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FlashSale;
use App\Models\FlashSaleProduct;
use App\Models\Product;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class FlashSaleController extends Controller
{
    public function index()
    {
        $sales = FlashSale::withCount('products')->latest()->paginate(15);
        return view('admin.flash-sales.index', compact('sales'));
    }

    public function create()
    {
        return view('admin.flash-sales.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'         => 'required|string|max:255',
            'description'  => 'nullable|string',
            'banner_text'  => 'nullable|string|max:255',
            'banner_color' => 'nullable|string|max:20',
            'starts_at'    => 'required|date',
            'ends_at'      => 'required|date|after:starts_at',
            'is_active'    => 'boolean',
        ]);

        $sale = FlashSale::create($data);
        AuditLogger::log('flash_sale.created', "Flash sale \"{$sale->name}\" created", $sale, [], $data);

        return redirect()->route('admin.flash-sales.edit', $sale)->with('success', 'Flash sale created. Now add products.');
    }

    public function edit(FlashSale $flashSale)
    {
        $flashSale->load('products.product.category');
        $products = Product::where('is_active', true)->orderBy('name')->get(['id', 'name', 'price', 'sale_price', 'image']);
        return view('admin.flash-sales.edit', compact('flashSale', 'products'));
    }

    public function update(Request $request, FlashSale $flashSale)
    {
        $data = $request->validate([
            'name'         => 'required|string|max:255',
            'description'  => 'nullable|string',
            'banner_text'  => 'nullable|string|max:255',
            'banner_color' => 'nullable|string|max:20',
            'starts_at'    => 'required|date',
            'ends_at'      => 'required|date|after:starts_at',
            'is_active'    => 'boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active');

        $flashSale->update($data);
        AuditLogger::log('flash_sale.updated', "Flash sale \"{$flashSale->name}\" updated", $flashSale);

        return back()->with('success', 'Flash sale updated.');
    }

    public function destroy(FlashSale $flashSale)
    {
        AuditLogger::log('flash_sale.deleted', "Flash sale \"{$flashSale->name}\" deleted");
        $flashSale->delete();
        return redirect()->route('admin.flash-sales.index')->with('success', 'Flash sale deleted.');
    }

    public function addProduct(Request $request, FlashSale $flashSale)
    {
        $data = $request->validate([
            'product_id'    => 'required|exists:products,id',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value'=> 'required|numeric|min:0.01',
            'stock_limit'   => 'nullable|integer|min:0',
        ]);

        FlashSaleProduct::updateOrCreate(
            ['flash_sale_id' => $flashSale->id, 'product_id' => $data['product_id']],
            ['discount_type' => $data['discount_type'], 'discount_value' => $data['discount_value'], 'stock_limit' => $data['stock_limit'] ?? 0]
        );

        return back()->with('success', 'Product added to flash sale.');
    }

    public function removeProduct(FlashSale $flashSale, FlashSaleProduct $product)
    {
        $product->delete();
        return back()->with('success', 'Product removed.');
    }
}
