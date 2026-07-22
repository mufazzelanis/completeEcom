<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index()
    {
        $wishlists = Wishlist::where('user_id', auth()->id())->with('product.activeFlashSaleProduct')->get();
        return view('wishlist.index', compact('wishlists'));
    }

    public function toggle(Request $request, Product $product)
    {
        $wishlist = Wishlist::where('user_id', auth()->id())
            ->where('product_id', $product->id)
            ->first();

        if ($wishlist) {
            $wishlist->delete();
            $message = 'Removed from wishlist.';
        } else {
            Wishlist::create(['user_id' => auth()->id(), 'product_id' => $product->id]);
            $message = 'Added to wishlist!';
        }

        if ($request->expectsJson()) {
            return response()->json([
                'status' => $wishlist ? 'removed' : 'added',
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }
}
