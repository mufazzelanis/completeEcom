<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            // A Conversions API access token is tied to one specific pixel ID on Meta's side —
            // a landing page with its own fb_pixel_id (a separate ad account/campaign from the
            // main store) can't actually authenticate server-side events with the site-wide
            // Settings → Facebook Pixel token, since that token is scoped to the site's own
            // pixel. Without this, every CAPI call for such a page would fail with an auth
            // error (logged, but silently — nothing in the UI would explain why). Nullable:
            // leaving it blank is fine for the common case of no custom fb_pixel_id at all, or
            // when the page's pixel happens to live under the same ad account as the site's.
            $table->string('fb_capi_access_token', 512)->nullable()->after('fb_pixel_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            $table->dropColumn('fb_capi_access_token');
        });
    }
};
