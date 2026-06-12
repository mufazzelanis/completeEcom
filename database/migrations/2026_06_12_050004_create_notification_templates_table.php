<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 60);   // order_placed, order_status_changed, etc.
            $table->string('channel', 20);       // email | sms | push | whatsapp
            $table->string('recipient', 20);     // customer | admin
            $table->string('subject')->nullable(); // email only
            $table->text('body');                // template with {{placeholders}}
            $table->string('push_title')->nullable(); // push only
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['event_type', 'channel', 'recipient']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
