<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use App\Services\ActivityLogger;
use App\Services\Facebook\ConversionsApi;
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

        $product->increment('views');
        ActivityLogger::log('product.view', "Viewed product: {$product->name}", $product);
        $product->load([
            'category', 'brand', 'images', 'reviews.user', 'faqs', 'activeFlashSaleProduct',
            'crossSells.recommended.activeFlashSaleProduct',
            'upsells.recommended.activeFlashSaleProduct',
        ]);

        if ($product->isBundle()) {
            $product->load('bundleItems.itemProduct');
        }

        if ($product->isVariable()) {
            $product->load(['colors', 'sizes', 'combinations.color', 'combinations.size']);
        }

        $related = Product::active()
            ->with('activeFlashSaleProduct')
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->take(4)->get();

        $wishlisted = auth()->check()
            ? $product->wishlists()->where('user_id', auth()->id())->exists()
            : false;

        // Shared with the client-side fbq('track', 'ViewContent', ..., {eventID}) call in
        // products/show.blade.php so Meta dedupes this pixel+CAPI pair into one event.
        $fbViewContentEventId = (string) Str::uuid();
        $user = auth()->user();
        ConversionsApi::track(
            eventName: 'ViewContent',
            eventId: $fbViewContentEventId,
            customData: [
                // Matches the client-side fbq call's own `id` value exactly (see
                // products/show.blade.php) — raw product ID, not SKU.
                'content_ids'      => [(string) $product->id],
                'content_type'     => 'product',
                'content_name'     => $product->name,
                'content_category' => $product->category->name ?? null,
                'value'            => (float) $product->final_price,
                'currency'         => setting('currency_code', 'BDT'),
            ],
            rawUserFields: $user ? ['name' => $user->name, 'email' => $user->email, 'phone' => $user->phone] : [],
        );

        return view('products.show', compact('product', 'related', 'wishlisted', 'fbViewContentEventId'));
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
