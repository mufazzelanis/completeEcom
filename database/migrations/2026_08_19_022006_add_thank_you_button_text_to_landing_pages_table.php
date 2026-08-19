<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Label for the "Continue Shopping" button on the thank-you state (see
     * landing/show.blade.php) — nullable, falls back to the same default text it already
     * shows today, so every existing landing page keeps behaving exactly as it does now.
     */
    public function up(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            $table->string('thank_you_button_text')->nullable()->after('thank_you_redirect_url');
        });
    }

    public function down(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            $table->dropColumn('thank_you_button_text');
        });
    }
};
