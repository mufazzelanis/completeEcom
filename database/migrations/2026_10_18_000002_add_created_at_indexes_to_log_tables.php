<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin → Activity Log lists newest first and filters by period; with no index on created_at
 * alone, every page (and every filter change) had to sort the whole table — ~30k rows on the
 * live site, which made the page so slow that filtering looked like it did nothing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', fn (Blueprint $table) => $table->index('created_at'));
        Schema::table('audit_logs', fn (Blueprint $table) => $table->index('created_at'));
    }

    public function down(): void
    {
        Schema::table('activity_logs', fn (Blueprint $table) => $table->dropIndex(['created_at']));
        Schema::table('audit_logs', fn (Blueprint $table) => $table->dropIndex(['created_at']));
    }
};
