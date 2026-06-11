<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Change ENUM to VARCHAR so dynamic payment method slugs work
        DB::statement("ALTER TABLE orders MODIFY payment_method VARCHAR(100) NOT NULL DEFAULT 'cod'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE orders MODIFY payment_method ENUM('cod','card','bkash','nagad') NOT NULL DEFAULT 'cod'");
    }
};
