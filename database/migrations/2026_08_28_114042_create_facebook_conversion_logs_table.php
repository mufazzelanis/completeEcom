<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facebook_conversion_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event_name', 40);       // Purchase, InitiateCheckout, AddToCart, ViewContent
            $table->string('event_id', 80)->index(); // shared with the browser pixel's fbq(...,{eventID}) call, for dedup
            $table->string('pixel_id', 40)->nullable();
            $table->enum('status', ['sent', 'failed'])->default('sent');
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->text('response')->nullable();    // Graph API's response body (events_received, fbtrace_id, or the error)
            $table->timestamp('sent_at')->useCurrent();

            $table->index(['event_name', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facebook_conversion_logs');
    }
};
