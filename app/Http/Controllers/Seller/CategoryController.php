<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function create()
    {
        $parents = Category::where('is_active', true)->where('approval_status', 'approved')->whereNull('parent_id')->orderBy('name')->get();

        return view('seller.categories.create', compact('parents'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string|max:1000',
        ]);

        Category::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name) . '-' . uniqid(),
            'parent_id' => $request->parent_id,
            'description' => $request->description,
            'vendor_id' => $request->user()->vendor->id,
            'approval_status' => 'pending',
            'is_active' => false,
        ]);

        return redirect()->route('seller.products.create')
            ->with('success', 'Category proposed. It will be available to pick once admin approves it.');
    }
}
