<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $query = Review::with(['user', 'product']);

        if ($request->filled('status')) {
            $query->where('is_approved', $request->status === 'approved');
        }

        $reviews = $query->latest()->paginate(15);
        return view('admin.reviews.index', compact('reviews'));
    }

    public function edit(Review $review)
    {
        $review->load(['user', 'product']);
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
