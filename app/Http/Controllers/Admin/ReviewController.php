<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        // user.vendor + product.seller so the view can flag "this reviewer is a vendor"
        // (and especially "...is this product's own seller") for the moderator —
        // storeReview() now blocks a seller reviewing their own listing going forward,
        // but this still matters for reviews already submitted before that fix, and for
        // spotting a vendor leaving a review on a rival's product.
        $query = Review::with(['user.vendor', 'product.seller']);

        if ($request->filled('status')) {
            $query->where('is_approved', $request->status === 'approved');
        }

        $reviews = $query->latest()->paginate(15);
        return view('admin.reviews.index', compact('reviews'));
    }

    public function edit(Review $review)
    {
        $review->load(['user.vendor', 'product.seller']);
        return view('admin.reviews.edit', compact('review'));
    }

    public function update(Request $request, Review $review)
    {
        $data = $request->validate([
            'rating'      => 'required|integer|between:1,5',
            'comment'     => 'nullable|string|max:1000',
            'is_approved' => 'nullable|boolean',
        ]);
        $data['is_approved'] = $request->boolean('is_approved');

        $review->update($data);

        return redirect()->route('admin.reviews.index')->with('success', 'Review updated.');
    }

    public function approve(Review $review)
    {
        $review->update(['is_approved' => !$review->is_approved]);
        $message = $review->is_approved ? 'Review approved.' : 'Review unapproved.';
        return back()->with('success', $message);
    }

    public function destroy(Review $review)
    {
        $review->delete();
        return back()->with('success', 'Review deleted.');
    }
}
