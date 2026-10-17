<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin-managed URL redirects (Settings → Redirects) and the 404 log that feeds them.
 *
 *   redirects       old address (as Google still shows it) → new address, 301/302, or 410
 *                   "gone"; applied by the CustomRedirects middleware before any route runs.
 *   not_found_logs  every storefront address that ended in a 404, with how often it's hit —
 *                   so broken links that still get visits (from Google, old shares, ads) show
 *                   up on their own, ready to be redirected with one click.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->string('source', 500)->unique();   // normalized: /path or /path?query, lowercase
            $table->string('target', 1000)->nullable(); // absolute URL; null for 410
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('hits')->default(0);
            $table->timestamp('last_hit_at')->nullable();
            $table->string('note', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('not_found_logs', function (Blueprint $table) {
            $table->id();
            $table->string('path', 500)->unique();
            $table->unsignedBigInteger('hits')->default(1);
            $table->string('last_referer', 500)->nullable();
            $table->timestamp('last_hit_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('not_found_logs');
        Schema::dropIfExists('redirects');
    }
};
