<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StockReason;
use Illuminate\Http\Request;

class StockReasonController extends Controller
{
    public function index()
    {
        $reasons = StockReason::orderBy('type')->orderBy('sort_order')->get();
        return view('admin.stock_reasons.index', compact('reasons'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'label' => 'required|string|max:255',
            'type'  => 'required|in:return_in,damage_out,manual_in,manual_out,purchase_in,any',
        ]);

        StockReason::create([
            'label'      => $request->label,
            'type'       => $request->type,
            'sort_order' => (int) ($request->sort_order ?? 0),
            'is_active'  => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.stock-reasons.index')->with('success', 'Stock reason added.');
    }

    public function update(Request $request, StockReason $stockReason)
    {
        $request->validate([
            'label' => 'required|string|max:255',
            'type'  => 'required|in:return_in,damage_out,manual_in,manual_out,purchase_in,any',
        ]);

        $stockReason->update([
            'label'      => $request->label,
            'type'       => $request->type,
            'sort_order' => (int) ($request->sort_order ?? 0),
            'is_active'  => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.stock-reasons.index')->with('success', 'Stock reason updated.');
    }

    public function destroy(StockReason $stockReason)
    {
        $stockReason->delete();
        return redirect()->route('admin.stock-reasons.index')->with('success', 'Stock reason deleted.');
    }

    public function toggle(StockReason $stockReason)
    {
        $stockReason->update(['is_active' => !$stockReason->is_active]);
        return back();
    }
}
