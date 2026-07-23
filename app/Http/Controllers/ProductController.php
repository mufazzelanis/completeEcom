<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function show(Product $product)
    {
        if ($product->redirect_url) {
            return redirect()->away($product->redirect_url, 301);
        }

        $product->increment('views');
        $product->load([
            'category', 'brand', 'images', 'reviews.user', 'faqs', 'activeFlashSaleProduct',
            'crossSells.recommended.activeFlashSaleProduct',
            'upsells.recommended.activeFlashSaleProduct',
        ]);

        if ($product->isBundle()) {
            $product->load('bundleItems.itemProduct');
        }

        $related = Product::active()
            ->with('activeFlashSaleProduct')
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->take(4)->get();

        $wishlisted = auth()->check()
            ? $product->wishlists()->where('user_id', auth()->id())->exists()
            : false;

        return view('products.show', compact('product', 'related', 'wishlisted'));
    }

    public function storeReview(Request $request, Product $product)
    {
        $request->validate([
            'rating'  => 'required|integer|between:1,5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $existing = Review::where('user_id', auth()->id())->where('product_id', $product->id)->first();

        if ($existing) {
            return back()->with('error', 'You have already reviewed this product.');
        }

        Review::create([
            'user_id'    => auth()->id(),
            'product_id' => $product->id,
            'rating'     => $request->rating,
            'comment'    => $request->comment,
            'is_approved' => false,
        ]);

        return back()->with('success', 'Review submitted and pending approval.');
    }
}
