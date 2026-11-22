<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Without this, the CAPI log table had no way to answer "is landing page X's pixel/CAPI
     * actually working?" — only site-wide totals. Nullable: every other call site
     * (products/cart/checkout) simply never sets it.
     */
    public function up(): void
    {
        Schema::table('facebook_conversion_logs', function (Blueprint $table) {
            $table->foreignId('landing_page_id')->nullable()->after('pixel_id')
                ->constrained()->nullOnDelete();
            $table->index(['landing_page_id', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::table('facebook_conversion_logs', function (Blueprint $table) {
            $table->dropIndex(['landing_page_id', 'sent_at']);
            $table->dropConstrainedForeignId('landing_page_id');
        });
    }
};
