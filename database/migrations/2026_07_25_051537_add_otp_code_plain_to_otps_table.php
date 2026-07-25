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
        Schema::table('otps', function (Blueprint $table) {
            // Dev/local convenience only — lets you read the code straight from the
            // DB when there's no real SMS gateway wired up yet. The model only fills
            // this outside production, so it's never populated on a live site.
            $table->string('otp_code_plain')->nullable()->after('otp_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('otps', function (Blueprint $table) {
            $table->dropColumn('otp_code_plain');
        });
    }
};
