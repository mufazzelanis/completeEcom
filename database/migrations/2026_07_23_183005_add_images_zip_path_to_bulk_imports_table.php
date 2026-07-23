<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bulk_imports', function (Blueprint $table) {
            $table->string('images_zip_path')->nullable()->after('stored_path');
            $table->unsignedInteger('images_matched_count')->default(0)->after('skipped_count');
            $table->unsignedInteger('images_missing_count')->default(0)->after('images_matched_count');
        });
    }

    public function down(): void
    {
        Schema::table('bulk_imports', function (Blueprint $table) {
            $table->dropColumn(['images_zip_path', 'images_matched_count', 'images_missing_count']);
        });
    }
};
