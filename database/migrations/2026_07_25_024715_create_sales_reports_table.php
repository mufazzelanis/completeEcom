<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sales_reports', function (Blueprint $table) {
            $table->id();
            $table->date('report_date')->unique();
            $table->unsignedInteger('total_orders')->default(0);
            $table->decimal('total_revenue', 12, 2)->default(0);
            $table->decimal('total_discounts', 12, 2)->default(0);
            $table->decimal('total_shipping', 12, 2)->default(0);
            $table->decimal('total_tax', 12, 2)->default(0);
            $table->decimal('avg_order_value', 12, 2)->default(0);
            $table->unsignedInteger('cancelled_orders')->default(0);
            $table->decimal('cancelled_revenue', 12, 2)->default(0);
            $table->json('payment_breakdown')->nullable();
            $table->json('status_breakdown')->nullable();
            $table->string('top_category')->nullable();
            $table->decimal('top_category_revenue', 12, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_reports');
    }
};
