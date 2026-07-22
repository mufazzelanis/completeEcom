<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE email_campaign_logs MODIFY status ENUM('pending', 'sent', 'failed') NOT NULL DEFAULT 'pending'");
        DB::statement('ALTER TABLE email_campaign_logs MODIFY sent_at TIMESTAMP NULL DEFAULT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE email_campaign_logs MODIFY status ENUM('sent', 'failed') NOT NULL");
        DB::statement('ALTER TABLE email_campaign_logs MODIFY sent_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
    }
};
