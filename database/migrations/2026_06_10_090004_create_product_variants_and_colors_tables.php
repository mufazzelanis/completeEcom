<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('name');                          // e.g. "Small", "M", "XL"
            $table->string('sku')->nullable();
            $table->decimal('price', 10, 2)->nullable();     // null = use product base price
            $table->integer('stock')->default(0);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('product_colors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('name');                          // e.g. "Red", "Navy Blue"
            $table->string('hex_code')->nullable();          // e.g. "#e53e3e"
            $table->string('image')->nullable();             // optional color swatch image
            $table->integer('stock')->nullable();            // null = uses main product stock
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_colors');
        Schema::dropIfExists('product_variants');
    }
};
