<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SubcategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Category::whereNotNull('parent_id')->with('parent')->withCount('products');

        if ($request->filled('parent')) {
            $query->where('parent_id', $request->parent);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $subcategories = $query->orderBy('parent_id')->orderBy('sort_order')->orderBy('name')->paginate(20);
        $parents       = Category::whereNull('parent_id')->orderBy('name')->get();

        return view('admin.subcategories.index', compact('subcategories', 'parents'));
    }

    public function create(Request $request)
    {
        $parents = Category::whereNull('parent_id')->where('is_active', true)->orderBy('name')->get();
        $selectedParent = $request->get('parent_id');
        return view('admin.subcategories.create', compact('parents', 'selectedParent'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'      => 'required|string|max:255',
            'parent_id' => 'required|exists:categories,id',
            'image'     => 'nullable|image|max:2048',
        ]);

        // Ensure parent is actually a top-level category
        $parent = Category::findOrFail($request->parent_id);
        if ($parent->parent_id !== null) {
            return back()->withErrors(['parent_id' => 'Cannot nest subcategories more than one level.'])->withInput();
        }

        $slug = Str::slug($request->name);
        // Make slug unique
        $count = 0;
        $baseSlug = $slug;
        while (Category::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . (++$count);
        }

        $data = [
            'name'        => $request->name,
            'slug'        => $slug,
            'description' => $request->description,
            'parent_id'   => $request->parent_id,
            'sort_order'  => $request->sort_order ?? 0,
            'is_active'   => $request->boolean('is_active', true),
        ];

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('categories', 'public');
        }

        Category::create($data);

        return redirect()->route('admin.subcategories.index')->with('success', 'Subcategory created successfully.');
    }

    public function edit(int $subcategory)
    {
        $sub     = Category::whereNotNull('parent_id')->findOrFail($subcategory);
        $parents = Category::whereNull('parent_id')->where('is_active', true)->orderBy('name')->get();
        return view('admin.subcategories.edit', ['subcategory' => $sub, 'parents' => $parents]);
    }

    public function update(Request $request, int $subcategory)
    {
        $sub = Category::whereNotNull('parent_id')->findOrFail($subcategory);

        $request->validate([
            'name'      => 'required|string|max:255|unique:categories,name,' . $sub->id,
            'parent_id' => 'required|exists:categories,id',
            'image'     => 'nullable|image|max:2048',
        ]);

        $data = [
            'name'        => $request->name,
            'slug'        => $request->filled('slug') ? Str::slug($request->slug) : $sub->slug,
            'description' => $request->description,
            'parent_id'   => $request->parent_id,
            'sort_order'  => $request->sort_order ?? 0,
            'is_active'   => $request->boolean('is_active'),
        ];

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('categories', 'public');
        }

        $sub->update($data);

        return redirect()->route('admin.subcategories.index')->with('success', 'Subcategory updated successfully.');
    }

    public function destroy(int $subcategory)
    {
        $sub = Category::whereNotNull('parent_id')->findOrFail($subcategory);

        // Unlink products
        \App\Models\Product::where('subcategory_id', $sub->id)->update(['subcategory_id' => null]);

        $sub->delete();

        return redirect()->route('admin.subcategories.index')->with('success', 'Subcategory deleted.');
    }
}
