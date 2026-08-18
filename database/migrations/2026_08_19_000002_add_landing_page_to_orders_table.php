<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Landing-page orders flow through the exact same Order/OrderItem pipeline as
            // cart checkouts (same admin fulfillment screen, reports, notifications) — this
            // is just a tag so admin can see/filter which orders a given landing page drove.
            // set-null (not cascade): deleting a landing page later must never delete real
            // orders that already happened through it.
            $table->foreignId('landing_page_id')->nullable()->after('user_id')
                ->constrained()->nullOnDelete();
            // Responses to the landing page's admin-defined *extra* order-form fields —
            // the standard name/phone/address ones already have real shipping_* columns.
            $table->json('landing_page_data')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['landing_page_id']);
            $table->dropColumn(['landing_page_id', 'landing_page_data']);
        });
    }
};
