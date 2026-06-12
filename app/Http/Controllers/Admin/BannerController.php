<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    public function index()
    {
        $banners = Banner::orderBy('position')->orderBy('sort_order')->get();
        return view('admin.banners.index', compact('banners'));
    }

    public function create()
    {
        return view('admin.banners.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'    => 'required|string|max:255',
            'position' => 'required|in:hero,top,middle,bottom,sidebar,popup',
            'image'    => 'nullable|image|max:4096',
        ]);

        $data = $request->only(['title', 'subtitle', 'description', 'button_text', 'button_link', 'position', 'bg_color', 'text_color', 'sort_order']);
        $data['is_active']  = $request->boolean('is_active', true);
        $data['sort_order'] = (int) ($request->sort_order ?? 0);
        $data['starts_at']  = $request->filled('starts_at') ? $request->starts_at : null;
        $data['ends_at']    = $request->filled('ends_at') ? $request->ends_at : null;

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('banners', 'public');
        }

        Banner::create($data);
        return redirect()->route('admin.banners.index')->with('success', 'Banner created.');
    }

    public function edit(Banner $banner)
    {
        return view('admin.banners.edit', compact('banner'));
    }

    public function update(Request $request, Banner $banner)
    {
        $request->validate([
            'title'    => 'required|string|max:255',
            'position' => 'required|in:hero,top,middle,bottom,sidebar,popup',
            'image'    => 'nullable|image|max:4096',
        ]);

        $data = $request->only(['title', 'subtitle', 'description', 'button_text', 'button_link', 'position', 'bg_color', 'text_color', 'sort_order']);
        $data['is_active']  = $request->boolean('is_active');
        $data['sort_order'] = (int) ($request->sort_order ?? 0);
        $data['starts_at']  = $request->filled('starts_at') ? $request->starts_at : null;
        $data['ends_at']    = $request->filled('ends_at') ? $request->ends_at : null;

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('banners', 'public');
        }

        $banner->update($data);
        return redirect()->route('admin.banners.index')->with('success', 'Banner updated.');
    }

    public function destroy(Banner $banner)
    {
        $banner->delete();
        return redirect()->route('admin.banners.index')->with('success', 'Banner deleted.');
    }

    public function toggle(Banner $banner)
    {
        $banner->update(['is_active' => !$banner->is_active]);
        return back()->with('success', 'Banner ' . ($banner->is_active ? 'deactivated' : 'activated') . '.');
    }
}
