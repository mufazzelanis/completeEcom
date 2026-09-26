<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\Page;
use App\Services\RecaptchaService;
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
        $page = $this->resolveAssignedPage('faq_page_id', 'faq');
        return view('pages.faq', compact('faqs', 'page'));
    }

    public function contact()
    {
        $page = $this->resolveAssignedPage('contact_page_id', 'contact');
        return view('pages.contact', compact('page'));
    }

    public function terms()
    {
        $page = $this->resolveAssignedPage('terms_page_id', 'terms-conditions');
        abort_if(!$page, 404);
        return view('pages.show', compact('page'));
    }

    public function privacy()
    {
        $page = $this->resolveAssignedPage('privacy_page_id', 'privacy-policy');
        abort_if(!$page, 404);
        return view('pages.show', compact('page'));
    }

    public function about()
    {
        $page = $this->resolveAssignedPage('about_page_id', 'about-us');
        abort_if(!$page, 404);
        return view('pages.show', compact('page'));
    }

    /**
     * Prefer the admin's Page Settings assignment (any slug) over the conventional
     * slug, so re-assigning a slot in the admin actually takes effect immediately.
     */
    private function resolveAssignedPage(string $settingKey, string $fallbackSlug): ?Page
    {
        $id = (int) setting($settingKey, 0);
        if ($id && $page = Page::where('id', $id)->active()->first()) {
            return $page;
        }

        return Page::where('slug', $fallbackSlug)->active()->first();
    }

    public function sendContact(Request $request, RecaptchaService $recaptcha)
    {
        $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|min:10',
        ]);

        $token = $request->input('recaptcha_token') ?? $request->input('g-recaptcha-response');
        if (! $recaptcha->verify($token, $request->ip())) {
            return back()->withInput()->withErrors(['recaptcha' => 'Please complete the reCAPTCHA verification.']);
        }

        // Until now this form saved nothing. Every message is now a CRM lead so it can't get lost.
        try {
            $contact = app(\App\Services\Crm\CrmContacts::class)->forEmail($request->email, 'contact_form', $request->name);
            $lead = \App\Models\Crm\CrmLead::create([
                'contact_id' => $contact?->id,
                'name' => mb_substr($request->name, 0, 250),
                'email' => $request->email,
                'source' => 'contact_form',
                'stage' => 'new',
                'interest' => mb_substr($request->subject . "

" . $request->message, 0, 5000),
            ]);
            \App\Services\AdminAlerts\AdminAlerts::notify(
                type: 'crm_lead',
                title: 'New lead: ' . $lead->name,
                body: mb_substr($request->subject, 0, 120),
                url: route('admin.crm.leads.show', $lead),
                data: ['lead_id' => $lead->id],
            );
        } catch (\Throwable $e) {
            report($e);
        }

        return back()->with('success', 'Your message has been sent! We\'ll get back to you soon.');
    }
}
