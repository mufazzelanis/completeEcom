<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lets a guest favorite a product without an account — same session_id
     * pattern the carts table already uses. user_id has to become nullable
     * since a guest row won't have one; done via raw SQL (not ->change())
     * because this app doesn't have doctrine/dbal installed.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE wishlists MODIFY user_id BIGINT UNSIGNED NULL');

        Schema::table('wishlists', function (Blueprint $table) {
            $table->string('session_id')->nullable()->after('user_id');
            $table->unique(['session_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::table('wishlists', function (Blueprint $table) {
            $table->dropUnique(['session_id', 'product_id']);
            $table->dropColumn('session_id');
        });

        DB::statement('ALTER TABLE wishlists MODIFY user_id BIGINT UNSIGNED NOT NULL');
    }
};
