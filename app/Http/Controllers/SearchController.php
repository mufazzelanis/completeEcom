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

        // Same matching as the shop page (Product::scopeSearch — every word, any order, brand,
        // spelling variants), best matches first; closest matches if nothing matches every word.
        $columns = ['products.id', 'products.name', 'products.slug', 'products.image', 'products.price', 'products.sale_price'];
        $products = Product::active()->search($q)->orderByRelevance($q)->limit(6)->get($columns);
        if ($products->isEmpty()) {
            $products = Product::active()->search($q, loose: true)->orderByRelevance($q)->limit(6)->get($columns);
        }

        $categories = Category::where('is_active', true)
            ->where('name', 'like', "%$q%")
            ->limit(4)
            ->get(['id', 'name', 'slug']);

        return response()->json([
            'products' => $products->map(fn($p) => self::productRow($p)),
            'categories' => $categories->map(fn($c) => [
                'name' => $c->name,
                'url'  => route('shop.category', $c->slug),
            ]),
        ]);
    }

    /** One product as the search dropdown shows it (name, price, regular price when on sale, thumb, link). */
    private static function productRow(Product $p): array
    {
        $now = (float) ($p->sale_price && $p->sale_price < $p->price ? $p->sale_price : $p->price);

        return [
            'name'  => $p->name,
            'price' => '৳' . number_format($now),
            'was'   => $now < (float) $p->price ? '৳' . number_format((float) $p->price) : null,
            'off'   => $now < (float) $p->price && $p->price > 0 ? (int) round(100 - $now / $p->price * 100) : null,
            'image' => $p->image ? image_thumb($p->image, 96) : null,
            'url'   => route('products.show', $p->slug),
        ];
    }

    /**
     * What the search box shows before anything is typed (and types out in its placeholder):
     * what shoppers search for most (Search history, last 30 days) and the products they open
     * most. Same for everyone, so cached for an hour and cacheable by the browser.
     */
    public function trending()
    {
        $data = \Illuminate\Support\Facades\Cache::remember('search:trending', 3600, function () {
            $searches = \App\Models\SearchQuery::where('searched_at', '>=', now()->subDays(30))
                ->selectRaw('LOWER(TRIM(`query`)) as q, COUNT(*) as n')
                ->groupBy('q')->having('n', '>=', 2)->orderByDesc('n')->limit(10)->pluck('q')
                ->filter(fn ($q) => mb_strlen($q) >= 3 && mb_strlen($q) <= 40)->values();

            $products = Product::active()->where('stock', '>', 0)->whereNotNull('image')
                ->orderByDesc('views')->limit(8)
                ->get(['products.id', 'products.name', 'products.slug', 'products.image', 'products.price', 'products.sale_price']);

            // a placeholder fits ~35 characters: the first words of a long product name
            $short = fn (string $name) => \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/', ' ', $name)), 34, '');

            return [
                'searches'     => $searches->all(),
                'products'     => $products->take(4)->map(fn ($p) => self::productRow($p))->values()->all(),
                'placeholders' => $searches->take(4)->merge($products->take(6)->map(fn ($p) => $short($p->name)))
                    ->unique()->shuffle()->values()->all(),
            ];
        });

        return response()->json($data)->header('Cache-Control', 'public, max-age=600');
    }

    /** Admin header quick-search: the list of admin pages, fetched once per session. */
    public function adminNavIndex()
    {
        return response()->json(\App\Support\AdminNavIndex::all())->header('Cache-Control', 'private, max-age=3600');
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

        // Phone included both in the search and the returned columns — an admin creating a
        // manual/phone order (Admin > Orders > Create Order) is usually looking a customer
        // up by the number they're calling from, not their name or email.
        $customers = User::where(fn($qb) => $qb
            ->where('name', 'like', "%$q%")
            ->orWhere('email', 'like', "%$q%")
            ->orWhere('phone', 'like', "%$q%")
        )
        ->limit(5)
        ->get(['id', 'name', 'email', 'phone', 'role']);

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
                'image'     => $p->image ? image_thumb($p->image, 96) : null,
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
                'id'    => $u->id,
                'name'  => $u->name,
                'email' => $u->email,
                'phone' => $u->phone,
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
