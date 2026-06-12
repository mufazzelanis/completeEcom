<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedSmallInteger('fraud_score')->default(0)->after('notes');
            $table->json('fraud_flags')->nullable()->after('fraud_score');
            $table->boolean('is_fraud_flagged')->default(false)->after('fraud_flags');
            $table->timestamp('fraud_checked_at')->nullable()->after('is_fraud_flagged');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['fraud_score', 'fraud_flags', 'is_fraud_flagged', 'fraud_checked_at']);
        });
    }
};
