<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\Page;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function show(Page $page)
    {
        abort_if(!$page->is_active, 404);

        if ($page->template === 'faq') {
            $faqs = Faq::active()->orderBy('category')->orderBy('sort_order')->get()->groupBy('category');
            return view('pages.faq', compact('page', 'faqs'));
        }

        if ($page->template === 'contact') {
            return view('pages.contact', compact('page'));
        }

        return view('pages.show', compact('page'));
    }

    public function faq()
    {
        $faqs = Faq::active()->orderBy('category')->orderBy('sort_order')->get()->groupBy('category');
        $page = Page::where('slug', 'faq')->active()->first();
        return view('pages.faq', compact('faqs', 'page'));
    }

    public function contact()
    {
        $page = Page::where('slug', 'contact')->active()->first();
        return view('pages.contact', compact('page'));
    }

    public function sendContact(Request $request)
    {
        $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|min:10',
        ]);

        // Email sending can be wired here (Mail::to(...)->send(...))
        return back()->with('success', 'Your message has been sent! We\'ll get back to you soon.');
    }
}
