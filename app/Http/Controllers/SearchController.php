<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function suggest(Request $request)
    {
        $q = trim($request->get('q', ''));
        if (strlen($q) < 2) {
            return response()->json(['products' => [], 'categories' => []]);
        }

        $products = Product::active()
            ->where(fn($qb) => $qb
                ->where('name', 'like', "%$q%")
                ->orWhere('sku', 'like', "%$q%")
                ->orWhere('short_description', 'like', "%$q%")
            )
            ->limit(6)
            ->get(['id', 'name', 'slug', 'image', 'price', 'sale_price']);

        $categories = Category::where('is_active', true)
            ->where('name', 'like', "%$q%")
            ->limit(4)
            ->get(['id', 'name', 'slug']);

        return response()->json([
            'products' => $products->map(fn($p) => [
                'name'  => $p->name,
                'price' => '৳' . number_format($p->sale_price ?? $p->price),
                'image' => $p->image ? asset('storage/' . $p->image) : null,
                'url'   => route('products.show', $p->slug),
            ]),
            'categories' => $categories->map(fn($c) => [
                'name' => $c->name,
                'url'  => route('shop.category', $c->slug),
            ]),
        ]);
    }

    public function adminSuggest(Request $request)
    {
        $q = trim($request->get('q', ''));
        if (strlen($q) < 2) {
            return response()->json(['products' => [], 'orders' => [], 'customers' => [], 'categories' => []]);
        }

        $products = Product::where(fn($qb) => $qb
            ->where('name', 'like', "%$q%")
            ->orWhere('sku', 'like', "%$q%")
            ->orWhere('barcode', 'like', "%$q%")
        )
        ->limit(6)
        ->get(['id', 'name', 'sku', 'image', 'price', 'is_active']);

        $orders = Order::where(fn($qb) => $qb
            ->where('order_number', 'like', "%$q%")
            ->orWhere('shipping_name', 'like', "%$q%")
            ->orWhere('shipping_phone', 'like', "%$q%")
        )
        ->latest()
        ->limit(5)
        ->get(['id', 'order_number', 'status', 'total', 'shipping_name']);

        $customers = User::where(fn($qb) => $qb
            ->where('name', 'like', "%$q%")
            ->orWhere('email', 'like', "%$q%")
        )
        ->limit(5)
        ->get(['id', 'name', 'email', 'role']);

        $categories = Category::where('name', 'like', "%$q%")
            ->limit(4)
            ->get(['id', 'name']);

        $brands = Brand::where('name', 'like', "%$q%")
            ->limit(4)
            ->get(['id', 'name']);

        return response()->json([
            'products' => $products->map(fn($p) => [
                'name'      => $p->name,
                'sku'       => $p->sku ?? 'No SKU',
                'price'     => '৳' . number_format($p->price),
                'image'     => $p->image ? asset('storage/' . $p->image) : null,
                'is_active' => (bool) $p->is_active,
                'url'       => route('admin.products.edit', $p->id),
            ]),
            'orders' => $orders->map(fn($o) => [
                'order_number' => $o->order_number,
                'customer'     => $o->shipping_name,
                'status'       => $o->status,
                'total'        => '৳' . number_format($o->total, 2),
                'url'          => route('admin.orders.show', $o->id),
            ]),
            'customers' => $customers->map(fn($u) => [
                'name'  => $u->name,
                'email' => $u->email,
                'role'  => $u->role,
                'url'   => route('admin.users.edit', $u->id),
            ]),
            'categories' => $categories->map(fn($c) => [
                'name' => $c->name,
                'type' => 'Category',
                'url'  => route('admin.categories.edit', $c->id),
            ])->concat($brands->map(fn($b) => [
                'name' => $b->name,
                'type' => 'Brand',
                'url'  => route('admin.brands.edit', $b->id),
            ]))->values(),
        ]);
    }
}
