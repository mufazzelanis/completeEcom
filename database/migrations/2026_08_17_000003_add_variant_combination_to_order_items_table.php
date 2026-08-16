<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Nullable + set-null-on-delete (never cascade): an order line is a
            // historical record and must survive the combination it was bought
            // as being edited or removed later. variant_label is a text snapshot
            // ("Red / Large") kept for display precisely for that reason.
            $table->foreignId('product_variant_combination_id')->nullable()->after('product_id')
                ->constrained('product_variant_combinations')->nullOnDelete();
            $table->string('variant_label')->nullable()->after('product_name');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['product_variant_combination_id']);
            $table->dropColumn(['product_variant_combination_id', 'variant_label']);
        });
    }
};
