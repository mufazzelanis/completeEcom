<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\PromoPopup;
use App\Support\ImageOptimizer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Marketing → Promo Popups: storefront promotion popups, their targeting and results. */
class PromoPopupController extends Controller
{
    public function index()
    {
        $popups = PromoPopup::orderByDesc('is_active')->orderByDesc('priority')->latest()->get();

        return view('admin.promo-popups.index', compact('popups'));
    }

    public function create()
    {
        return $this->form(new PromoPopup([
            'is_active' => true, 'accent_color' => '#f97316', 'pages' => ['product'], 'device' => 'all', 'audience' => 'all',
            'source' => 'all', 'trigger' => 'delay', 'trigger_value' => 5, 'frequency' => 'session', 'frequency_days' => 3,
        ]));
    }

    public function edit(PromoPopup $promoPopup)
    {
        return $this->form($promoPopup);
    }

    private function form(PromoPopup $popup)
    {
        $categories = Category::orderBy('name')->get(['id', 'name', 'parent_id']);
        $products = $popup->product_ids ? Product::whereIn('id', $popup->product_ids)->get(['id', 'name', 'image']) : collect();

        return view('admin.promo-popups.form', compact('popup', 'categories', 'products'));
    }

    public function store(Request $request)
    {
        $popup = PromoPopup::create($this->validated($request) + ['stats_since' => now()]);

        return redirect()->route('admin.promo-popups.index')->with('success', "Popup “{$popup->name}” created.");
    }

    public function update(Request $request, PromoPopup $promoPopup)
    {
        $promoPopup->update($this->validated($request, $promoPopup));

        return redirect()->route('admin.promo-popups.index')->with('success', "Popup “{$promoPopup->name}” saved.");
    }

    public function destroy(PromoPopup $promoPopup)
    {
        if ($promoPopup->image) {
            Storage::disk('public')->delete($promoPopup->image);
        }
        $promoPopup->delete();

        return back()->with('success', 'Popup deleted.');
    }

    public function toggle(PromoPopup $promoPopup)
    {
        $promoPopup->update(['is_active' => !$promoPopup->is_active]);

        return response()->json(['active' => $promoPopup->is_active, 'status' => $promoPopup->status()]);
    }

    public function duplicate(PromoPopup $promoPopup)
    {
        $copy = $promoPopup->replicate(['views', 'clicks', 'closes', 'orders', 'revenue']);
        $copy->name = $promoPopup->name . ' (copy)';
        $copy->is_active = false;
        $copy->stats_since = now();
        if ($promoPopup->image) {
            $ext = pathinfo($promoPopup->image, PATHINFO_EXTENSION);
            $path = 'promo-popups/' . uniqid('copy_') . '.' . $ext;
            Storage::disk('public')->copy($promoPopup->image, $path);
            $copy->image = $path;
        }
        $copy->save();

        return redirect()->route('admin.promo-popups.edit', $copy)->with('success', 'Copied — it starts switched off.');
    }

    public function resetStats(PromoPopup $promoPopup)
    {
        $promoPopup->forceFill(['views' => 0, 'clicks' => 0, 'closes' => 0, 'orders' => 0, 'revenue' => 0, 'stats_since' => now()])->save();

        return back()->with('success', 'Results reset.');
    }

    /** Product picker search (form). */
    public function products(Request $request)
    {
        $q = trim((string) $request->query('q'));

        return response()->json(Product::query()
            ->when($q !== '', fn ($x) => $x->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('sku', 'like', "%{$q}%")))
            ->orderBy('name')->limit(12)->get(['id', 'name', 'image'])
            ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'image' => image_thumb($p->image, 96)]));
    }

    private function validated(Request $request, ?PromoPopup $popup = null): array
    {
        $v = $request->validate([
            'name' => 'required|string|max:120',
            'priority' => 'nullable|integer|min:0|max:999',
            'image' => 'nullable|image|max:4096',
            'title' => 'nullable|string|max:160',
            'body' => 'nullable|string|max:600',
            'button_text' => 'nullable|string|max:40',
            // a full URL or a path on this site (/shop, /products/…)
            'link_url' => ['nullable', 'string', 'max:1000', 'regex:#^(https?://|/)#i'],
            'coupon_code' => 'nullable|string|max:60',
            'countdown_to' => 'nullable|date',
            'accent_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'pages' => 'nullable|array',
            'pages.*' => Rule::in(array_keys(PromoPopup::PAGES)),
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'integer',
            'device' => ['required', Rule::in(array_keys(PromoPopup::DEVICES))],
            'audience' => ['required', Rule::in(array_keys(PromoPopup::AUDIENCES))],
            'source' => ['required', Rule::in(array_keys(PromoPopup::SOURCES))],
            'trigger' => ['required', Rule::in(array_keys(PromoPopup::TRIGGERS))],
            'trigger_value' => 'nullable|integer|min:0|max:600',
            'frequency' => ['required', Rule::in(array_keys(PromoPopup::FREQUENCIES))],
            'frequency_days' => 'nullable|integer|min:1|max:365',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
        ], [
            'link_url.regex' => 'Use a full link (https://…) or a page on this site starting with /.',
            'ends_at.after_or_equal' => 'The end date has to be after the start date.',
        ]);

        $removeImage = $request->boolean('remove_image');
        $hasImage = $request->hasFile('image') || ($popup?->image && !$removeImage);
        if (!$hasImage && !filled($v['title'] ?? null) && !filled($v['body'] ?? null)) {
            throw ValidationException::withMessages(['image' => 'Add a picture, a title or some text — at least one.']);
        }

        $tz = setting('timezone', 'Asia/Dhaka');
        $at = fn ($key) => filled($v[$key] ?? null) ? Carbon::parse($v[$key], $tz)->utc() : null;

        $data = [
            'name' => $v['name'],
            'priority' => (int) ($v['priority'] ?? 0),
            'is_active' => $request->boolean('is_active'),
            'title' => $v['title'] ?? null,
            'body' => $v['body'] ?? null,
            'button_text' => $v['button_text'] ?? null,
            'link_url' => $v['link_url'] ?? null,
            'new_tab' => $request->boolean('new_tab'),
            'coupon_code' => isset($v['coupon_code']) ? strtoupper(trim($v['coupon_code'])) : null,
            'countdown_to' => $at('countdown_to'),
            'accent_color' => $v['accent_color'] ?? '#f97316',
            'pages' => array_values($v['pages'] ?? []) ?: ['all'],
            'category_ids' => array_values(array_map('intval', $v['category_ids'] ?? [])) ?: null,
            'product_ids' => array_values(array_unique(array_map('intval', $v['product_ids'] ?? []))) ?: null,
            'device' => $v['device'],
            'audience' => $v['audience'],
            'source' => $v['source'],
            'trigger' => $v['trigger'],
            'trigger_value' => (int) ($v['trigger_value'] ?? 0),
            'frequency' => $v['frequency'],
            'frequency_days' => (int) ($v['frequency_days'] ?? 3),
            'starts_at' => $at('starts_at'),
            'ends_at' => $at('ends_at'),
        ];

        if ($request->hasFile('image') || $removeImage) {
            if ($popup?->image) {
                Storage::disk('public')->delete($popup->image);
            }
            $data['image'] = $request->hasFile('image') ? ImageOptimizer::store($request->file('image'), 'promo-popups', 'public', 1200) : null;
        }

        return $data;
    }
}
