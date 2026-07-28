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
        Schema::table('categories', function (Blueprint $table) {
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('approved')->after('is_active');
            $table->string('rejection_reason')->nullable()->after('approval_status');
            $table->foreignId('vendor_id')->nullable()->after('rejection_reason')->constrained()->nullOnDelete();
            $table->index('approval_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vendor_id');
            $table->dropColumn(['approval_status', 'rejection_reason']);
        });
    }
};
