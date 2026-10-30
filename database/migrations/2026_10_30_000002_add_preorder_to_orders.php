<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pre-orders: an out-of-stock product can still be ordered — the shopper pays only the
 * delivery charge up front by bKash / Nagad (advance_amount) and the rest on delivery.
 * Pre-orders never take or give back stock (there was none to take).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('is_preorder')->default(false)->after('source')->index();
            $table->decimal('advance_amount', 12, 2)->default(0)->after('is_preorder');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['is_preorder', 'advance_amount']);
        });
    }
};
