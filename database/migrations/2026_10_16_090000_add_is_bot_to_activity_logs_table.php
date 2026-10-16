<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            // Computed once at write time from the User-Agent (see
            // ActivityLog::isBotUserAgent) — indexed so the admin's "Humans only" /
            // "Bots only" filter on a 20k+ row table stays a plain index lookup
            // instead of a full scan re-parsing every row's user_agent on every request.
            $table->boolean('is_bot')->default(false)->after('platform')->index();
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropColumn('is_bot');
        });
    }
};
