<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FlashSale;
use App\Models\FlashSaleProduct;
use App\Models\Product;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class FlashSaleController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'all');

        $sales = FlashSale::withCount('products')
            ->withSum('products as units_sold', 'sold_count')
            ->when($status === 'live', fn ($q) => $q->live())
            ->when($status === 'upcoming', fn ($q) => $q->upcoming())
            ->when($status === 'ended', fn ($q) => $q->where('is_active', true)->where('ends_at', '<', now()))
            ->when($status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderByRaw('CASE WHEN is_active = 1 AND starts_at <= ? AND ends_at >= ? THEN 0 WHEN is_active = 1 AND starts_at > ? THEN 1 ELSE 2 END', [now(), now(), now()])
            ->orderByDesc('starts_at')
            ->paginate(15)
            ->withQueryString();

        // Aggregated in SQL (same formula as FlashSaleProduct::sale_price) so this stays one
        // query however many deals have ever run.
        $totals = FlashSaleProduct::query()
            ->join('products', 'products.id', '=', 'flash_sale_products.product_id')
            ->where('flash_sale_products.sold_count', '>', 0)
            ->selectRaw('SUM(flash_sale_products.sold_count) AS units')
            ->selectRaw("SUM(flash_sale_products.sold_count * GREATEST(0, CASE WHEN flash_sale_products.discount_type = 'percentage'
                THEN COALESCE(products.sale_price, products.price) * (1 - flash_sale_products.discount_value / 100)
                ELSE COALESCE(products.sale_price, products.price) - flash_sale_products.discount_value END)) AS revenue")
            ->first();
        $stats = [
            'live'     => FlashSale::live()->count(),
            'upcoming' => FlashSale::upcoming()->count(),
            'units'    => (int) $totals->units,
            'revenue'  => (float) $totals->revenue,
        ];

        return view('admin.flash-sales.index', compact('sales', 'stats', 'status'));
    }

    public function create()
    {
        return view('admin.flash-sales.create', ['flashSale' => new FlashSale([
            'theme' => 'inferno', 'layout' => 'carousel', 'home_limit' => 12, 'banner_color' => '#ef4444',
            'show_on_home' => true, 'show_upcoming' => true, 'show_stock_bar' => true, 'is_active' => true,
        ])]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $sale = FlashSale::create($data);
        AuditLogger::log('flash_sale.created', "Flash sale \"{$sale->name}\" created", $sale, [], $data);

        return redirect()->route('admin.flash-sales.edit', $sale)->with('success', 'Flash sale created. Now add products.');
    }

    public function edit(FlashSale $flashSale)
    {
        $flashSale->load('products.product.category');

        return view('admin.flash-sales.edit', compact('flashSale'));
    }

    /** Product picker for "Add deals" — searched server-side so the page never ships the whole catalogue. */
    public function searchProducts(Request $request, FlashSale $flashSale)
    {
        $q = trim((string) $request->query('q', ''));

        $products = Product::where('is_active', true)
            ->whereNotIn('id', $flashSale->products()->select('product_id'))
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('sku', 'like', "%{$q}%")))
            ->when($request->boolean('in_stock'), fn ($query) => $query->where('stock', '>', 0))
            ->orderBy('name')
            ->limit(30)
            ->get(['id', 'name', 'price', 'sale_price', 'image', 'stock']);

        return response()->json($products->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'price' => (float) ($p->sale_price ?? $p->price),
            'stock' => (int) $p->stock,
            'image' => $p->image ? image_thumb($p->image, 96) : null,
        ]));
    }

    public function update(Request $request, FlashSale $flashSale)
    {
        $data = $this->validated($request, $flashSale);

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

    public function toggle(FlashSale $flashSale)
    {
        $flashSale->update(['is_active' => ! $flashSale->is_active]);
        AuditLogger::log('flash_sale.toggled', "Flash sale \"{$flashSale->name}\" " . ($flashSale->is_active ? 'activated' : 'paused'), $flashSale);

        return back()->with('success', $flashSale->is_active ? 'Flash sale activated.' : 'Flash sale paused.');
    }

    public function duplicate(FlashSale $flashSale)
    {
        $copy = $flashSale->duplicate();
        AuditLogger::log('flash_sale.created', "Flash sale \"{$copy->name}\" duplicated from \"{$flashSale->name}\"", $copy);

        return redirect()->route('admin.flash-sales.edit', $copy)->with('success', 'Copy created (paused). Adjust the schedule, then activate it.');
    }

    /** Adds one or many products with the same deal terms. */
    public function addProduct(Request $request, FlashSale $flashSale)
    {
        $data = $request->validate([
            'product_ids'     => 'required|array|min:1',
            'product_ids.*'   => 'integer|exists:products,id',
            'discount_type'   => 'required|in:percentage,fixed',
            'discount_value'  => ['required', 'numeric', 'min:0.01', Rule::when($request->discount_type === 'percentage', 'max:99')],
            'stock_limit'     => 'nullable|integer|min:0',
            'per_order_limit' => 'nullable|integer|min:0',
        ]);

        $next = (int) $flashSale->products()->max('sort_order');
        foreach (array_unique($data['product_ids']) as $productId) {
            FlashSaleProduct::updateOrCreate(
                ['flash_sale_id' => $flashSale->id, 'product_id' => $productId],
                [
                    'discount_type'   => $data['discount_type'],
                    'discount_value'  => $data['discount_value'],
                    'stock_limit'     => $data['stock_limit'] ?? 0,
                    'per_order_limit' => $data['per_order_limit'] ?? 0,
                    'sort_order'      => ++$next,
                ]
            );
        }

        $n = count(array_unique($data['product_ids']));
        return back()->with('success', $n === 1 ? 'Product added to flash sale.' : "{$n} products added to flash sale.");
    }

    /** Saves the inline-edited products table in one go (and drops any rows ticked for removal). */
    public function updateProducts(Request $request, FlashSale $flashSale)
    {
        $data = $request->validate([
            'items'                    => 'array',
            'items.*.discount_type'    => 'required|in:percentage,fixed',
            'items.*.discount_value'   => 'required|numeric|min:0.01',
            'items.*.stock_limit'      => 'nullable|integer|min:0',
            'items.*.per_order_limit'  => 'nullable|integer|min:0',
            'items.*.sort_order'       => 'nullable|integer|min:0',
            'items.*.is_featured'      => 'nullable|boolean',
            'remove'                   => 'array',
            'remove.*'                 => 'integer',
        ]);

        $remove = array_map('intval', $data['remove'] ?? []);
        $rows = $flashSale->products()->get()->keyBy('id');

        foreach ($data['items'] ?? [] as $id => $item) {
            $row = $rows->get((int) $id);
            if (! $row || in_array((int) $id, $remove)) continue;

            $value = (float) $item['discount_value'];
            if ($item['discount_type'] === 'percentage') $value = min($value, 99);

            $row->update([
                'discount_type'   => $item['discount_type'],
                'discount_value'  => $value,
                'stock_limit'     => (int) ($item['stock_limit'] ?? 0),
                'per_order_limit' => (int) ($item['per_order_limit'] ?? 0),
                'sort_order'      => (int) ($item['sort_order'] ?? 0),
                'is_featured'     => (bool) ($item['is_featured'] ?? false),
            ]);
        }

        $removed = $remove ? $flashSale->products()->whereIn('id', $remove)->delete() : 0;
        AuditLogger::log('flash_sale.updated', "Flash sale \"{$flashSale->name}\" products updated", $flashSale);

        return back()->with('success', $removed ? "Products saved ({$removed} removed)." : 'Products saved.');
    }

    public function removeProduct(FlashSale $flashSale, FlashSaleProduct $product)
    {
        abort_unless($product->flash_sale_id === $flashSale->id, 404);
        $product->delete();
        return back()->with('success', 'Product removed.');
    }

    private function validated(Request $request, ?FlashSale $sale = null): array
    {
        $data = $request->validate([
            'name'           => 'required|string|max:255',
            'slug'           => ['nullable', 'alpha_dash', 'max:255', Rule::unique('flash_sales', 'slug')->ignore($sale?->id)],
            'description'    => 'nullable|string|max:255',
            'banner_text'    => 'nullable|string|max:255',
            'banner_color'   => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'theme'          => ['required', Rule::in(array_keys(FlashSale::THEMES))],
            'layout'         => ['required', Rule::in(array_keys(FlashSale::LAYOUTS))],
            'home_limit'     => 'required|integer|min:4|max:40',
            'priority'       => 'nullable|integer|min:0|max:100',
            'starts_at'      => 'required|date',
            'ends_at'        => 'required|date|after:starts_at',
        ]);

        // The form's datetime-local inputs are in the store's own timezone; storage is UTC.
        $tz = setting('timezone', 'Asia/Dhaka');
        $data['starts_at'] = Carbon::parse($data['starts_at'], $tz)->utc();
        $data['ends_at'] = Carbon::parse($data['ends_at'], $tz)->utc();
        $data['priority'] = (int) ($data['priority'] ?? 0);
        $data['banner_color'] = $data['banner_color'] ?? '#ef4444';

        foreach (['is_active', 'show_on_home', 'show_upcoming', 'show_stock_bar'] as $flag) {
            $data[$flag] = $request->boolean($flag);
        }

        return $data;
    }
}
