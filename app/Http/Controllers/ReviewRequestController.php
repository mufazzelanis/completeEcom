<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * The page behind the post-delivery review link (/r/{token}, see Order::reviewUrl() and
 * reviews:send-requests): every product in that delivered order with a star rating and an
 * optional comment, no login needed — the unguessable token is the proof of purchase, which is
 * also why these reviews are marked "Verified purchase". They go through the same admin
 * approval as every other review before appearing on the product page (and in its schema).
 */
class ReviewRequestController extends Controller
{
    /** How long after delivery the link keeps working. */
    private const LINK_VALID_DAYS = 90;

    public function show(string $token)
    {
        $order = $this->order($token);
        $items = $this->reviewableItems($order);

        return view('reviews.request', [
            'order' => $order,
            'items' => $items,
            'existing' => $this->existingReviews($order, $items->pluck('product_id')),
            'defaultName' => $this->defaultName($order),
        ]);
    }

    public function store(Request $request, string $token)
    {
        $order = $this->order($token);
        $productIds = $this->reviewableItems($order)->pluck('product_id');

        $data = $request->validate([
            'name' => 'nullable|string|max:60',
            'reviews' => 'required|array',
            'reviews.*.rating' => 'nullable|integer|min:1|max:5',
            'reviews.*.comment' => 'nullable|string|max:1000',
        ]);

        $existing = $this->existingReviews($order, $productIds);
        $name = trim(strip_tags($data['name'] ?? '')) ?: $this->defaultName($order);
        $saved = 0;
        foreach ($data['reviews'] as $productId => $input) {
            $productId = (int) $productId;
            if (empty($input['rating']) || !$productIds->contains($productId)) {
                continue;
            }
            $values = [
                'rating' => (int) $input['rating'],
                'comment' => trim(strip_tags($input['comment'] ?? '')) ?: null,
                'is_approved' => false, // same moderation as reviews written on the product page
            ];
            if ($review = $existing->get($productId)) {
                // editing before approval is fine; an approved review stays as published
                if (!$review->is_approved) {
                    $review->update($values + ['reviewer_name' => $name]);
                }
            } else {
                Review::create($values + [
                    'product_id' => $productId,
                    'order_id' => $order->id,
                    'user_id' => $order->user_id,
                    'reviewer_name' => $name,
                ]);
            }
            $saved++;
        }

        return redirect()->route('reviews.request', $token)->with('review_saved', $saved);
    }

    private function order(string $token): Order
    {
        $order = Order::with('items.product', 'user')->where('review_token', $token)->first();
        abort_unless($order && in_array($order->status, ['delivered', 'completed'], true), 404);
        abort_if($order->delivered_at && $order->delivered_at->lt(now()->subDays(self::LINK_VALID_DAYS)), 410);

        return $order;
    }

    /** One entry per product still in the catalog (a deleted product can't be reviewed). */
    private function reviewableItems(Order $order)
    {
        return $order->items->filter(fn ($item) => $item->product)->unique('product_id')->values();
    }

    /**
     * Reviews already left for these products from this order — or, for a customer with an
     * account, from the product page — keyed by product, so nothing is reviewed twice.
     */
    private function existingReviews(Order $order, $productIds)
    {
        return Review::whereIn('product_id', $productIds)
            ->where(fn ($q) => $q->where('order_id', $order->id)
                ->when($order->user_id, fn ($q) => $q->orWhere('user_id', $order->user_id)))
            ->get()->keyBy('product_id');
    }

    /** "Rahim A." — the buyer's first name and last initial, never their full name or phone. */
    private function defaultName(Order $order): string
    {
        $parts = preg_split('/\s+/u', trim($order->user?->name ?: (string) $order->shipping_name), -1, PREG_SPLIT_NO_EMPTY);
        if (!$parts) {
            return 'Customer';
        }

        return count($parts) > 1 ? $parts[0] . ' ' . mb_strtoupper(mb_substr(end($parts), 0, 1)) . '.' : $parts[0];
    }
}
