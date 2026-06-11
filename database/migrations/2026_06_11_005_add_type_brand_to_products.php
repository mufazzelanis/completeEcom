<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->enum('type', ['simple', 'variable', 'bundle', 'digital'])->default('simple')->after('id');
            $table->foreignId('brand_id')->nullable()->constrained('brands')->onDelete('set null')->after('subcategory_id');
            $table->decimal('weight', 8, 2)->nullable()->after('stock');
            $table->string('download_file')->nullable()->after('weight');
            $table->integer('download_expiry_days')->nullable()->after('download_file');
            $table->string('meta_title')->nullable()->after('is_featured');
            $table->text('meta_description')->nullable()->after('meta_title');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['brand_id']);
            $table->dropColumn(['type', 'brand_id', 'weight', 'download_file', 'download_expiry_days', 'meta_title', 'meta_description']);
        });
    }
};
