<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin → Live Visitors: one row per browser currently (or recently) on the storefront,
 * refreshed by a small heartbeat the page sends every ~20 s (LiveVisitorController::ping).
 * "Live" = last_seen_at within the last minute. Rows are pruned after two days.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('live_visitors', function (Blueprint $table) {
            $table->id();
            $table->string('visitor_key', 40)->unique();      // random id kept in the visitor's browser
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->string('device', 20)->nullable();
            $table->string('browser', 40)->nullable();
            $table->string('platform', 40)->nullable();
            $table->string('source', 40)->nullable();          // Google / Facebook / Direct … on arrival
            $table->string('page_type', 20)->default('other');  // home, product, category, brand, search, cart, checkout, blog, account, other
            $table->string('url', 500)->nullable();
            $table->string('title', 255)->nullable();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('cart_count')->default(0);
            $table->decimal('cart_total', 12, 2)->default(0);
            $table->json('path')->nullable();                  // pages visited this session [{t, title, url, at}]
            $table->unsignedInteger('pings')->default(0);
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('page_started_at')->nullable();
            $table->timestamp('last_seen_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_visitors');
    }
};
