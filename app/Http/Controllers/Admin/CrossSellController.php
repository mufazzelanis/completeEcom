<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductRecommendation;
use Illuminate\Http\Request;

class CrossSellController extends Controller
{
    public function index()
    {
        $products = Product::where('is_active', true)
            ->withCount([
                'recommendations as cross_sell_count' => fn($q) => $q->where('type', 'cross_sell'),
                'recommendations as upsell_count'     => fn($q) => $q->where('type', 'upsell'),
            ])
            ->having('cross_sell_count', '>', 0)
            ->orHaving('upsell_count', '>', 0)
            ->orderByDesc('cross_sell_count')
            ->paginate(20);

        $totalWithRecs = Product::whereHas('recommendations')->count();

        return view('admin.cross-sell.index', compact('products', 'totalWithRecs'));
    }

    public function manage(Product $product)
    {
        $crossSells = ProductRecommendation::where('product_id', $product->id)
            ->where('type', 'cross_sell')
            ->with('recommended.category')
            ->orderBy('sort_order')
            ->get();

        $upsells = ProductRecommendation::where('product_id', $product->id)
            ->where('type', 'upsell')
            ->with('recommended.category')
            ->orderBy('sort_order')
            ->get();

        $existingIds = $crossSells->pluck('recommended_product_id')
            ->merge($upsells->pluck('recommended_product_id'))
            ->merge([$product->id]);

        $allProducts = Product::where('is_active', true)
            ->whereNotIn('id', $existingIds)
            ->orderBy('name')
            ->get(['id', 'name', 'price', 'sale_price', 'image']);

        return view('admin.cross-sell.manage', compact('product', 'crossSells', 'upsells', 'allProducts'));
    }

    public function store(Request $request, Product $product)
    {
        $data = $request->validate([
            'recommended_product_id' => 'required|exists:products,id|different:id',
            'type'                   => 'required|in:cross_sell,upsell',
        ]);

        ProductRecommendation::firstOrCreate([
            'product_id'             => $product->id,
            'recommended_product_id' => $data['recommended_product_id'],
            'type'                   => $data['type'],
        ]);

        return back()->with('success', ucfirst(str_replace('_', '-', $data['type'])) . ' added.');
    }

    public function destroy(Product $product, ProductRecommendation $recommendation)
    {
        abort_unless($recommendation->product_id === $product->id, 403);
        $recommendation->delete();
        return back()->with('success', 'Recommendation removed.');
    }
}
