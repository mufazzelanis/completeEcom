<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    public function index()
    {
        $faqs = Faq::orderBy('category')->orderBy('sort_order')->get();
        $categories = Faq::whereNotNull('category')->distinct()->orderBy('category')->pluck('category');
        return view('admin.faqs.index', compact('faqs', 'categories'));
    }

    public function store(Request $request)
    {
        $request->validate(['question' => 'required|string', 'answer' => 'required|string']);

        Faq::create([
            'question'   => $request->question,
            'answer'     => $request->answer,
            'category'   => $request->filled('category') ? $request->category : null,
            'sort_order' => (int) ($request->sort_order ?? 0),
            'is_active'  => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.faqs.index')->with('success', 'FAQ added.');
    }

    public function update(Request $request, Faq $faq)
    {
        $request->validate(['question' => 'required|string', 'answer' => 'required|string']);

        $faq->update([
            'question'   => $request->question,
            'answer'     => $request->answer,
            'category'   => $request->filled('category') ? $request->category : null,
            'sort_order' => (int) ($request->sort_order ?? 0),
            'is_active'  => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.faqs.index')->with('success', 'FAQ updated.');
    }

    public function destroy(Faq $faq)
    {
        $faq->delete();
        return redirect()->route('admin.faqs.index')->with('success', 'FAQ deleted.');
    }

    public function toggle(Faq $faq)
    {
        $faq->update(['is_active' => !$faq->is_active]);
        return back();
    }
}
