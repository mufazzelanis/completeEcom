<?php

namespace App\Http\Controllers;

use App\Models\NotificationPreference;

class EmailUnsubscribeController extends Controller
{
    public function unsubscribe(string $token)
    {
        $pref = NotificationPreference::where('unsubscribe_token', $token)->firstOrFail();

        $pref->update(['email_promo' => false]);

        return view('emails.unsubscribed');
    }
}
