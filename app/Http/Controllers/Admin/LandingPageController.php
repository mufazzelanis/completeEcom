<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LandingPage;
use App\Models\Order;
use App\Models\Product;
use App\Services\AuditLogger;
use App\Support\ImageOptimizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
        $data = array_merge($data, $this->normalizeSections($request));

        $this->applyUploads($request, $data);
        $this->applyGalleries($request, $data);

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
        $data = array_merge($data, $this->normalizeSections($request));

        $this->applyUploads($request, $data);
        $this->applyGalleries($request, $data, $landingPage);

        $landingPage->update($data);

        AuditLogger::log('landing_page.updated', "Updated landing page \"{$landingPage->title}\"", $landingPage);

        // store() redirects back to edit (so the admin can keep filling in images/sections
        // right after creating), but update()'s Save button is the "I'm done" action — send
        // them back to the list, same as every other admin CRUD screen in this app.
        return redirect()->route('admin.landing-pages.index')->with('success', 'Landing page updated.');
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

    public function report()
    {
        $pages = LandingPage::withCount('orders')
            ->withSum('orders', 'total')
            ->orderByDesc('views_count')
            ->get();

        $totals = [
            'views'   => $pages->sum('views_count'),
            'orders'  => $pages->sum('orders_count'),
            'revenue' => $pages->sum('orders_sum_total'),
        ];
        $totals['conversion'] = $totals['views'] > 0 ? round($totals['orders'] / $totals['views'] * 100, 1) : 0;

        return view('admin.landing-pages.report', compact('pages', 'totals'));
    }

    /**
     * Landing orders live entirely on their own screen, deliberately not mixed into the
     * regular Admin\OrderController@index list — a landing order has no user_id/account
     * behind it (see LandingPageController@order in the public namespace: it never touches
     * `user_id`, only the shipping_name/phone typed into the funnel's own form) and carries
     * landing-specific data (which page, its custom form field answers) the generic orders
     * table doesn't show. Same underlying `orders` row, but its own dedicated list.
     */
    public function orders(Request $request)
    {
        $query = Order::whereNotNull('landing_page_id')->with('landingPage');

        if ($request->filled('landing_page_id')) {
            $query->where('landing_page_id', $request->landing_page_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('order_number', 'like', '%' . $request->search . '%')
                    ->orWhere('shipping_name', 'like', '%' . $request->search . '%')
                    ->orWhere('shipping_phone', 'like', '%' . $request->search . '%');
            });
        }

        $orders = $query->latest()->paginate(15)->withQueryString();
        $pages = LandingPage::orderBy('title')->get(['id', 'title']);

        return view('admin.landing-pages.orders', compact('orders', 'pages'));
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
            'favicon'                  => 'nullable|image|max:512',
            'og_image'                 => 'nullable|image|max:4096',
            'urgency_bar_text'         => 'nullable|string|max:255',
            // Clamped 0-9 / 0-59 so the total always stays under 10 minutes — matches the
            // urgency bar's own "order within 10 minutes" copy; a longer countdown than the
            // text itself promises would just look broken.
            'urgency_bar_minutes'      => 'nullable|integer|min:0|max:9',
            'urgency_bar_seconds'      => 'nullable|integer|min:0|max:59',
            'rating_value'             => 'nullable|numeric|min:0|max:5',
            'rating_count'             => 'nullable|integer|min:0',
            'how_it_works_heading'     => 'nullable|string|max:255',
            'how_it_works_video'       => 'nullable|url|max:500',
            'benefits_heading'         => 'nullable|string|max:255',
            'who_for_heading'          => 'nullable|string|max:255',
            'testimonials_heading'     => 'nullable|string|max:255',
            'offer_badge_text'         => 'nullable|string|max:60',
            'compare_at_price'         => 'nullable|numeric|min:0',
            'faqs_heading'             => 'nullable|string|max:255',
            'certificates_heading'     => 'nullable|string|max:255',
            'certificates_subheading'  => 'nullable|string|max:500',
            'testimonial_images.*'     => 'nullable|image|max:4096',
            'certificates.*'           => 'nullable|image|max:4096',
            'tb_image.*'               => 'nullable|image|max:512',
            'benefit_image.*'          => 'nullable|image|max:512',
            'wf_image.*'               => 'nullable|image|max:512',
        ]);

        $data = $request->only([
            'title', 'product_id', 'hero_heading', 'hero_subheading', 'content',
            'price_override', 'order_button_text', 'thank_you_heading',
            'thank_you_message', 'thank_you_redirect_url', 'meta_title', 'meta_description',
            'urgency_bar_text', 'rating_value', 'rating_count',
            'how_it_works_heading', 'how_it_works_video',
            'benefits_heading', 'who_for_heading', 'testimonials_heading',
            'offer_badge_text', 'compare_at_price',
            'faqs_heading', 'certificates_heading', 'certificates_subheading',
        ]);

        $data['status']              = $request->status;
        $data['collect_address']     = $request->boolean('collect_address');
        $data['require_address']     = $request->boolean('require_address');
        $data['order_button_text']   = $request->filled('order_button_text') ? $request->order_button_text : 'Order Now';
        $data['thank_you_heading']   = $request->filled('thank_you_heading') ? $request->thank_you_heading : 'Thank You!';
        $data['urgency_bar_enabled'] = $request->boolean('urgency_bar_enabled');
        $data['urgency_bar_minutes'] = $request->filled('urgency_bar_minutes') ? (int) $request->urgency_bar_minutes : 9;
        $data['urgency_bar_seconds'] = $request->filled('urgency_bar_seconds') ? (int) $request->urgency_bar_seconds : 45;
        // A 0:00 countdown would just sit there reading "00:00" the instant the page loads —
        // treat it as "not really set" and give it a sane floor instead.
        if ($data['urgency_bar_minutes'] === 0 && $data['urgency_bar_seconds'] === 0) {
            $data['urgency_bar_seconds'] = 30;
        }

        return $data;
    }

    /**
     * All of the template's repeater sections (trust badges, benefits grid, "who is this
     * for", video testimonials, FAQ, pricing line items, delivery zones) post the same
     * parallel-array shape as normalizeFields() above — one input array per column, one
     * index per row — for the same reason: it's what a plain Alpine x-for repeater with
     * name="x[]" inputs naturally produces, no nested-array JS needed. Rows where the first
     * ("primary") column is blank are dropped rather than saved as empty entries.
     */
    private function normalizeSections(Request $request): array
    {
        return [
            'trust_badges'    => $this->normalizeRepeaterWithImage($request, ['icon' => 'tb_icon', 'text' => 'tb_text'], 'text', 'tb'),
            'benefits'        => $this->normalizeRepeaterWithImage($request, ['icon' => 'benefit_icon', 'title' => 'benefit_title', 'description' => 'benefit_desc'], 'title', 'benefit'),
            'who_for'         => $this->normalizeRepeaterWithImage($request, ['icon' => 'wf_icon', 'text' => 'wf_text'], 'text', 'wf'),
            'testimonial_videos' => $this->normalizeRepeater($request, ['video_url' => 'tv_url', 'name' => 'tv_name'], 'video_url'),
            'faqs'            => $this->normalizeRepeater($request, ['question' => 'faq_q', 'answer' => 'faq_a'], 'question'),
            'pricing_items'   => $this->normalizeRepeater($request, ['label' => 'price_label', 'price' => 'price_amount'], 'label'),
            'delivery_zones'  => $this->normalizeRepeater($request, ['label' => 'zone_label', 'charge' => 'zone_charge'], 'label'),
        ];
    }

    private function normalizeRepeater(Request $request, array $fields, string $primaryKey): array
    {
        $columns = [];
        foreach ($fields as $outKey => $inputName) {
            $columns[$outKey] = $request->input($inputName, []);
        }

        $rows = [];
        foreach ($columns[$primaryKey] ?? [] as $i => $primaryValue) {
            if (trim((string) $primaryValue) === '') {
                continue;
            }
            $row = [];
            foreach ($columns as $outKey => $values) {
                $row[$outKey] = trim((string) ($values[$i] ?? ''));
            }
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Same shape as normalizeRepeater(), for repeaters where each row can also carry an
     * uploaded image (a custom icon) alongside its text fields — see
     * admin/landing-pages/_form.blade.php's {$prefix}_image[]/{$prefix}_existing_image[]/
     * {$prefix}_remove_image[] inputs, one row's worth per array index, same alignment as
     * the text columns. A brand-new upload replaces any existing image; the remove checkbox
     * clears it back to text/emoji-only; a row dropped from the form entirely (blank
     * primary field) has its stored image file deleted rather than left orphaned on disk.
     */
    private function normalizeRepeaterWithImage(Request $request, array $fields, string $primaryKey, string $prefix): array
    {
        $columns = [];
        foreach ($fields as $outKey => $inputName) {
            $columns[$outKey] = $request->input($inputName, []);
        }
        $existing = $request->input($prefix . '_existing_image', []);
        $removes  = $request->input($prefix . '_remove_image', []);
        $files    = $request->file($prefix . '_image', []);

        $rows = [];
        $kept = [];
        foreach ($columns[$primaryKey] ?? [] as $i => $primaryValue) {
            if (trim((string) $primaryValue) === '') {
                continue;
            }

            $row = [];
            foreach ($columns as $outKey => $values) {
                $row[$outKey] = trim((string) ($values[$i] ?? ''));
            }

            $image = filled($existing[$i] ?? null) ? $existing[$i] : null;
            if (($removes[$i] ?? '0') === '1' && $image) {
                Storage::disk('public')->delete($image);
                $image = null;
            }
            if (($files[$i] ?? null) instanceof \Illuminate\Http\UploadedFile) {
                if ($image) {
                    Storage::disk('public')->delete($image);
                }
                $image = ImageOptimizer::store($files[$i], 'landing-pages', 'public', 200);
            }

            $row['image'] = $image;
            if ($image) {
                $kept[] = $image;
            }
            $rows[] = $row;
        }

        // Rows removed outright (blank primary field) still had their old image path in
        // {$prefix}_existing_image[] — clean those up too rather than leaving them on disk.
        foreach (array_diff(array_filter($existing), $kept) as $orphan) {
            Storage::disk('public')->delete($orphan);
        }

        return $rows;
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
        if ($request->hasFile('favicon')) {
            $data['favicon'] = ImageOptimizer::store($request->file('favicon'), 'landing-pages', 'public', 256);
        }
        if ($request->hasFile('og_image')) {
            $data['og_image'] = ImageOptimizer::store($request->file('og_image'), 'landing-pages', 'public', 1200);
        }
    }

    /**
     * testimonial_images and certificates are plain JSON arrays of stored paths (no separate
     * table/sort_order needed for a handful of decorative screenshots) — the form lets the
     * admin tick existing thumbnails to remove and/or add new ones in the same submit; new
     * uploads are appended after removals so ordering stays predictable.
     */
    private function applyGalleries(Request $request, array &$data, ?LandingPage $landingPage = null): void
    {
        foreach (['testimonial_images' => 4096, 'certificates' => 4096] as $field => $maxWidth) {
            $existing = $landingPage?->{$field} ?? [];
            $toRemove = $request->input('remove_' . $field, []);
            $kept = array_values(array_diff($existing, $toRemove));
            foreach ($toRemove as $path) {
                Storage::disk('public')->delete($path);
            }

            $uploaded = [];
            if ($request->hasFile($field)) {
                foreach ($request->file($field) as $img) {
                    $uploaded[] = ImageOptimizer::store($img, 'landing-pages', 'public', $maxWidth);
                }
            }

            if ($kept !== $existing || $uploaded) {
                $data[$field] = array_merge($kept, $uploaded);
            }
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
