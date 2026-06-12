<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            // Email
            $table->boolean('email_order')->default(true);
            $table->boolean('email_return')->default(true);
            $table->boolean('email_ticket')->default(true);
            $table->boolean('email_promo')->default(false);
            // SMS
            $table->boolean('sms_order')->default(false);
            $table->boolean('sms_return')->default(false);
            $table->boolean('sms_ticket')->default(false);
            $table->boolean('sms_promo')->default(false);
            // Push
            $table->boolean('push_order')->default(true);
            $table->boolean('push_return')->default(true);
            $table->boolean('push_ticket')->default(true);
            $table->boolean('push_promo')->default(false);
            // WhatsApp
            $table->boolean('whatsapp_order')->default(false);
            $table->boolean('whatsapp_return')->default(false);
            $table->boolean('whatsapp_ticket')->default(false);
            $table->boolean('whatsapp_promo')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
