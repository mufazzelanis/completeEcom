<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remarks on an order — who wrote what and when (call outcomes, courier notes, payment
     * notes…), with an optional pin and call-back time. Staff only, never shown to the customer.
     * The old single "internal note" (orders.admin_note) becomes each order's first remark.
     */
    public function up(): void
    {
        Schema::create('order_remarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20)->default('note');          // note · call · courier · payment · customer
            $table->string('outcome', 30)->nullable();            // call: confirmed · no_answer · call_later · cancel_request · wrong_number
            $table->text('body')->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->timestamp('follow_up_at')->nullable();        // "call back at …"
            $table->timestamps();
            $table->index(['order_id', 'created_at']);
            $table->index('follow_up_at');
        });

        $now = now();
        DB::table('orders')->whereNotNull('admin_note')->where('admin_note', '!=', '')->orderBy('id')
            ->select('id', 'admin_note', 'updated_at')
            ->chunk(500, function ($orders) use ($now) {
                DB::table('order_remarks')->insert($orders->map(fn ($o) => [
                    'order_id' => $o->id, 'type' => 'note', 'body' => $o->admin_note,
                    'created_at' => $o->updated_at ?? $now, 'updated_at' => $now,
                ])->all());
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_remarks');
    }
};
