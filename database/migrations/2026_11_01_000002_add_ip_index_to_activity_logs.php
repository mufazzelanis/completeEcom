<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Activity Log counts visits per IP and unique visitors — this keeps those lookups instant. */
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->index(['ip_address', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex(['ip_address', 'created_at']);
        });
    }
};
