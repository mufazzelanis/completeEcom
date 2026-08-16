<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Snapshot, not a live read of the product's download_expiry_days —
            // computed once at purchase so a later admin edit to that setting
            // can't retroactively shrink an existing customer's access.
            $table->timestamp('download_expires_at')->nullable()->after('variant_label');
            $table->unsignedInteger('download_count')->default(0)->after('download_expires_at');
            $table->timestamp('last_downloaded_at')->nullable()->after('download_count');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['download_expires_at', 'download_count', 'last_downloaded_at']);
        });
    }
};
