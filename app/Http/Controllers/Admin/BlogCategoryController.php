<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BlogCategoryController extends Controller
{
    public function index()
    {
        $categories = BlogCategory::withCount('posts')->with('parent')->orderBy('sort_order')->orderBy('name')->get();
        return view('admin.blog.categories.index', compact('categories'));
    }

    public function create()
    {
        $parents = BlogCategory::whereNull('parent_id')->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        return view('admin.blog.categories.create', compact('parents'));
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255', 'image' => 'nullable|image|max:2048']);

        $data = $request->only(['name', 'description', 'sort_order']);
        $data['slug']       = $this->uniqueSlug(Str::slug($request->name));
        $data['is_active']  = $request->boolean('is_active', true);
        $data['sort_order'] = (int) ($request->sort_order ?? 0);
        $data['parent_id']  = $request->filled('parent_id') ? $request->parent_id : null;

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('blog/categories', 'public');
        }

        BlogCategory::create($data);
        return redirect()->route('admin.blog.categories.index')->with('success', 'Category created.');
    }

    public function edit(BlogCategory $blogCategory)
    {
        $parents = BlogCategory::whereNull('parent_id')->where('is_active', true)->where('id', '!=', $blogCategory->id)->orderBy('name')->get(['id', 'name']);
        return view('admin.blog.categories.edit', compact('blogCategory', 'parents'));
    }

    public function update(Request $request, BlogCategory $blogCategory)
    {
        $request->validate(['name' => 'required|string|max:255', 'image' => 'nullable|image|max:2048']);

        $data = $request->only(['name', 'description', 'sort_order']);
        $data['is_active']  = $request->boolean('is_active');
        $data['sort_order'] = (int) ($request->sort_order ?? 0);
        $data['parent_id']  = $request->filled('parent_id') ? $request->parent_id : null;

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('blog/categories', 'public');
        }

        $blogCategory->update($data);
        return redirect()->route('admin.blog.categories.index')->with('success', 'Category updated.');
    }

    public function destroy(BlogCategory $blogCategory)
    {
        $blogCategory->delete();
        return redirect()->route('admin.blog.categories.index')->with('success', 'Category deleted.');
    }

    private function uniqueSlug(string $slug, ?int $exceptId = null): string
    {
        $original = $slug; $i = 1;
        while (true) {
            $q = BlogCategory::where('slug', $slug);
            if ($exceptId) $q->where('id', '!=', $exceptId);
            if (!$q->exists()) break;
            $slug = $original . '-' . $i++;
        }
        return $slug;
    }
}
