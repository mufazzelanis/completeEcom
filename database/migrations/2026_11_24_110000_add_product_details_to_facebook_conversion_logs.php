<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** What each Conversions API event was about, so Live & Traffic can show and link the product. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facebook_conversion_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable()->after('landing_page_id'); // first product in content_ids
            $table->unsignedSmallInteger('items')->nullable()->after('product_id');          // how many products the event covered
            $table->decimal('value', 12, 2)->nullable()->after('items');
            $table->string('source_url', 500)->nullable()->after('value');
        });
    }

    public function down(): void
    {
        Schema::table('facebook_conversion_logs', function (Blueprint $table) {
            $table->dropColumn(['product_id', 'items', 'value', 'source_url']);
        });
    }
};
