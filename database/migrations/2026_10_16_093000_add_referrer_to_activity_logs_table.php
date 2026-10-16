<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            // The HTTP Referer header as sent by the browser — "which medium did this
            // visit come through" (Google search, Facebook, a direct URL typed/bookmarked,
            // ...), derived from this at display time rather than stored pre-computed, so
            // relabeling what counts as which channel later doesn't require touching old rows.
            $table->string('referrer', 1000)->nullable()->after('url');
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropColumn('referrer');
        });
    }
};
