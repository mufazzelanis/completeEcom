<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NewsletterSubscriptionController extends Controller
{
    public function subscribe(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $existing = NewsletterSubscriber::where('email', $request->email)->first();

        if ($existing && $existing->is_active) {
            return back()->with('success', 'You are already subscribed to our newsletter!');
        }

        if ($existing) {
            $existing->update([
                'is_active'        => true,
                'subscribed_at'    => now(),
                'unsubscribed_at'  => null,
            ]);
        } else {
            NewsletterSubscriber::create([
                'email'             => $request->email,
                'is_active'         => true,
                'unsubscribe_token' => Str::random(48),
                'subscribed_at'     => now(),
            ]);
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
