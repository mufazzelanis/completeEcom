<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Same reasoning as products_listing_index (see that migration) — ShopController's other
     * three sort options (Price: Low to High / High to Low, Most Popular) each filter on
     * is_active + approval_status and then ORDER BY a column with no index backing it, so
     * MySQL reads and filesorts the whole matching set on every single request for that sort,
     * same as the default-sort problem that index already fixed.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index(['is_active', 'approval_status', 'price'], 'products_price_sort_index');
            $table->index(['is_active', 'approval_status', 'views'], 'products_popularity_sort_index');
            // Also the category/brand listing pages' own default order (ShopController::listing()).
            $table->index(['is_active', 'approval_status', 'sort_order'], 'products_manual_sort_index');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_price_sort_index');
            $table->dropIndex('products_popularity_sort_index');
            $table->dropIndex('products_manual_sort_index');
        });
    }
};
