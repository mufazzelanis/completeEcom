<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Who triggered each Conversions API event — shown as the Visitor column in Live & Traffic → Meta events. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facebook_conversion_logs', function (Blueprint $table) {
            $table->string('ip_address', 45)->nullable()->after('source_url');
            $table->string('user_agent', 255)->nullable()->after('ip_address');
            $table->index(['ip_address', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::table('facebook_conversion_logs', function (Blueprint $table) {
            $table->dropIndex(['ip_address', 'sent_at']);
            $table->dropColumn(['ip_address', 'user_agent']);
        });
    }
};
