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
        Schema::create('otps', function (Blueprint $table) {
            $table->id();
            // Phone or email being verified — not a foreign key, since an OTP is often
            // sent before any account exists yet (registration, guest checkout).
            $table->string('identifier');
            // Purpose keeps this one table reusable across every flow (registration,
            // login, password reset, checkout phone verification, etc.) instead of a
            // separate table per feature.
            $table->string('purpose')->default('verification');
            // Hashed, not plain — same reasoning as passwords: if the DB ever leaks,
            // a stolen row shouldn't hand out a live code (still short-lived either way).
            $table->string('otp_code');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['identifier', 'purpose']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otps');
    }
};
