<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('payment_method_id')->nullable()->constrained()->onDelete('set null');
            $table->string('payment_method_slug');         // snapshot — survives method deletion
            $table->string('payment_method_name');
            $table->decimal('amount', 10, 2);
            $table->decimal('charge', 10, 2)->default(0);  // gateway fee
            $table->enum('status', ['pending', 'pending_verification', 'completed', 'failed', 'refunded'])->default('pending');
            $table->string('transaction_id')->nullable();   // customer-provided
            $table->string('sender_number')->nullable();    // mobile banking sender
            $table->string('gateway_ref')->nullable();      // real gateway reference
            $table->text('gateway_response')->nullable();   // raw gateway JSON
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->text('admin_note')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
        });

        // Add payment_id to orders and change payment_method to varchar
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('payment_id')->nullable()->after('id')->constrained('payments')->onDelete('set null');
            $table->decimal('payment_charge', 10, 2)->default(0)->after('payment_status');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['payment_id']);
            $table->dropColumn(['payment_id', 'payment_charge']);
        });
        Schema::dropIfExists('payments');
    }
};
