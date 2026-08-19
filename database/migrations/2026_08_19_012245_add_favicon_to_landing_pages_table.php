<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-landing-page favicon override — same pattern as header_logo (nullable, falls back
     * to the main site's global favicon setting when not set), so a campaign-specific
     * landing page can have its own browser-tab icon without touching site-wide branding.
     */
    public function up(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            $table->string('favicon')->nullable()->after('header_logo');
        });
    }

    public function down(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            $table->dropColumn('favicon');
        });
    }
};
