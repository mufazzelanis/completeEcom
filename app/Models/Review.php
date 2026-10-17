<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = ['user_id', 'product_id', 'order_id', 'reviewer_name', 'rating', 'comment', 'is_approved'];

    protected $casts = ['is_approved' => 'boolean'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /** The delivered order this review came from (post-delivery review request) — "Verified purchase". */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Name to show for the reviewer: the one they chose on the review-request page (default
     * "First L.", and the only name an account-less, phone-only order has), else their account name.
     */
    public function getAuthorNameAttribute(): string
    {
        return $this->reviewer_name ?: ($this->user?->name ?: 'Customer');
    }

    public function isVerifiedPurchase(): bool
    {
        return $this->order_id !== null;
    }
}
