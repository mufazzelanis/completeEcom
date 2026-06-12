<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BundleItem;
use App\Models\Product;
use Illuminate\Http\Request;

class BundleController extends Controller
{
    public function index()
    {
        $bundles = Product::where('type', 'bundle')
            ->withCount('bundleItems')
            ->with('category')
            ->latest()
            ->paginate(20);

        return view('admin.bundles.index', compact('bundles'));
    }

    public function manage(Product $product)
    {
        abort_unless($product->isBundle(), 404);
        $product->load('bundleItems.itemProduct.category');
        $allProducts = Product::where('is_active', true)
            ->where('id', '!=', $product->id)
            ->whereIn('type', ['simple', 'variable'])
            ->orderBy('name')
            ->get(['id', 'name', 'price', 'sale_price', 'image']);

        return view('admin.bundles.manage', compact('product', 'allProducts'));
    }

    public function addItem(Request $request, Product $product)
    {
        abort_unless($product->isBundle(), 404);

        $data = $request->validate([
            'item_product_id' => 'required|exists:products,id|different:product_id',
            'quantity'        => 'required|integer|min:1',
            'discount_pct'    => 'nullable|numeric|min:0|max:100',
        ]);

        BundleItem::updateOrCreate(
            ['bundle_product_id' => $product->id, 'item_product_id' => $data['item_product_id']],
            ['quantity' => $data['quantity'], 'discount_pct' => $data['discount_pct'] ?? 0]
        );

        return back()->with('success', 'Item added to bundle.');
    }

    public function removeItem(Product $product, BundleItem $item)
    {
        abort_unless($item->bundle_product_id === $product->id, 403);
        $item->delete();
        return back()->with('success', 'Item removed.');
    }

    public function updateItem(Request $request, Product $product, BundleItem $item)
    {
        abort_unless($item->bundle_product_id === $product->id, 403);

        $data = $request->validate([
            'quantity'     => 'required|integer|min:1',
            'discount_pct' => 'nullable|numeric|min:0|max:100',
        ]);

        $item->update($data);
        return back()->with('success', 'Item updated.');
    }
}
