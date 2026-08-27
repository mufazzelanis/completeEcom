<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\ImageOptimizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    protected array $groups = [
        'general', 'branding', 'header', 'footer',
        'localization', 'currency', 'tax',
        'shipping', 'payment', 'orders', 'invoice',
        'email', 'sms', 'seo', 'social', 'notifications',
        'theme', 'security', 'maintenance', 'api', 'pages',
        'facebook_pixel', 'google_ads', 'ads',
    ];

    public function show(string $group = 'general')
    {
        if (!in_array($group, $this->groups)) {
            abort(404);
        }
        return view("admin.settings.{$group}");
    }

    public function update(Request $request, string $group)
    {
        if (!in_array($group, $this->groups)) {
            abort(404);
        }

        // Every file field this generic handler has ever received is a logo/banner/favicon
        // upload — validate each posted file is a real image (content-checked, not just
        // extension) before it's ever written to the public disk. Without this, any file
        // (e.g. a .php web shell) could be uploaded under any field name and land directly
        // in the public/storage tree, which has no PHP-execution restriction.
        $request->validate(
            collect($request->allFiles())->mapWithKeys(fn ($file, $key) => [$key => 'image|max:4096'])->all()
        );

        // These two are rendered unescaped ({!! !!}) on the storefront (announcement
        // banner, footer copyright line) — sanitize on write, same as Page::content.
        $rawHtmlKeys = ['announcement_text', 'copyright_text'];

        // Save regular fields (skip Laravel internals, delete_ prefixed keys, and _hex companions)
        $skip = ['_token', '_method'];
        foreach ($request->except($skip) as $key => $value) {
            if (str_starts_with($key, 'delete_') || str_ends_with($key, '_hex') || $request->hasFile($key)) {
                continue;
            }
            if (in_array($key, $rawHtmlKeys, true) && $value !== null) {
                $value = clean($value);
            }
            Setting::set($key, $value, $group);
        }

        // Handle boolean fields that may be absent when unchecked
        // (they send hidden "0" before each checkbox in the view, so this is fine)

        // Handle logo deletions (fields named delete_<logo_key>)
        foreach ($request->input() as $inputKey => $inputVal) {
            if (str_starts_with($inputKey, 'delete_') && $inputVal) {
                $logoKey = substr($inputKey, 7); // remove 'delete_' prefix
                $old = Setting::get($logoKey);
                if ($old && Storage::disk('public')->exists($old)) {
                    Storage::disk('public')->delete($old);
                }
                Setting::set($logoKey, '', $group);
            }
        }

        // Handle file uploads
        foreach ($request->allFiles() as $key => $file) {
            if ($file->isValid()) {
                $old = Setting::get($key);
                if ($old && Storage::disk('public')->exists($old)) {
                    Storage::disk('public')->delete($old);
                }
                $path = ImageOptimizer::store($file, 'settings', 'public', 1600);
                Setting::set($key, $path, $group);
            }
        }

        Setting::bust();

        return back()->with('success', 'Settings saved successfully.');
    }

    public function testEmail(Request $request)
    {
        $request->validate(['test_email' => 'required|email']);

        try {
            \Illuminate\Support\Facades\Mail::raw(
                'This is a test email from ' . setting('site_name', config('app.name')),
                fn($msg) => $msg->to($request->test_email)->subject('Test Email – ' . setting('site_name', config('app.name')))
            );
            return back()->with('success', 'Test email sent to ' . $request->test_email);
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed: ' . $e->getMessage());
        }
    }
}
