<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_leads', function (Blueprint $table) {
            $table->string('ip_address', 45)->nullable()->after('source');
            $table->string('user_agent')->nullable()->after('ip_address');
            $table->boolean('is_spam')->default(false)->after('user_agent');
            $table->string('spam_reason', 50)->nullable()->after('is_spam');
        });

        Schema::table('newsletter_subscribers', function (Blueprint $table) {
            $table->string('ip_address', 45)->nullable()->after('email');
            $table->string('user_agent')->nullable()->after('ip_address');
            $table->boolean('is_spam')->default(false)->after('user_agent');
            $table->string('spam_reason', 50)->nullable()->after('is_spam');
        });
    }

    public function down(): void
    {
        Schema::table('crm_leads', function (Blueprint $table) {
            $table->dropColumn(['ip_address', 'user_agent', 'is_spam', 'spam_reason']);
        });

        Schema::table('newsletter_subscribers', function (Blueprint $table) {
            $table->dropColumn(['ip_address', 'user_agent', 'is_spam', 'spam_reason']);
        });
    }
};
