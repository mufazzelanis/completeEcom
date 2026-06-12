<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->nullOnDelete()->constrained();
            $table->string('channel', 20);           // email | sms | push | whatsapp
            $table->string('event_type', 60);        // order_placed, order_status_changed, etc.
            $table->string('recipient', 255);        // email address or phone number
            $table->enum('status', ['sent', 'failed', 'skipped'])->default('sent');
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->useCurrent();

            $table->index(['channel', 'status']);
            $table->index(['user_id', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
