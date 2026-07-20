<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function index(Request $request)
    {
        $query = NewsletterSubscriber::query();

        if ($request->filled('search')) {
            $query->where('email', 'like', '%'.$request->search.'%');
        }
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $subscribers = $query->latest('subscribed_at')->paginate(30)->withQueryString();
        $activeCount = NewsletterSubscriber::where('is_active', true)->count();

        return view('admin.newsletter.index', compact('subscribers', 'activeCount'));
    }

    public function destroy(NewsletterSubscriber $subscriber)
    {
        $subscriber->delete();

        return back()->with('success', 'Subscriber removed.');
    }
}
