<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            // All nullable — an unset field falls back to the site-wide Facebook Pixel /
            // Google Analytics / Google Ads settings (Settings → Facebook Pixel / Google
            // Analytics & Ads), same override-or-inherit pattern as brand_color.
            $table->string('fb_pixel_id', 32)->nullable()->after('brand_color');
            $table->string('ga_measurement_id', 20)->nullable()->after('fb_pixel_id');
            $table->string('google_ads_conversion_id', 20)->nullable()->after('ga_measurement_id');
            $table->string('google_ads_conversion_label', 60)->nullable()->after('google_ads_conversion_id');
        });
    }

    public function down(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            $table->dropColumn(['fb_pixel_id', 'ga_measurement_id', 'google_ads_conversion_id', 'google_ads_conversion_label']);
        });
    }
};
