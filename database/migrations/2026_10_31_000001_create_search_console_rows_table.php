<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Google Search Console performance, one row per day × search query × page (synced daily —
     * App\Services\SearchConsole). product_id ties a row to the product whose page it was, so
     * Admin → SEO Insights can show each product's keywords, positions and what to improve.
     */
    public function up(): void
    {
        Schema::create('search_console_rows', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('query', 500);
            $table->char('query_hash', 32);
            $table->string('page', 700);
            $table->char('page_hash', 32);
            $table->string('page_type', 20)->nullable();      // product / category / brand / blog / home / other
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('clicks')->default(0);
            $table->unsignedInteger('impressions')->default(0);
            $table->float('position')->default(0);
            $table->unique(['date', 'query_hash', 'page_hash']);
            $table->index(['product_id', 'date']);
            $table->index(['query_hash', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_console_rows');
    }
};
