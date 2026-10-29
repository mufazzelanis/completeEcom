<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin → Sales → Incomplete Checkouts: shoppers who typed a valid phone number on the checkout
 * page but didn't place the order — with what was in their cart — so the team can call or
 * message them. One row per browser session; it's marked recovered when that session (or that
 * phone) places an order.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkout_leads', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 100)->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('phone', 20)->index();
            $table->string('address', 500)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('zone', 20)->nullable();
            $table->json('items');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->string('status', 20)->default('open')->index(); // open | contacted | recovered | dismissed
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('contacted_at')->nullable();
            $table->timestamp('sms_sent_at')->nullable();
            $table->string('note', 500)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('last_activity_at')->useCurrent()->index(); // a NOT NULL timestamp needs a default under MySQL strict mode
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkout_leads');
    }
};
