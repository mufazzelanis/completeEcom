<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('home_sections', function (Blueprint $table) {
            $table->string('view_all_label', 40)->nullable()->after('view_all_query');
        });
    }

    public function down(): void
    {
        Schema::table('home_sections', function (Blueprint $table) {
            $table->dropColumn('view_all_label');
        });
    }
};
