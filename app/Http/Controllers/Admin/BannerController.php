<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Support\ImageOptimizer;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class BannerController extends Controller
{
    /**
     * hero/top are each shown through a fixed-aspect-ratio container on the storefront
     * (home.blade.php's 16:5 slider and 2:1 promo tiles) — cropping to that exact canvas
     * here, once, at upload time means the frontend never has to crop or letterbox the
     * image itself, whatever ratio the admin's original file happened to be. Other
     * positions (middle/bottom/sidebar/popup) aren't pinned to one container shape yet,
     * so they keep the old resize-only behavior rather than guessing a crop target.
     */
    private function storeBannerImage(UploadedFile $file, string $position): string
    {
        return match ($position) {
            'hero'  => ImageOptimizer::storeCropped($file, 'banners', 'public', 1920, 600),
            'top'   => ImageOptimizer::storeCropped($file, 'banners', 'public', 1200, 600),
            default => ImageOptimizer::store($file, 'banners', 'public', 1920),
        };
    }
    public function index()
    {
        $banners = Banner::orderBy('position')->orderBy('sort_order')->paginate(20);
        return view('admin.banners.index', compact('banners'));
    }

    public function create()
    {
        return view('admin.banners.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            // Nullable on purpose — a banner image that already has its own headline
            // baked in doesn't need the page to stamp a second, redundant title on top.
            'title'    => 'nullable|string|max:255',
            'position' => 'required|in:hero,top,middle,bottom,sidebar,popup',
            'image'    => 'nullable|image|max:4096',
        ]);

        $data = $request->only(['title', 'subtitle', 'description', 'button_text', 'button_link', 'position', 'bg_color', 'text_color', 'sort_order']);
        $data['is_active']  = $request->boolean('is_active');
        $data['sort_order'] = (int) ($request->sort_order ?? 0);
        $data['starts_at']  = $request->filled('starts_at') ? $request->starts_at : null;
        $data['ends_at']    = $request->filled('ends_at') ? $request->ends_at : null;

        if ($request->hasFile('image')) {
            $data['image'] = $this->storeBannerImage($request->file('image'), $data['position']);
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
            'title'    => 'nullable|string|max:255',
            'position' => 'required|in:hero,top,middle,bottom,sidebar,popup',
            'image'    => 'nullable|image|max:4096',
        ]);

        $data = $request->only(['title', 'subtitle', 'description', 'button_text', 'button_link', 'position', 'bg_color', 'text_color', 'sort_order']);
        $data['is_active']  = $request->boolean('is_active');
        $data['sort_order'] = (int) ($request->sort_order ?? 0);
        $data['starts_at']  = $request->filled('starts_at') ? $request->starts_at : null;
        $data['ends_at']    = $request->filled('ends_at') ? $request->ends_at : null;

        if ($request->hasFile('image')) {
            $data['image'] = $this->storeBannerImage($request->file('image'), $data['position']);
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
