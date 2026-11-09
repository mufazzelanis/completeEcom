<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Setting;
use App\Support\ImageOptimizer;
use App\Support\Watermark;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/** Settings → Watermark: the logo on product photos (App\Support\Watermark). */
class WatermarkController extends Controller
{
    public function index()
    {
        $live = Watermark::live();
        $desired = Watermark::desired();
        $saved = $desired ?? $live;

        return view('admin.settings.watermark', [
            'look'      => ($saved ?? []) + Watermark::DEFAULTS + ['logo' => null],
            'enabled'   => (bool) $desired,
            'isLive'    => (bool) $live,
            'preparing' => $desired && (int) ($live['v'] ?? 0) !== (int) $desired['v'],
            'progress'  => $this->progress($desired),
            'samples'   => Product::whereNotNull('image')->where('image', 'like', 'products/%')->latest('id')->limit(6)->pluck('image')
                ->filter(fn ($p) => Storage::disk('public')->exists($p))->take(3)->values(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'enabled'      => ['nullable', 'boolean'],
            'logo'         => ['nullable', 'string', 'max:255'],
            'position'     => ['required', Rule::in(array_keys(Watermark::POSITIONS))],
            'size'         => ['required', 'integer', 'min:10', 'max:80'],
            'opacity'      => ['required', 'integer', 'min:3', 'max:40'],
            'tone'         => ['required', Rule::in(array_keys(Watermark::TONES))],
            'protect'      => ['nullable', 'boolean'],
            'skip_sellers' => ['nullable', 'boolean'],
        ]);

        Setting::set('watermark_protect', $request->boolean('protect') ? '1' : '0', 'watermark');
        Setting::set('watermark_skip_sellers', $request->boolean('skip_sellers') ? '1' : '0', 'watermark');
        Watermark::forgetExclusions();

        if (!$request->boolean('enabled')) {
            Watermark::disable();

            return back()->with('success', 'Watermark is off. Customers see the plain photos again.');
        }

        $logo = (string) ($data['logo'] ?? '');
        if (!Watermark::isLogoPath($logo) || !Storage::disk('public')->exists($logo)) {
            return back()->withErrors(['logo' => 'Upload your logo first.'])->withInput();
        }

        Watermark::saveLook([
            'logo' => $logo, 'position' => $data['position'], 'size' => (int) $data['size'],
            'opacity' => (int) $data['opacity'], 'tone' => $data['tone'],
        ]);

        return back()->with('success', 'Saved. The new look goes live by itself as soon as every photo is ready.');
    }

    /** Stores a logo the moment it's picked, so the preview can show it before anything is saved. */
    public function logo(Request $request)
    {
        $request->validate(['logo' => ['required', 'image', 'mimes:png,webp', 'max:4096']]);
        $file = $request->file('logo');
        if (!Watermark::hasTransparentBackground((string) file_get_contents($file->getRealPath()))) {
            return response()->json(['message' => 'This logo has a solid background. Use a PNG or WebP with a transparent background.'], 422);
        }
        $path = ImageOptimizer::store($file, Watermark::logoDirectory(), 'public', 1200, 90);

        return response()->json(['path' => $path, 'url' => Storage::disk('public')->url($path)]);
    }

    /** One sample photo with the sliders' current values. Never saved to disk. */
    public function preview(Request $request)
    {
        $data = $request->validate([
            'logo'     => ['required', 'string', 'max:255'],
            'image'    => ['required', 'string', 'max:255'],
            'position' => ['required', Rule::in(array_keys(Watermark::POSITIONS))],
            'size'     => ['required', 'integer', 'min:10', 'max:80'],
            'opacity'  => ['required', 'integer', 'min:3', 'max:40'],
            'tone'     => ['required', Rule::in(array_keys(Watermark::TONES))],
        ]);
        $disk = Storage::disk('public');
        abort_unless(Watermark::isLogoPath($data['logo']) && $disk->exists($data['logo']), 404);
        abort_unless(Watermark::covers($data['image']) && !str_contains($data['image'], '..') && $disk->exists($data['image']), 404);

        $bytes = Watermark::previewBytes($data['image'], 800, ['v' => 0] + $data);
        abort_unless($bytes, 404);

        return response($bytes, 200, ['Content-Type' => 'image/webp', 'Cache-Control' => 'no-store']);
    }

    /** Skip the wait: show the saved look now; photos not built yet are made on their first view. */
    public function publishNow()
    {
        if (Watermark::desired()) {
            Watermark::publish();
        }

        return back()->with('success', 'The new watermark is live.');
    }

    /** How far watermark:build has got with the version being prepared, in percent. */
    private function progress(?array $desired): int
    {
        if (!$desired) {
            return 0;
        }
        $query = fn () => Product::where('is_active', true)->whereNotNull('image');
        $total = $query()->count();
        $cursor = (int) Cache::get('watermark:cursor:' . $desired['v'], 0);

        return $total ? (int) floor($query()->where('id', '<=', $cursor)->count() / $total * 100) : 100;
    }
}
