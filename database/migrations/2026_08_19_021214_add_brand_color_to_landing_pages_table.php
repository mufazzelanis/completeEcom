<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-landing-page accent color — every button/price/border on the page currently
     * always uses the site-wide primary_color setting. This lets one campaign page run a
     * different color (to match a specific ad's creative, say) without touching the main
     * site's branding. Nullable, falls back to the site-wide color when unset.
     */
    public function up(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            $table->string('brand_color', 7)->nullable()->after('order_button_text');
        });
    }

    public function down(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            $table->dropColumn('brand_color');
        });
    }
};
