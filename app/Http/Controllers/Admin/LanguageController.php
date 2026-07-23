<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Language;
use Illuminate\Http\Request;

class LanguageController extends Controller
{
    public function index()
    {
        $languages = Language::orderBy('sort_order')->orderBy('name')->get();
        return view('admin.languages.index', compact('languages'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'code'        => 'required|string|max:10|unique:languages,code',
            'name'        => 'required|string|max:100',
            'native_name' => 'required|string|max:100',
            'flag_emoji'  => 'nullable|string|max:10',
            'direction'   => 'required|in:ltr,rtl',
        ]);

        Language::create([
            'code'        => strtolower($request->code),
            'name'        => $request->name,
            'native_name' => $request->native_name,
            'flag_emoji'  => $request->flag_emoji,
            'direction'   => $request->direction,
            'is_active'   => true,
            'sort_order'  => (Language::max('sort_order') ?? 0) + 1,
        ]);

        Language::bust();

        return back()->with('success', 'Language added.');
    }

    public function update(Request $request, Language $language)
    {
        $request->validate([
            'name'        => 'required|string|max:100',
            'native_name' => 'required|string|max:100',
            'flag_emoji'  => 'nullable|string|max:10',
            'direction'   => 'required|in:ltr,rtl',
        ]);

        $language->update($request->only(['name', 'native_name', 'flag_emoji', 'direction']));
        Language::bust();

        return back()->with('success', 'Language updated.');
    }

    public function destroy(Language $language)
    {
        if ($language->is_default) {
            return back()->with('error', 'Cannot delete the default language. Set another language as default first.');
        }

        $language->delete();
        Language::bust();

        return back()->with('success', 'Language removed.');
    }

    public function toggle(Language $language)
    {
        if ($language->is_default && $language->is_active) {
            return back()->with('error', 'Cannot disable the default language. Set another language as default first.');
        }

        $language->update(['is_active' => !$language->is_active]);
        Language::bust();

        return back()->with('success', 'Language ' . ($language->is_active ? 'enabled' : 'disabled') . '.');
    }

    public function setDefault(Language $language)
    {
        if (!$language->is_active) {
            return back()->with('error', 'Enable this language before making it the default.');
        }

        Language::where('id', '!=', $language->id)->update(['is_default' => false]);
        $language->update(['is_default' => true]);
        Language::bust();

        return back()->with('success', $language->name . ' is now the default language.');
    }
}
