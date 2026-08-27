<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // 'web' covers every order placed today (regular checkout, guest checkout,
            // landing pages — landing_page_id already distinguishes those) — defaulted so
            // every existing row backfills correctly with no separate data migration.
            // 'phone' is for orders an admin types in on a customer's behalf after a call.
            $table->string('source', 20)->default('web')->after('landing_page_id');
            // Which admin actually created a manually-entered order — nullOnDelete so
            // deleting that admin's user account later doesn't cascade into deleting the
            // order history they created.
            $table->foreignId('created_by')->nullable()->after('source')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn('source');
        });
    }
};
