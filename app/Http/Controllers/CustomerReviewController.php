<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;

class CustomerReviewController extends Controller
{
    public function index()
    {
        $reviews = Review::where('user_id', auth()->id())
            ->with('product')
            ->latest()
            ->paginate(10);
        return view('account.reviews.index', compact('reviews'));
    }

    public function edit(Review $review)
    {
        abort_unless($review->user_id === auth()->id(), 403);
        return view('account.reviews.edit', compact('review'));
    }

    public function update(Request $request, Review $review)
    {
        abort_unless($review->user_id === auth()->id(), 403);

        $data = $request->validate([
            'rating'  => 'required|integer|between:1,5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $review->update($data + ['is_approved' => false]);
        return redirect()->route('account.reviews.index')->with('success', 'Review updated. It will be visible once approved.');
    }

    public function destroy(Review $review)
    {
        abort_unless($review->user_id === auth()->id(), 403);
        $review->delete();
        return back()->with('success', 'Review deleted.');
    }
}
