<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per (alert, admin-panel user) — not one shared row per event — so every staff
     * member has their own read/unread state: one person opening an order alert must not
     * silently clear it for the rest of the team.
     */
    public function up(): void
    {
        Schema::create('admin_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40);          // order | subscriber | test
            $table->string('title');
            $table->string('body', 500)->nullable();
            $table->string('url', 500)->nullable(); // where clicking the alert should land (relative or absolute)
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at']);
            $table->index(['user_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_alerts');
    }
};
