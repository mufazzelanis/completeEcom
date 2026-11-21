<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use App\Support\PageView;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function show(Product $product)
    {
        if ($product->redirect_url) {
            return redirect()->away($product->redirect_url, 301);
        }

        $isOwningVendor = auth()->check() && auth()->user()->vendor && $product->seller_id === auth()->user()->vendor->id;
        if (!$product->isApproved() && !$isOwningVendor) {
            abort(404);
        }

        // Shared with the client-side fbq('track', 'ViewContent', ..., {eventID}) call in
        // products/show.blade.php so Meta dedupes this pixel+CAPI pair into one event.
        $fbViewContentEventId = (string) Str::uuid();
        // View counter, activity log, recently-viewed history and the CAPI ViewContent —
        // now, or (Turbo hover-prefetch) only once the page is actually shown; see PageView.
        $pageViewToken = PageView::record('product', $product->id, ['event' => $fbViewContentEventId]);

        // .recommended's own reviews/brand are needed too — partials/product-card.blade.php
        // reads both on every card it renders (star rating, brand name), and without this
        // each recommended product in these two sections lazy-loaded both separately.
        $product->load([
            'category', 'brand', 'images', 'reviews.user', 'faqs', 'activeFlashSaleProduct',
            'crossSells.recommended.activeFlashSaleProduct',
            'crossSells.recommended.reviews',
            'crossSells.recommended.brand',
            'upsells.recommended.activeFlashSaleProduct',
            'upsells.recommended.reviews',
            'upsells.recommended.brand',
        ]);

        if ($product->isBundle()) {
            $product->load('bundleItems.itemProduct');
        }

        if ($product->isVariable()) {
            $product->load(['colors', 'sizes', 'combinations.color', 'combinations.size']);
        }

        // 'reviews'/'brand' are needed by every product-card partial for its star
        // rating and brand name — without them each card lazy-loads its own queries.
        $related = Product::active()
            ->with('activeFlashSaleProduct', 'reviews', 'brand', 'category')
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->take(4)->get();

        $wishlisted = auth()->check()
            ? $product->wishlists()->where('user_id', auth()->id())->exists()
            : $product->wishlists()->where('session_id', session()->getId())->exists();

        // Bundle box only for a product that itself goes to the cart in one tap
        $fbt = !$product->isVariable() && !$product->isDigital() && $product->available_stock > 0
            ? \App\Services\Personalization::frequentlyBoughtTogether($product)
            : collect();
        $recentlyViewed = \App\Services\Personalization::recentlyViewed($product->id, 12)
            ->reject(fn ($p) => $related->contains('id', $p->id))->values();

        return view('products.show', compact('product', 'related', 'wishlisted', 'fbViewContentEventId', 'fbt', 'recentlyViewed', 'pageViewToken'));
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

        // The check above and this create() aren't atomic — two near-simultaneous submits
        // (a double-click, two open tabs) could both pass it. A unique index on
        // (user_id, product_id) makes the database itself the real guard; this only
        // turns the second one's failure into the same friendly message instead of a
        // raw 500, rather than trying to prevent it from racing in the first place.
        try {
            Review::create([
                'user_id'    => auth()->id(),
                'product_id' => $product->id,
                'rating'     => $request->rating,
                'comment'    => $request->comment,
                'is_approved' => false,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            if ((int) $e->getCode() === 23000) {
                return back()->with('error', 'You have already reviewed this product.');
            }
            throw $e;
        }

        return back()->with('success', 'Review submitted and pending approval.');
    }
}
