<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function index()
    {
        $warehouses = Warehouse::withCount('stockEntries')
            ->orderBy('sort_order')->orderBy('name')->paginate(20);
        return view('admin.warehouses.index', compact('warehouses'));
    }

    public function create()
    {
        return view('admin.warehouses.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'         => 'required|string|max:100',
            'code'         => 'required|string|max:20|unique:warehouses,code',
            'address'      => 'nullable|string',
            'city'         => 'nullable|string|max:100',
            'phone'        => 'nullable|string|max:30',
            'manager_name' => 'nullable|string|max:100',
            'sort_order'   => 'nullable|integer|min:0',
            'is_active'    => 'nullable|boolean',
        ]);

        $data['is_active']  = $request->boolean('is_active');
        $data['sort_order'] = $request->input('sort_order', 0);

        Warehouse::create($data);

        return redirect()->route('admin.warehouses.index')
            ->with('success', 'Warehouse created successfully.');
    }

    public function edit(Warehouse $warehouse)
    {
        return view('admin.warehouses.edit', compact('warehouse'));
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        $data = $request->validate([
            'name'         => 'required|string|max:100',
            'code'         => 'required|string|max:20|unique:warehouses,code,' . $warehouse->id,
            'address'      => 'nullable|string',
            'city'         => 'nullable|string|max:100',
            'phone'        => 'nullable|string|max:30',
            'manager_name' => 'nullable|string|max:100',
            'sort_order'   => 'nullable|integer|min:0',
            'is_active'    => 'nullable|boolean',
        ]);

        $data['is_active']  = $request->boolean('is_active');
        $data['sort_order'] = $request->input('sort_order', 0);

        $warehouse->update($data);

        return redirect()->route('admin.warehouses.index')
            ->with('success', 'Warehouse updated successfully.');
    }

    public function destroy(Warehouse $warehouse)
    {
        if ($warehouse->stockEntries()->where('stock', '>', 0)->exists()) {
            return back()->with('error', 'Cannot delete warehouse with existing stock. Zero out stock first.');
        }

        $warehouse->delete();

        return redirect()->route('admin.warehouses.index')
            ->with('success', 'Warehouse deleted.');
    }
}
