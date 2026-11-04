<?php

namespace App\Http\Controllers;

use App\Models\FlashSale;
use Illuminate\Http\Request;

class FlashSalePageController extends Controller
{
    /**
     * /flash-sale shows whatever is live right now (or the next teased sale);
     * /flash-sale/{slug} shows that one sale — live, upcoming or ended.
     */
    public function show(Request $request, ?string $slug = null)
    {
        $sale = $slug
            ? FlashSale::where('slug', $slug)->where('is_active', true)->firstOrFail()
            : (FlashSale::current() ?? FlashSale::nextUpcoming());

        $items = collect();
        if ($sale) {
            $items = $sale->products()
                ->with('product')
                ->get()
                ->filter(fn ($fsp) => $fsp->product && $fsp->product->is_active);

            $items = match ($request->query('sort')) {
                'discount'   => $items->sortByDesc('discount_percent'),
                'price_low'  => $items->sortBy('sale_price'),
                'price_high' => $items->sortByDesc('sale_price'),
                'ending'     => $items->sortBy(fn ($i) => $i->remaining ?? PHP_INT_MAX),
                default      => $items,
            };
            // sold-out deals always sink to the end, whatever the sort
            $items = $items->sortBy(fn ($i) => $i->isAvailable() ? 0 : 1)->values();
        }

        $upcoming = FlashSale::upcoming()->where('show_upcoming', true)
            ->when($sale, fn ($q) => $q->where('id', '!=', $sale->id))
            ->withCount('products')
            ->orderBy('starts_at')
            ->take(3)
            ->get();

        return view('flash-sale.show', [
            'sale' => $sale,
            'items' => $items,
            'upcoming' => $upcoming,
            'sort' => $request->query('sort', 'featured'),
        ]);
    }
}
