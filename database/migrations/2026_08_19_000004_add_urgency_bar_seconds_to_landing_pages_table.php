<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Splits the urgency bar countdown into minutes + seconds so the admin can set an exact
     * time (e.g. 9:45) rather than only whole minutes — the "why exactly 9:45 and not 10:00"
     * feel is the whole point of a countdown like this. urgency_bar_minutes stays but its
     * practical range is now clamped 0-9 in the admin form/validation (see
     * Admin\LandingPageController@validated) so the total always stays under 10 minutes,
     * matching the urgency bar's own "order within 10 minutes" copy.
     */
    public function up(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            $table->unsignedTinyInteger('urgency_bar_seconds')->default(0)->after('urgency_bar_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            $table->dropColumn('urgency_bar_seconds');
        });
    }
};
