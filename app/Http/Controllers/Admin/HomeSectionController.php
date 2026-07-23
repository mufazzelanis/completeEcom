<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\HomeSection;
use Illuminate\Http\Request;

class HomeSectionController extends Controller
{
    public function index()
    {
        $sections = HomeSection::with('category')->orderBy('sort_order')->get();
        $categories = Category::orderBy('name')->get();

        return view('admin.home-sections.index', compact('sections', 'categories'));
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();
        return view('admin.home-sections.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['sort_order'] = (HomeSection::max('sort_order') ?? 0) + 1;

        HomeSection::create($data);

        return redirect()->route('admin.home-sections.index')->with('success', 'Homepage section added.');
    }

    public function edit(HomeSection $homeSection)
    {
        $categories = Category::orderBy('name')->get();
        return view('admin.home-sections.edit', ['section' => $homeSection, 'categories' => $categories]);
    }

    public function update(Request $request, HomeSection $homeSection)
    {
        $homeSection->update($this->validated($request));

        return redirect()->route('admin.home-sections.index')->with('success', 'Homepage section updated.');
    }

    public function destroy(HomeSection $homeSection)
    {
        $homeSection->delete();

        return back()->with('success', 'Homepage section removed.');
    }

    public function toggle(HomeSection $homeSection)
    {
        $homeSection->update(['is_active' => !$homeSection->is_active]);

        return back()->with('success', 'Section ' . ($homeSection->is_active ? 'enabled' : 'disabled') . '.');
    }

    public function moveUp(HomeSection $homeSection)
    {
        $this->swapWithNeighbor($homeSection, 'up');
        return back();
    }

    public function moveDown(HomeSection $homeSection)
    {
        $this->swapWithNeighbor($homeSection, 'down');
        return back();
    }

    private function swapWithNeighbor(HomeSection $section, string $direction): void
    {
        $neighbor = $direction === 'up'
            ? HomeSection::where('sort_order', '<', $section->sort_order)->orderByDesc('sort_order')->first()
            : HomeSection::where('sort_order', '>', $section->sort_order)->orderBy('sort_order')->first();

        if (!$neighbor) {
            return;
        }

        [$a, $b] = [$section->sort_order, $neighbor->sort_order];
        $section->update(['sort_order' => $b]);
        $neighbor->update(['sort_order' => $a]);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title'          => 'required|string|max:100',
            'subtitle'       => 'nullable|string|max:150',
            'source_type'    => 'required|in:featured,top_selling,new_arrivals,on_sale,category',
            'category_id'    => 'nullable|exists:categories,id|required_if:source_type,category',
            'product_limit'  => 'required|integer|min:2|max:32',
            'theme'          => 'required|in:light,sale',
            'view_all_query' => 'nullable|string|max:100',
            'view_all_label' => 'nullable|string|max:40',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
