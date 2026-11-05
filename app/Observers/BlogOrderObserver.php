<?php

namespace App\Observers;

use App\Models\BlogConversion;
use App\Models\BlogPost;
use App\Models\BlogProductClick;
use App\Models\BlogRead;
use App\Models\Order;
use Illuminate\Support\Facades\Cookie;

/**
 * Credits an order to the blog post the shopper clicked a product in (cookie "blg" = "postId.readKey",
 * set by blog/partials/tracker, 7 days). After commit, like OrderAlertObserver: a checkout that rolls
 * back must not count as a sale. matched_revenue is the part of the order that is a product from that
 * post (attached, or clicked during that read) — the order total alone would credit a post for a
 * basket it had nothing to do with.
 */
class BlogOrderObserver
{
    public bool $afterCommit = true;

    public function created(Order $order): void
    {
        // staff orders (typed in for a phone customer, or a test order from the shop) aren't blog sales
        if ($order->created_by || $order->source === 'phone' || !app()->bound('request') || auth()->user()?->canAccessAdmin()) {
            return;
        }

        $raw = (string) request()->cookie('blg', '');
        if (!preg_match('/^(\d+)\.([A-Za-z0-9-]{8,36})$/', $raw, $m)) {
            return;
        }

        $post = BlogPost::find((int) $m[1]);
        if (!$post) {
            return;
        }
        $read = BlogRead::where('read_key', $m[2])->where('blog_post_id', $post->id)->first();

        $productIds = $post->products()->pluck('products.id')
            ->merge($read ? BlogProductClick::where('blog_read_id', $read->id)->pluck('product_id') : [])
            ->unique();
        $matched = $order->items()->whereIn('product_id', $productIds)->sum('subtotal');

        BlogConversion::firstOrCreate(['order_id' => $order->id], [
            'blog_post_id'    => $post->id,
            'blog_read_id'    => $read?->id,
            'revenue'         => (float) $order->total,
            'matched_revenue' => (float) $matched,
        ]);

        // one order per click — the next order needs a new visit to the blog to count
        Cookie::queue(Cookie::forget('blg'));
    }
}
