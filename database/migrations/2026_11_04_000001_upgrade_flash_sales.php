<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flash_sales', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('name');
            $table->string('theme', 20)->default('inferno')->after('banner_color');
            $table->string('layout', 20)->default('carousel')->after('theme');
            $table->unsignedTinyInteger('home_limit')->default(12)->after('layout');
            $table->boolean('show_on_home')->default(true)->after('home_limit');
            $table->boolean('show_upcoming')->default(true)->after('show_on_home');
            $table->boolean('show_stock_bar')->default(true)->after('show_upcoming');
            $table->integer('priority')->default(0)->after('show_stock_bar');
        });

        Schema::table('flash_sale_products', function (Blueprint $table) {
            $table->unsignedInteger('per_order_limit')->default(0)->after('stock_limit'); // 0 = no limit
            $table->unsignedInteger('sort_order')->default(0)->after('sold_count');
            $table->boolean('is_featured')->default(false)->after('sort_order');
        });

        foreach (DB::table('flash_sales')->get(['id', 'name']) as $sale) {
            DB::table('flash_sales')->where('id', $sale->id)
                ->update(['slug' => (Str::slug($sale->name) ?: 'flash-sale') . '-' . $sale->id]);
        }
    }

    public function down(): void
    {
        Schema::table('flash_sale_products', function (Blueprint $table) {
            $table->dropColumn(['per_order_limit', 'sort_order', 'is_featured']);
        });

        Schema::table('flash_sales', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn(['slug', 'theme', 'layout', 'home_limit', 'show_on_home', 'show_upcoming', 'show_stock_bar', 'priority']);
        });
    }
};
