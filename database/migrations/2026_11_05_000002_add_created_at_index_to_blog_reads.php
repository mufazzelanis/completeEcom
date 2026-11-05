<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Blog analytics' hourly chart (today / yesterday) and visitors:resolve-ips (every minute) both
 * filter blog_reads by created_at — without this they scan the whole table as it grows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_reads', fn (Blueprint $table) => $table->index('created_at'));
    }

    public function down(): void
    {
        Schema::table('blog_reads', fn (Blueprint $table) => $table->dropIndex(['created_at']));
    }
};
