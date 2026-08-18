<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LandingPage;
use App\Models\Product;
use App\Services\AuditLogger;
use App\Support\ImageOptimizer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LandingPageController extends Controller
{
    public function index(Request $request)
    {
        $query = LandingPage::withCount('orders')->with('product');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $landingPages = $query->latest()->paginate(15)->withQueryString();

        return view('admin.landing-pages.index', compact('landingPages'));
    }

    public function create()
    {
        $products = Product::active()->orderBy('name')->get(['id', 'name']);

        return view('admin.landing-pages.create', compact('products'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        // Admin can type their own slug (e.g. matching an ad campaign's URL); falls back
        // to deriving one from the title when left blank.
        $data['slug'] = $this->uniqueSlug(Str::slug($request->filled('slug') ? $request->slug : $request->title));
        $data['order_form_fields'] = $this->normalizeFields($request);

        $this->applyUploads($request, $data);

        $landingPage = LandingPage::create($data);

        AuditLogger::log('landing_page.created', "Created landing page \"{$landingPage->title}\"", $landingPage);

        return redirect()->route('admin.landing-pages.edit', $landingPage)->with('success', 'Landing page created.');
    }

    public function edit(LandingPage $landingPage)
    {
        $products = Product::active()->orderBy('name')->get(['id', 'name']);

        return view('admin.landing-pages.edit', compact('landingPage', 'products'));
    }

    public function update(Request $request, LandingPage $landingPage)
    {
        $data = $this->validated($request, $landingPage->id);

        // Only reslug if the admin actually changed the title — a live landing page's URL
        // (already on FB ads, printed on packaging, etc.) shouldn't silently move just
        // because they tweaked a headline elsewhere on the page.
        if ($request->filled('slug')) {
            $data['slug'] = $this->uniqueSlug(Str::slug($request->slug), $landingPage->id);
        }

        $data['order_form_fields'] = $this->normalizeFields($request);

        $this->applyUploads($request, $data);

        $landingPage->update($data);

        AuditLogger::log('landing_page.updated', "Updated landing page \"{$landingPage->title}\"", $landingPage);

        return redirect()->route('admin.landing-pages.edit', $landingPage)->with('success', 'Landing page updated.');
    }

    public function destroy(LandingPage $landingPage)
    {
        AuditLogger::log('landing_page.deleted', "Deleted landing page \"{$landingPage->title}\"", $landingPage);
        // Orders already placed through this page are untouched — landing_page_id is
        // nullOnDelete, never cascade (see the orders migration).
        $landingPage->delete();

        return redirect()->route('admin.landing-pages.index')->with('success', 'Landing page deleted.');
    }

    public function toggle(LandingPage $landingPage)
    {
        $landingPage->update(['status' => $landingPage->status === 'published' ? 'draft' : 'published']);

        return back()->with('success', 'Landing page is now ' . $landingPage->status . '.');
    }

    private function validated(Request $request, ?int $exceptId = null): array
    {
        $request->validate([
            'title'                    => 'required|string|max:255',
            'slug'                     => 'nullable|alpha_dash|max:255|unique:landing_pages,slug,' . ($exceptId ?? 'NULL') . ',id',
            'product_id'               => 'nullable|exists:products,id',
            'status'                   => 'required|in:draft,published',
            'hero_heading'             => 'nullable|string|max:255',
            'hero_subheading'          => 'nullable|string|max:255',
            'content'                  => 'nullable|string',
            'price_override'           => 'nullable|numeric|min:0',
            'order_button_text'        => 'nullable|string|max:60',
            'collect_address'          => 'nullable|boolean',
            'require_address'          => 'nullable|boolean',
            'thank_you_heading'        => 'nullable|string|max:255',
            'thank_you_message'        => 'nullable|string',
            'thank_you_redirect_url'   => 'nullable|url|max:255',
            'meta_title'               => 'nullable|string|max:255',
            'meta_description'         => 'nullable|string|max:500',
            'hero_image'               => 'nullable|image|max:4096',
            'header_logo'              => 'nullable|image|max:2048',
            'og_image'                 => 'nullable|image|max:4096',
        ]);

        $data = $request->only([
            'title', 'product_id', 'hero_heading', 'hero_subheading', 'content',
            'price_override', 'order_button_text', 'thank_you_heading',
            'thank_you_message', 'thank_you_redirect_url', 'meta_title', 'meta_description',
        ]);

        $data['status']           = $request->status;
        $data['collect_address']  = $request->boolean('collect_address');
        $data['require_address']  = $request->boolean('require_address');
        $data['order_button_text'] = $request->filled('order_button_text') ? $request->order_button_text : 'Order Now';
        $data['thank_you_heading'] = $request->filled('thank_you_heading') ? $request->thank_you_heading : 'Thank You!';

        return $data;
    }

    /**
     * The repeater UI (see admin/landing-pages/_form.blade.php) posts parallel arrays —
     * fields[label][], fields[type][], fields[required][], fields[options][] — one entry
     * per row, rather than fields[i][label] nested arrays, since that's what a plain Alpine
     * x-for repeater with name="fields[label][]"-style inputs naturally produces. Blank rows
     * (no label typed) are dropped rather than saved as an empty field.
     */
    private function normalizeFields(Request $request): array
    {
        $labels    = $request->input('field_label', []);
        $types     = $request->input('field_type', []);
        $required  = $request->input('field_required', []);
        $options   = $request->input('field_options', []);

        $fields = [];
        foreach ($labels as $i => $label) {
            if (trim((string) $label) === '') {
                continue;
            }
            $type = in_array($types[$i] ?? 'text', ['text', 'textarea', 'select', 'checkbox', 'tel', 'email', 'number'], true)
                ? $types[$i] : 'text';
            $field = [
                'key'      => Str::slug($label, '_') ?: 'field_' . $i,
                'label'    => $label,
                'type'     => $type,
                // The form posts field_required[] as an always-present "0"/"1" hidden input
                // per row (see admin/landing-pages/_form.blade.php), not a bare checkbox —
                // an unchecked checkbox simply isn't submitted at all, which would shift
                // every array index after it out of alignment with field_label[]/field_type[].
                'required' => ($required[$i] ?? '0') === '1',
            ];
            if ($type === 'select' && filled($options[$i] ?? null)) {
                $field['options'] = array_values(array_filter(array_map('trim', explode(',', $options[$i]))));
            }
            $fields[] = $field;
        }

        return $fields;
    }

    private function applyUploads(Request $request, array &$data): void
    {
        if ($request->hasFile('hero_image')) {
            $data['hero_image'] = ImageOptimizer::store($request->file('hero_image'), 'landing-pages', 'public', 1600);
        }
        if ($request->hasFile('header_logo')) {
            $data['header_logo'] = ImageOptimizer::store($request->file('header_logo'), 'landing-pages', 'public', 400);
        }
        if ($request->hasFile('og_image')) {
            $data['og_image'] = ImageOptimizer::store($request->file('og_image'), 'landing-pages', 'public', 1200);
        }
    }

    private function uniqueSlug(string $slug, ?int $exceptId = null): string
    {
        $original = $slug;
        $i = 1;
        while (true) {
            $q = LandingPage::where('slug', $slug);
            if ($exceptId) {
                $q->where('id', '!=', $exceptId);
            }
            if (! $q->exists()) {
                break;
            }
            $slug = $original . '-' . $i++;
        }

        return $slug;
    }
}
