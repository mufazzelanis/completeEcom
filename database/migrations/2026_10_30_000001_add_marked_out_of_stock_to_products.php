<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin's manual "out of stock" switch (Products list / bulk action): shows the product as out
 * of stock and blocks ordering it, without touching the real stock count — switch it back and
 * the same stock is sellable again (and "notify me" shoppers get told).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('marked_out_of_stock')->default(false)->after('stock')->index();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('marked_out_of_stock');
        });
    }
};
