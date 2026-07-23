<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\Translation;
use Illuminate\Http\Request;

class TranslationController extends Controller
{
    public function index(Request $request)
    {
        $languages = Language::orderBy('sort_order')->get();

        $keys = Translation::query()
            ->when($request->filled('group'), fn ($q) => $q->where('group', $request->group))
            ->when($request->filled('search'), fn ($q) => $q->where('key', 'like', '%' . $request->search . '%'))
            ->orderBy('group')
            ->orderBy('key')
            ->pluck('key')
            ->unique()
            ->values();

        $rows = Translation::whereIn('key', $keys)->get()->groupBy('key');
        $groups = Translation::query()->distinct()->orderBy('group')->pluck('group');

        return view('admin.translations.index', compact('languages', 'keys', 'rows', 'groups'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'key'   => 'required|string|max:150',
            'group' => 'nullable|string|max:50',
        ]);

        $group = $request->group ?: 'common';
        foreach (Language::orderBy('sort_order')->get() as $lang) {
            $value = $request->input('value_' . $lang->code);
            if ($value !== null && $value !== '') {
                Translation::set($request->key, $lang->code, $value, $group);
            }
        }

        Translation::bust();

        return back()->with('success', 'Translation saved.');
    }

    public function update(Request $request)
    {
        $request->validate(['key' => 'required|string|max:150']);

        foreach (Language::orderBy('sort_order')->get() as $lang) {
            if ($request->has('value_' . $lang->code)) {
                Translation::set($request->key, $lang->code, (string) $request->input('value_' . $lang->code), $request->input('group', 'common'));
            }
        }

        Translation::bust();

        return back()->with('success', 'Translation updated.');
    }

    public function destroy(Request $request)
    {
        $request->validate(['key' => 'required|string|max:150']);

        Translation::where('key', $request->key)->delete();
        Translation::bust();

        return back()->with('success', 'Translation key removed.');
    }
}
