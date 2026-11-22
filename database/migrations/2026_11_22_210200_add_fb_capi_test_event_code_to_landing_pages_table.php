<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Settings → Facebook Pixel has one site-wide facebook_capi_test_event_code field — a
     * landing page running its own separate ad account/pixel (fb_pixel_id override) had no
     * way to verify ITS OWN Conversions API events in Meta's Test Events tool independently
     * of (or without disturbing) the site-wide pixel's own verification.
     */
    public function up(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            $table->string('fb_capi_test_event_code', 50)->nullable()->after('fb_capi_access_token');
        });
    }

    public function down(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            $table->dropColumn('fb_capi_test_event_code');
        });
    }
};
