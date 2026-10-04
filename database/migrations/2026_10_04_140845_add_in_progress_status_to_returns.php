<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE returns MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'completed', 'in_progress') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("UPDATE returns SET status = 'approved' WHERE status = 'in_progress'");
        DB::statement("ALTER TABLE returns MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'completed') NOT NULL DEFAULT 'pending'");
    }
};
