<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        // Paginated by TOP-LEVEL category (not raw row count) so a parent and all its
        // children always land on the same page together, and both levels genuinely
        // respect sort_order — the previous COALESCE(parent_id, id) grouping ordered
        // top-level rows by their own id underneath the hood (always unique, so
        // sort_order never got a chance to reorder them), which is why move up/down
        // visibly did nothing for any category that had subcategories.
        $topLevel = Category::whereNull('parent_id')
            ->withCount(['products', 'children'])
            ->with(['children' => fn ($q) => $q->withCount('products')->orderBy('sort_order')->orderBy('name')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(50);

        // Categories in this app only nest one level deep (no sub-subcategories), so
        // every flattened child row simply gets children_count = 0 — no lazy-load risk.
        // Each child's `parent` relation is set from the already-loaded $parent instead
        // of letting the view's `$category->parent` access trigger a lazy query per row.
        $categories = $topLevel->getCollection()->flatMap(function ($parent) {
            $parent->children->each(function ($child) use ($parent) {
                $child->children_count = 0;
                $child->setRelation('parent', $parent);
            });
            return collect([$parent])->merge($parent->children);
        });

        $topLevel->setCollection($categories);

        return view('admin.categories.index', ['categories' => $topLevel]);
    }

    public function create()
    {
        $parents = Category::whereNull('parent_id')->get();
        return view('admin.categories.create', compact('parents'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'      => 'required|string|max:255|unique:categories',
            'slug'      => 'nullable|string|unique:categories',
            'image'     => 'nullable|image|max:2048',
            'og_image'  => 'nullable|image|max:2048',
            'parent_id' => 'nullable|exists:categories,id',
        ]);

        $data = $request->only([
            'name', 'slug', 'description', 'parent_id', 'sort_order', 'is_active',
            'meta_title', 'meta_description', 'meta_keywords', 'canonical_url',
        ]);
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);
        $data['is_active'] = $request->boolean('is_active', true);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('categories', 'public');
        }
        if ($request->hasFile('og_image')) {
            $data['og_image'] = $request->file('og_image')->store('categories', 'public');
        }

        Category::create($data);
        return redirect()->route('admin.categories.index')->with('success', 'Category created successfully.');
    }

    public function edit(Category $category)
    {
        $parents = Category::whereNull('parent_id')->where('id', '!=', $category->id)->get();
        return view('admin.categories.edit', compact('category', 'parents'));
    }

    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name'  => 'required|string|max:255|unique:categories,name,' . $category->id,
            'slug'  => 'nullable|string|unique:categories,slug,' . $category->id,
            'image' => 'nullable|image|max:2048',
            'og_image' => 'nullable|image|max:2048',
        ]);

        $data = $request->only([
            'name', 'slug', 'description', 'parent_id', 'sort_order',
            'meta_title', 'meta_description', 'meta_keywords', 'canonical_url',
        ]);
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);
        $data['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('categories', 'public');
        }
        if ($request->hasFile('og_image')) {
            $data['og_image'] = $request->file('og_image')->store('categories', 'public');
        }

        $category->update($data);
        return redirect()->route('admin.categories.index')->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category)
    {
        if ($category->products()->exists()) {
            return back()->with('error', 'Cannot delete category with existing products. Reassign or remove them first.');
        }
        if ($category->children()->exists()) {
            return back()->with('error', 'Cannot delete category with subcategories. Remove or reassign them first.');
        }

        $category->delete();
        return redirect()->route('admin.categories.index')->with('success', 'Category deleted.');
    }

    public function show(Category $category)
    {
        return redirect()->route('admin.categories.edit', $category);
    }

    public function moveUp(Category $category)
    {
        $this->swapWithNeighbor($category, 'up');
        return back();
    }

    public function moveDown(Category $category)
    {
        $this->swapWithNeighbor($category, 'down');
        return back();
    }

    /**
     * Scoped by parent_id — top-level categories (the horizontal nav bar) and each
     * parent's own subcategories reorder independently of one another, since they
     * render as separate lists on the frontend, not one combined sequence.
     */
    private function swapWithNeighbor(Category $category, string $direction): void
    {
        $siblings = Category::where('parent_id', $category->parent_id);

        $neighbor = $direction === 'up'
            ? (clone $siblings)->where('sort_order', '<', $category->sort_order)->orderByDesc('sort_order')->first()
            : (clone $siblings)->where('sort_order', '>', $category->sort_order)->orderBy('sort_order')->first();

        if (!$neighbor) {
            return;
        }

        [$a, $b] = [$category->sort_order, $neighbor->sort_order];
        $category->update(['sort_order' => $b]);
        $neighbor->update(['sort_order' => $a]);
    }
}
