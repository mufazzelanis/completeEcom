<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_reasons', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->enum('type', ['return_in', 'damage_out', 'manual_in', 'manual_out', 'purchase_in', 'any'])->default('any');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_reasons');
    }
};
