<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            // App\Support\LandingThemes — the same content shown in a different design.
            // Existing pages keep the design they have today.
            $table->string('theme', 20)->default('classic')->after('brand_color');
        });
    }

    public function down(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            $table->dropColumn('theme');
        });
    }
};
