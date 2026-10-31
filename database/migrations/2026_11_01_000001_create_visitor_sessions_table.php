<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per storefront visit (a browser's session — a break of 30+ minutes starts a new
     * one), kept up to date by the live heartbeat (LiveVisitorController::ping). Counted by the
     * browser's own random key, not by IP, so a whole mobile network behind one IP isn't one
     * "visitor". Feeds Admin → Live Visitors: funnel, sources, peak hours, time on site.
     */
    public function up(): void
    {
        Schema::create('visitor_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('visitor_key', 40)->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_team')->default(false);
            $table->string('source', 40)->nullable();
            $table->string('device', 20)->nullable();
            $table->string('landing_type', 20)->nullable();
            $table->string('landing_url', 500)->nullable();
            $table->unsignedSmallInteger('pages')->default(0);
            $table->boolean('reached_product')->default(false);
            $table->boolean('reached_cart')->default(false);
            $table->boolean('reached_checkout')->default(false);
            $table->boolean('ordered')->default(false);
            $table->decimal('cart_max', 12, 2)->default(0);
            // nullable on purpose: a NOT NULL first timestamp gets MySQL's implicit
            // "ON UPDATE CURRENT_TIMESTAMP", which would reset the start on every heartbeat
            $table->timestamp('started_at')->nullable()->index();
            $table->timestamp('last_seen_at')->nullable();
        });

        Schema::table('live_visitors', function (Blueprint $table) {
            $table->unsignedBigInteger('session_id')->nullable()->after('visitor_key');
        });
    }

    public function down(): void
    {
        Schema::table('live_visitors', fn (Blueprint $table) => $table->dropColumn('session_id'));
        Schema::dropIfExists('visitor_sessions');
    }
};
