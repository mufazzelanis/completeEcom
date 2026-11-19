<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            // [{"qty": 2, "price": 5000}, ...] — an explicit TOTAL price for buying that many
            // units, set by the admin per campaign. Quantity 1 is never stored here; it's
            // always the page's own effective_price. Any quantity without a matching row just
            // falls back to effective_price * qty (today's plain linear behavior).
            $table->json('quantity_tiers')->nullable()->after('compare_at_price');

            // Null/0 = feature off. When set, a visitor who completes their order within this
            // many minutes of first viewing the page gets delivery free, regardless of the
            // delivery_mode/zone charge below — enforced server-side in
            // LandingPageController@order against a signed, tamper-proof cookie set at
            // LandingPageController@show, never trusted from client input.
            $table->unsignedSmallInteger('free_delivery_minutes')->nullable()->after('delivery_mode');
        });
    }

    public function down(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            $table->dropColumn(['quantity_tiers', 'free_delivery_minutes']);
        });
    }
};
