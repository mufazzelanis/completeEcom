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
        Schema::table('vendors', function (Blueprint $table) {
            $table->json('pending_changes')->nullable()->after('payout_details');
            $table->enum('profile_status', ['none', 'pending', 'rejected'])->default('none')->after('pending_changes');
            $table->string('profile_rejection_reason')->nullable()->after('profile_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn(['pending_changes', 'profile_status', 'profile_rejection_reason']);
        });
    }
};
