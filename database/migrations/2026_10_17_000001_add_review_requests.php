<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Post-delivery review requests (reviews:send-requests):
 *
 *   orders.delivered_at        set when an order first becomes "delivered" — the request goes
 *                              out a few days after this. Left null for orders delivered before
 *                              this feature, so deploying it never messages old customers.
 *   orders.review_requested_at when the request was sent (sent once per order)
 *   orders.review_token        the short link in the SMS (/r/{token}) — lets a customer who
 *                              ordered with just a phone number review without an account
 *   reviews.order_id           "Verified purchase": the review came from a real delivered order
 *   reviews.reviewer_name      for those account-less reviews (user_id becomes nullable)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('delivered_at')->nullable()->after('status');
            $table->timestamp('review_requested_at')->nullable()->after('delivered_at');
            $table->string('review_token', 24)->nullable()->unique()->after('review_requested_at');
            $table->index(['status', 'delivered_at', 'review_requested_at']);
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->foreignId('order_id')->nullable()->after('product_id')->constrained()->nullOnDelete();
            $table->string('reviewer_name', 100)->nullable()->after('order_id');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropConstrainedForeignId('order_id');
            $table->dropColumn('reviewer_name');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['status', 'delivered_at', 'review_requested_at']);
            $table->dropUnique(['review_token']);
            $table->dropColumn(['delivered_at', 'review_requested_at', 'review_token']);
        });
    }
};
