<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->nullOnDelete()->constrained();
            $table->string('type', 20)->default('web'); // web | fcm
            $table->text('endpoint');                   // web push endpoint OR fcm token
            $table->text('p256dh')->nullable();         // web push key
            $table->text('auth')->nullable();           // web push auth
            $table->string('device_type', 20)->default('desktop'); // desktop | mobile | tablet
            $table->string('browser', 50)->nullable();
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
    }
};
