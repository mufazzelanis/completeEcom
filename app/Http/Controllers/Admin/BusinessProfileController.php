<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\BusinessProfile;
use App\Support\ImageOptimizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** Settings → Business Profile: the one place for the shop's name and contact details. */
class BusinessProfileController extends Controller
{
    public function index()
    {
        return view('admin.settings.business', [
            'profile' => BusinessProfile::current(),
            'differences' => BusinessProfile::differences(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'legal_name' => 'nullable|string|max:150',
            'phone' => 'nullable|string|max:40',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'tagline' => 'nullable|string|max:200',
            'hours' => 'nullable|string|max:120',
            'whatsapp' => 'nullable|string|max:255',
            'tax' => 'nullable|string|max:100',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:4096',
        ]);

        $alsoChanged = BusinessProfile::save($data);

        if ($request->hasFile('logo')) {
            $old = array_filter([Setting::get('site_logo'), Setting::get('invoice_logo')]);
            BusinessProfile::saveLogo(ImageOptimizer::store($request->file('logo'), 'settings', 'public', 1600));
            Storage::disk('public')->delete($old);
        }

        return back()->with('success', 'Business details saved and updated everywhere.'
            . ($alsoChanged ? ' The new shop name was also written into ' . count($alsoChanged) . ' other text(s) such as the SEO title and copyright line.' : ''));
    }
}
