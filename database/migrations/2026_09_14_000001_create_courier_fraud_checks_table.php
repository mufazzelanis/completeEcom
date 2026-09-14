<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Every phone-number check against the courier aggregator, kept as a log (not one row
     * per phone) so re-checking the same number over time builds a history rather than
     * silently overwriting it — useful for seeing whether a customer's behavior is
     * improving or getting worse. index.blade.php's "recent checks" list and the per-order
     * panel both just read the newest row for a given phone.
     */
    public function up(): void
    {
        Schema::create('courier_fraud_checks', function (Blueprint $table) {
            $table->id();
            $table->string('phone')->index();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('provider')->default('bdcourier');
            $table->boolean('success')->default(false); // whether the API call itself succeeded
            $table->text('error')->nullable(); // API/network failure message, when success = false

            // Aggregated across every courier the provider reports on.
            $table->unsignedInteger('total_orders')->default(0);
            $table->unsignedInteger('total_delivered')->default(0);
            $table->unsignedInteger('total_cancelled')->default(0);
            $table->decimal('success_rate', 5, 2)->nullable(); // null when total_orders = 0 (no history anywhere)

            // low / medium / high / unknown — unknown means too little data to judge, not "safe".
            $table->string('risk_level')->default('unknown');

            // Full raw response, per courier — lets the order/checker page show a
            // courier-by-courier breakdown, not just the aggregate.
            $table->json('breakdown')->nullable();
            $table->json('raw_response')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_fraud_checks');
    }
};
