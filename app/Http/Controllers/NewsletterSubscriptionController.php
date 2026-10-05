<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use App\Services\AdminAlerts\AdminAlerts;
use App\Services\RecaptchaService;
use App\Services\SpamGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class NewsletterSubscriptionController extends Controller
{
    public function subscribe(Request $request, RecaptchaService $recaptcha)
    {
        // Not $request->validate() — this app's exception handler only renders validation
        // failures as JSON for api/* routes (see bootstrap/app.php), so that shorthand would
        // always redirect here regardless of Accept header, defeating the footer newsletter
        // form's AJAX flow. Handling it manually keeps this route's JSON contract intact.
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
        ]);
        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $validator->errors()->first(),
                    'errors'  => $validator->errors(),
                ], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        $token = $request->input('recaptcha_token') ?? $request->input('g-recaptcha-response');
        if (! $recaptcha->verify($token, $request->ip())) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Please complete the reCAPTCHA verification.'], 422);
            }
            return back()->withErrors(['recaptcha' => 'Please complete the reCAPTCHA verification.']);
        }

        // See PageController::sendContact() for why a flagged submission still gets
        // saved (never silently drop a possibly-real signup) instead of rejected.
        $spamReason = SpamGuard::check($request);

        $existing = NewsletterSubscriber::where('email', $request->email)->first();

        $attributes = [
            'ip_address'  => $request->ip(),
            'user_agent'  => mb_substr((string) $request->userAgent(), 0, 250),
            'is_spam'     => $spamReason !== null,
            'spam_reason' => $spamReason,
        ];

        if ($existing && $existing->is_active) {
            $existing->update($attributes);
            if ($request->expectsJson()) {
                return response()->json(['message' => 'You are already subscribed to our newsletter!']);
            }
            return back()->with('success', 'You are already subscribed to our newsletter!');
        }

        if ($existing) {
            $existing->update($attributes + [
                'is_active'        => true,
                'subscribed_at'    => now(),
                'unsubscribed_at'  => null,
            ]);
        } else {
            NewsletterSubscriber::create($attributes + [
                'email'             => $request->email,
                'is_active'         => true,
                'unsubscribe_token' => Str::random(48),
                'subscribed_at'     => now(),
            ]);
        }

        try {
            $crm = app(\App\Services\Crm\CrmContacts::class);
            if ($contact = $crm->forEmail($request->email, 'newsletter')) {
                $crm->tag($contact, 'Newsletter', 'indigo');
            }
        } catch (\Throwable $e) {
            report($e);
        }

        if ($spamReason === null) {
            AdminAlerts::notify(
                type: 'subscriber',
                title: 'New newsletter subscriber',
                body: $request->email,
                url: route('admin.newsletter.index'),
            );
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Thank you for subscribing to our newsletter!']);
        }

        return back()->with('success', 'Thank you for subscribing to our newsletter!');
    }

    public function unsubscribe(string $token)
    {
        $subscriber = NewsletterSubscriber::where('unsubscribe_token', $token)->firstOrFail();

        $subscriber->update([
            'is_active'        => false,
            'unsubscribed_at'  => now(),
        ]);

        return view('newsletter.unsubscribed');
    }
}
