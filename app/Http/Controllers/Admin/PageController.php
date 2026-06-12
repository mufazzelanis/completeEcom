<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PageController extends Controller
{
    public function index(Request $request)
    {
        $query = Page::query();
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        $pages = $query->orderBy('sort_order')->orderBy('title')->paginate(20)->withQueryString();
        return view('admin.pages.index', compact('pages'));
    }

    public function create()
    {
        return view('admin.pages.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'  => 'required|string|max:255',
            'type'   => 'required|in:static,landing,seo',
            'image'  => 'nullable|image|max:4096',
        ]);

        $data = $request->only(['type', 'title', 'excerpt', 'content', 'template', 'sort_order',
            'meta_title', 'meta_description', 'meta_keywords', 'og_title', 'og_description', 'canonical_url']);
        $data['slug']       = $this->uniqueSlug(Str::slug($request->title));
        $data['is_active']  = $request->boolean('is_active', true);
        $data['sort_order'] = (int) ($request->sort_order ?? 0);
        $data['template']   = $request->filled('template') ? $request->template : 'default';

        if ($request->hasFile('image'))    $data['image']    = $request->file('image')->store('pages', 'public');
        if ($request->hasFile('og_image')) $data['og_image'] = $request->file('og_image')->store('pages/og', 'public');

        Page::create($data);
        return redirect()->route('admin.pages.index')->with('success', 'Page created.');
    }

    public function edit(Page $page)
    {
        return view('admin.pages.edit', compact('page'));
    }

    public function update(Request $request, Page $page)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'type'  => 'required|in:static,landing,seo',
            'image' => 'nullable|image|max:4096',
        ]);

        $data = $request->only(['type', 'title', 'excerpt', 'content', 'template', 'sort_order',
            'meta_title', 'meta_description', 'meta_keywords', 'og_title', 'og_description', 'canonical_url']);
        $data['is_active']  = $request->boolean('is_active');
        $data['sort_order'] = (int) ($request->sort_order ?? 0);
        $data['template']   = $request->filled('template') ? $request->template : 'default';

        if ($request->hasFile('image'))    $data['image']    = $request->file('image')->store('pages', 'public');
        if ($request->hasFile('og_image')) $data['og_image'] = $request->file('og_image')->store('pages/og', 'public');

        $page->update($data);
        return redirect()->route('admin.pages.edit', $page)->with('success', 'Page updated.');
    }

    public function destroy(Page $page)
    {
        $page->delete();
        return redirect()->route('admin.pages.index')->with('success', 'Page deleted.');
    }

    private function uniqueSlug(string $slug, ?int $exceptId = null): string
    {
        $original = $slug; $i = 1;
        while (true) {
            $q = Page::where('slug', $slug);
            if ($exceptId) $q->where('id', '!=', $exceptId);
            if (!$q->exists()) break;
            $slug = $original . '-' . $i++;
        }
        return $slug;
    }
}
