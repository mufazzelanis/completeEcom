<?php

namespace App\Http\Controllers\Auth\Concerns;

use App\Models\Cart;
use App\Models\ProductView;
use App\Models\SearchQuery;
use App\Models\Wishlist;
use Illuminate\Support\Facades\Auth;

/**
 * Shared by every "a guest just became an authenticated user" path (password login,
 * social login) — a guest's session-scoped cart/wishlist/view-history/search-history
 * needs the same reassignment onto the account regardless of which door they came in
 * through. Pulled out of AuthenticatedSessionController so SocialAuthController doesn't
 * have to duplicate it (and the two can't quietly drift apart over time).
 */
trait MergesGuestSession
{
    private function mergeGuestCart(string $guestSessionId): void
    {
        $userId = Auth::id();

        $guestItems = Cart::where('session_id', $guestSessionId)->get();

        foreach ($guestItems as $guestItem) {
            $existing = Cart::where('user_id', $userId)
                ->where('product_id', $guestItem->product_id)
                ->first();

            if ($existing) {
                $existing->increment('quantity', $guestItem->quantity);
                $guestItem->delete();
            } else {
                $guestItem->update(['user_id' => $userId, 'session_id' => null]);
            }
        }
    }

    /**
     * Same merge shape as the cart above: a guest-favorited product that the
     * now-logging-in user already had saved is just dropped (no double
     * entry); everything else gets reassigned to the account.
     */
    private function mergeGuestWishlist(string $guestSessionId): void
    {
        $userId = Auth::id();

        $guestItems = Wishlist::where('session_id', $guestSessionId)->get();

        foreach ($guestItems as $guestItem) {
            $exists = Wishlist::where('user_id', $userId)
                ->where('product_id', $guestItem->product_id)
                ->exists();

            if ($exists) {
                $guestItem->delete();
            } else {
                $guestItem->update(['user_id' => $userId, 'session_id' => null]);
            }
        }
    }

    /**
     * Same idea as the cart/wishlist merges above, but additive rather than drop-the-
     * duplicate: a product viewed both as a guest and (separately, some other time) while
     * logged in should keep BOTH view counts combined — recency/frequency is the whole
     * signal this table exists for (see ProductView::record()), so merging them is more
     * correct than silently discarding one side's history.
     */
    private function mergeGuestProductViews(string $guestSessionId): void
    {
        $userId = Auth::id();

        $guestViews = ProductView::where('session_id', $guestSessionId)->get();

        foreach ($guestViews as $guestView) {
            $existing = ProductView::where('user_id', $userId)
                ->where('product_id', $guestView->product_id)
                ->first();

            if ($existing) {
                $existing->update([
                    'view_count' => $existing->view_count + $guestView->view_count,
                    'viewed_at' => max($existing->viewed_at, $guestView->viewed_at),
                ]);
                $guestView->delete();
            } else {
                $guestView->update(['user_id' => $userId, 'session_id' => null]);
            }
        }
    }

    /**
     * Search history has no natural "duplicate" to collapse (unlike a product id) beyond
     * what SearchQuery::record() already dedupes at write time, so this is just a
     * reassignment — every guest-session search becomes this account's.
     */
    private function mergeGuestSearchQueries(string $guestSessionId): void
    {
        SearchQuery::where('session_id', $guestSessionId)
            ->update(['user_id' => Auth::id(), 'session_id' => null]);
    }

    private function mergeGuestSession(string $guestSessionId): void
    {
        $this->mergeGuestCart($guestSessionId);
        $this->mergeGuestWishlist($guestSessionId);
        $this->mergeGuestProductViews($guestSessionId);
        $this->mergeGuestSearchQueries($guestSessionId);
    }
}
