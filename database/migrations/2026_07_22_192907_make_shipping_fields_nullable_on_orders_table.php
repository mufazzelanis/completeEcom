<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE orders MODIFY shipping_city VARCHAR(255) NULL');
        DB::statement('ALTER TABLE orders MODIFY shipping_address TEXT NULL');
        DB::statement('ALTER TABLE orders MODIFY shipping_phone VARCHAR(255) NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("UPDATE orders SET shipping_city = '' WHERE shipping_city IS NULL");
        DB::statement("UPDATE orders SET shipping_address = '' WHERE shipping_address IS NULL");
        DB::statement("UPDATE orders SET shipping_phone = '' WHERE shipping_phone IS NULL");
        DB::statement('ALTER TABLE orders MODIFY shipping_city VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE orders MODIFY shipping_address TEXT NOT NULL');
        DB::statement('ALTER TABLE orders MODIFY shipping_phone VARCHAR(255) NOT NULL');
    }
};
