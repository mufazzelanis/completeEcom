<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    /**
     * Same guest-or-account split the cart already uses — a session_id-scoped
     * wishlist for anyone not logged in, so favoriting never demands an
     * account up front. Only checkout still requires one.
     */
    private function getWishlistQuery()
    {
        if (auth()->check()) {
            return Wishlist::where('user_id', auth()->id());
        }

        return Wishlist::where('session_id', session()->getId());
    }

    public function index()
    {
        $wishlists = $this->getWishlistQuery()->with('product.activeFlashSaleProduct')->get();
        return view('wishlist.index', compact('wishlists'));
    }

    public function toggle(Request $request, Product $product)
    {
        $wishlist = $this->getWishlistQuery()->where('product_id', $product->id)->first();

        if ($wishlist) {
            $wishlist->delete();
            $message = 'Removed from wishlist.';
        } else {
            Wishlist::create(array_filter([
                'user_id'    => auth()->id(),
                'session_id' => auth()->check() ? null : session()->getId(),
                'product_id' => $product->id,
            ], fn ($v) => $v !== null));
            $message = 'Added to wishlist!';
        }

        if ($request->expectsJson()) {
            return response()->json([
                'status'  => $wishlist ? 'removed' : 'added',
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }
}
