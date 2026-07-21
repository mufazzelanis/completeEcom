<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TagController extends Controller
{
    public function index(Request $request)
    {
        $tags = Tag::withCount('products')
            ->when($request->filled('search'), fn($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->orderBy('name')
            ->paginate(30);
        return view('admin.tags.index', compact('tags'));
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:100|unique:tags,name']);

        Tag::create([
            'name' => trim($request->name),
            'slug' => Str::slug($request->name),
        ]);

        return back()->with('success', 'Tag created.');
    }

    public function update(Request $request, Tag $tag)
    {
        $request->validate(['name' => 'required|string|max:100|unique:tags,name,' . $tag->id]);

        $tag->update([
            'name' => trim($request->name),
            'slug' => Str::slug($request->name),
        ]);

        return back()->with('success', 'Tag updated.');
    }

    public function destroy(Tag $tag)
    {
        $tag->delete();
        return back()->with('success', 'Tag deleted.');
    }

    public function quickCreate(Request $request)
    {
        $request->validate(['name' => 'required|string|max:100']);

        $tag = Tag::findOrCreateByName($request->name);

        return response()->json(['id' => $tag->id, 'name' => $tag->name]);
    }
}
