<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Shop / category / homepage listings all filter on is_active + approval_status and sort
     * newest first — without this MySQL read ~1.8k rows and filesorted them on every page view.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index(['is_active', 'approval_status', 'created_at'], 'products_listing_index');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_listing_index');
        });
    }
};
