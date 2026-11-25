<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which customer-matching keys each Conversions API event carried (em, ph, fbp, fbc, external_id…)
 * — the key names only, never the values — so Live & Traffic → Meta events can show how well Meta
 * could recognise the customer behind each event.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facebook_conversion_logs', function (Blueprint $table) {
            $table->string('match_keys', 120)->nullable()->after('user_agent'); // ",em,ph,fbp," — commas both ends for exact LIKE
        });
    }

    public function down(): void
    {
        Schema::table('facebook_conversion_logs', function (Blueprint $table) {
            $table->dropColumn('match_keys');
        });
    }
};
