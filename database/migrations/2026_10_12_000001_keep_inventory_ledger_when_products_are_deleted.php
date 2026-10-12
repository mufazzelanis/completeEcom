<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * stock_adjustments and purchase_items referenced products with ON DELETE RESTRICT, so any
 * product that had ever been restocked or purchased could never be deleted (the admin got a
 * 500 page). They're the inventory ledger, though — the rows must survive the product.
 *
 * Now each row keeps its own copy of the product's name/SKU, and product_id becomes nullable
 * with ON DELETE SET NULL: deleting a product really deletes it, and its stock/purchase
 * history still reads "CeraVe Cream 340g" instead of breaking or disappearing.
 */
return new class extends Migration
{
    private const TABLES = ['stock_adjustments', 'purchase_items'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropForeign(['product_id']);
            });

            Schema::table($table, function (Blueprint $t) {
                $t->unsignedBigInteger('product_id')->nullable()->change();
                $t->string('product_name')->nullable()->after('product_id');
                $t->string('product_sku')->nullable()->after('product_name');
                $t->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            });

            // Snapshot every existing row's product name/SKU while the products still exist.
            DB::table($table)->whereNotNull('product_id')->orderBy('id')->chunkById(500, function ($rows) use ($table) {
                $products = DB::table('products')->whereIn('id', $rows->pluck('product_id')->unique())->get(['id', 'name', 'sku'])->keyBy('id');
                foreach ($rows as $row) {
                    if ($product = $products->get($row->product_id)) {
                        DB::table($table)->where('id', $row->id)->update(['product_name' => $product->name, 'product_sku' => $product->sku]);
                    }
                }
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropForeign(['product_id']);
                $t->dropColumn(['product_name', 'product_sku']);
            });
            // Rows whose product has since been deleted can't satisfy the old NOT NULL
            // RESTRICT key; they're left with product_id NULL rather than being destroyed.
            Schema::table($table, function (Blueprint $t) {
                $t->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            });
        }
    }
};
