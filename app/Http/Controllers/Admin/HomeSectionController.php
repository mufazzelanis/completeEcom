<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\HomeSection;
use App\Models\Setting;
use Illuminate\Http\Request;

class HomeSectionController extends Controller
{
    public function index()
    {
        $sections = HomeSection::with('category')->orderBy('sort_order')->get();
        $categories = Category::orderBy('name')->get();

        return view('admin.home-sections.index', compact('sections', 'categories'));
    }

    /**
     * "Just For You" isn't a HomeSection row (it's a fixed overflow block, see
     * HomeController::index()), so its heading/button text live as plain Settings
     * instead of on a manageable row like the sections above.
     */
    public function updateJustForYou(Request $request)
    {
        $data = $request->validate([
            'just_for_you_title'       => 'required|string|max:60',
            'just_for_you_button_text' => 'required|string|max:60',
        ]);

        foreach ($data as $key => $value) {
            Setting::set($key, $value, 'general');
        }
        Setting::bust();

        return back()->with('success', '"Just For You" section text updated.');
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
            'title'            => 'required|string|max:100',
            'subtitle'         => 'nullable|string|max:150',
            'source_type'      => 'required|in:featured,top_selling,new_arrivals,on_sale,category',
            'category_ids'     => 'nullable|array|required_if:source_type,category',
            'category_ids.*'   => 'integer|exists:categories,id',
            'product_limit'    => 'required|integer|min:2|max:32',
            'columns'          => 'required|integer|min:2|max:6',
            'theme'            => 'required|in:light,sale',
            'view_all_query'   => 'nullable|string|max:100',
            'view_all_label'   => 'nullable|string|max:40',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        // The 'integer' validation rule above only checks the values, it doesn't cast
        // them — request input arrives as strings, so without this the stored array
        // is ["1","2"] not [1,2]. That mismatch would silently break the edit form's
        // checkbox pre-check (`categoryIds.includes(1)` is false for an array of
        // strings) since it compares with strict equality.
        if (isset($data['category_ids'])) {
            $data['category_ids'] = array_map('intval', $data['category_ids']);
        }

        // category_id (the old single-category column) is kept in sync as the first
        // selected category, purely so anything still reading the belongsTo relation
        // (e.g. the admin list's "→ Category Name" label) shows something sensible.
        // category_ids is the real source of truth from here on.
        $data['category_id'] = $data['category_ids'][0] ?? null;

        return $data;
    }
}
