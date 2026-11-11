<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PageController extends Controller
{
    public const SYSTEM_SLUGS = ['faq', 'contact', 'return-policy'];

    private function rules(): array
    {
        return [
            'title'            => 'required|string|max:255',
            'slug'             => ['nullable', 'string', 'max:120', 'regex:/^[A-Za-z0-9\s_-]+$/'],
            'type'             => 'required|in:static,landing,seo',
            'template'         => 'nullable|in:default,faq,contact,landing',
            'excerpt'          => 'nullable|string|max:500',
            'meta_title'       => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'og_title'         => 'nullable|string|max:255',
            'og_description'   => 'nullable|string|max:500',
            'meta_keywords'    => 'nullable|string|max:255',
            'canonical_url'    => 'nullable|url|max:255',
            'image'            => 'nullable|image|max:4096',
            'og_image'         => 'nullable|image|max:4096',
        ];
    }

    private const SLUG_MESSAGES = ['slug.regex' => 'The slug can only use English letters, numbers and hyphens (e.g. about-us).'];

    /** The admin's slug, else one from the title, else (a Bangla-only title) a readable fallback. */
    private function slugFrom(Request $request, ?Page $except = null): string
    {
        $slug = Str::slug((string) ($request->input('slug') ?: $request->input('title')));

        return $this->uniqueSlug($slug !== '' ? Str::limit($slug, 100, '') : 'page-' . Str::lower(Str::random(6)), $except?->id);
    }

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
        $request->validate($this->rules(), self::SLUG_MESSAGES);

        $data = $request->only(['type', 'title', 'excerpt', 'content', 'template', 'sort_order',
            'meta_title', 'meta_description', 'meta_keywords', 'og_title', 'og_description', 'canonical_url']);
        $data['slug']       = $this->slugFrom($request);
        $data['is_active']  = $request->boolean('is_active');
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
        $request->validate($this->rules(), self::SLUG_MESSAGES);

        $data = $request->only(['type', 'title', 'excerpt', 'content', 'template', 'sort_order',
            'meta_title', 'meta_description', 'meta_keywords', 'og_title', 'og_description', 'canonical_url']);

        // a new address for a normal page: the old one redirects (301) so links and rankings carry over
        $oldSlug = $page->slug;
        if (!in_array($oldSlug, self::SYSTEM_SLUGS, true) && $request->filled('slug') && Str::slug($request->slug) !== $oldSlug) {
            $data['slug'] = $this->slugFrom($request, $page);
        }
        $data['is_active']  = $request->boolean('is_active');
        $data['sort_order'] = (int) ($request->sort_order ?? 0);
        $data['template']   = $request->filled('template') ? $request->template : 'default';

        if (in_array($page->slug, self::SYSTEM_SLUGS, true) && ! $data['is_active']) {
            return back()->withInput()->with('error', "\"{$page->slug}\" is linked from the site (footer/nav) and cannot be deactivated. Edit its content instead, or delete the link elsewhere first.");
        }

        if ($request->hasFile('image'))    $data['image']    = $request->file('image')->store('pages', 'public');
        if ($request->hasFile('og_image')) $data['og_image'] = $request->file('og_image')->store('pages/og', 'public');

        $page->update($data);
        if ($page->slug !== $oldSlug) {
            \App\Models\Redirect::updateOrCreate(['source' => '/pages/' . $oldSlug],
                ['target' => \App\Models\Redirect::normalizeTarget('/pages/' . $page->slug), 'status_code' => 301, 'is_active' => true, 'note' => 'Page address changed']);
            // a redirect that pointed at the new address would now loop
            \App\Models\Redirect::where('source', '/pages/' . $page->slug)->delete();
        }

        return redirect()->route('admin.pages.edit', $page)->with('success', 'Page updated.');
    }

    public function destroy(Page $page)
    {
        if (in_array($page->slug, self::SYSTEM_SLUGS, true)) {
            return back()->with('error', "\"{$page->slug}\" is linked from the site (footer/nav) and cannot be deleted.");
        }

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
