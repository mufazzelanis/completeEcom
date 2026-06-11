<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use Illuminate\Http\Request;

class AttributeController extends Controller
{
    public function index(Request $request)
    {
        $attributes = Attribute::when($request->filled('search'), fn($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(25);
        return view('admin.attributes.index', compact('attributes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:100|unique:attributes,name',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        Attribute::create([
            'name'       => trim($request->name),
            'is_active'  => true,
            'sort_order' => (int) $request->sort_order,
        ]);

        return back()->with('success', 'Attribute added.');
    }

    public function update(Request $request, Attribute $attribute)
    {
        $request->validate([
            'name'       => 'required|string|max:100|unique:attributes,name,' . $attribute->id,
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $attribute->update([
            'name'       => trim($request->name),
            'is_active'  => $request->boolean('is_active'),
            'sort_order' => (int) $request->sort_order,
        ]);

        return back()->with('success', 'Attribute updated.');
    }

    public function destroy(Attribute $attribute)
    {
        $attribute->delete();
        return back()->with('success', 'Attribute deleted.');
    }

    public function toggle(Attribute $attribute)
    {
        $attribute->update(['is_active' => !$attribute->is_active]);
        return back()->with('success', 'Status updated.');
    }
}
