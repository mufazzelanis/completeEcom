<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Enum column: doctrine/dbal isn't installed so Blueprint::change() isn't
        // available — a plain MODIFY is the simplest way to widen the enum.
        DB::statement("ALTER TABLE vendors MODIFY status ENUM('pending', 'approved', 'rejected', 'suspended', 'needs_correction') NOT NULL DEFAULT 'pending'");

        Schema::table('vendors', function (Blueprint $table) {
            $table->text('correction_notes')->nullable()->after('rejection_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn('correction_notes');
        });

        DB::statement("ALTER TABLE vendors MODIFY status ENUM('pending', 'approved', 'rejected', 'suspended') NOT NULL DEFAULT 'pending'");
    }
};
