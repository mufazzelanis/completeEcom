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
        Schema::table('vendors', function (Blueprint $table) {
            $table->string('website')->nullable()->after('email');
            $table->enum('document_type', ['nid', 'birth_certificate'])->nullable()->after('rejection_reason');
            $table->string('nid_number')->nullable()->after('document_type');
            $table->string('nid_front_image')->nullable()->after('nid_number');
            $table->string('nid_back_image')->nullable()->after('nid_front_image');
            $table->string('birth_certificate_image')->nullable()->after('nid_back_image');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn(['website', 'document_type', 'nid_number', 'nid_front_image', 'nid_back_image', 'birth_certificate_image']);
        });
    }
};
