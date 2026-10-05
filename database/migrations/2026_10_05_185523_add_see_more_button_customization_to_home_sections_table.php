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
        Schema::table('home_sections', function (Blueprint $table) {
            // Previously the big in-page "reveal more" pill button (home.blade.php) shared
            // view_all_label with the small "View All" link in the section header above it —
            // same text on both, even though one stays on the page and reveals more products
            // while the other navigates to the full /shop listing. Split into its own field so
            // each can read differently (e.g. "See More" here, "View All" on the link).
            $table->string('see_more_label', 40)->nullable()->after('view_all_label');
            // All nullable — unset keeps today's exact look (theme === 'sale' ? white/orange-text
            // : orange-to-red gradient/white-text, see home.blade.php), same
            // "customize if you want, otherwise unchanged" pattern as the site-wide Order Now
            // button colors (Settings → General → Storefront Buttons).
            $table->string('see_more_color_from', 7)->nullable()->after('see_more_label');
            $table->string('see_more_color_to', 7)->nullable()->after('see_more_color_from');
            $table->string('see_more_text_color', 7)->nullable()->after('see_more_color_to');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('home_sections', function (Blueprint $table) {
            $table->dropColumn(['see_more_label', 'see_more_color_from', 'see_more_color_to', 'see_more_text_color']);
        });
    }
};
