<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE home_sections MODIFY COLUMN source_type ENUM('featured','top_selling','new_arrivals','on_sale','category','personalized') NOT NULL DEFAULT 'category'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE home_sections MODIFY COLUMN source_type ENUM('featured','top_selling','new_arrivals','on_sale','category') NOT NULL DEFAULT 'category'");
    }
};
