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
        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('noindex')->default(false)->after('og_image');
            $table->boolean('nofollow')->default(false)->after('noindex');
            $table->boolean('nosnippet')->default(false)->after('nofollow');
            $table->boolean('noimageindex')->default(false)->after('nosnippet');
            $table->string('robots_meta')->nullable()->after('noimageindex');
            $table->string('redirect_url')->nullable()->after('robots_meta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['noindex', 'nofollow', 'nosnippet', 'noimageindex', 'robots_meta', 'redirect_url']);
        });
    }
};
