<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Every Live & Traffic → Meta events filter is a time window on sent_at. */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasIndex('facebook_conversion_logs', ['sent_at'])) {
            return;
        }
        Schema::table('facebook_conversion_logs', function (Blueprint $table) {
            $table->index('sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('facebook_conversion_logs', function (Blueprint $table) {
            $table->dropIndex(['sent_at']);
        });
    }
};
