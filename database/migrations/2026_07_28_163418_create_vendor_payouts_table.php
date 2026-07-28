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
        Schema::create('vendor_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('method')->nullable();
            $table->string('reference')->nullable();
            $table->string('notes')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('vendor_id');
        });

        Schema::table('vendor_transactions', function (Blueprint $table) {
            $table->foreignId('payout_id')->nullable()->after('status')->constrained('vendor_payouts')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendor_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payout_id');
        });

        Schema::dropIfExists('vendor_payouts');
    }
};
