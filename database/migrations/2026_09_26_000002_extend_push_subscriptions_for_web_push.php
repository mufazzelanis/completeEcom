<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The original push_subscriptions table (2026_06_12) was scaffolded for a legacy FCM
     * server-key flow that nothing ever wrote to — no service worker, no subscribe endpoint.
     * Real browser Web Push (VAPID) needs a few more things per subscription: a stable way to
     * dedupe by endpoint (the endpoint itself is a long URL, so it's hashed for the unique
     * index), which side of the app it belongs to (admin alerts vs. a future customer feature),
     * and last-success bookkeeping so dead subscriptions can be told apart from live ones.
     */
    public function up(): void
    {
        Schema::table('push_subscriptions', function (Blueprint $table) {
            $table->string('scope', 20)->default('customer')->after('user_id');
            $table->char('endpoint_hash', 64)->nullable()->unique()->after('endpoint');
            $table->string('content_encoding', 20)->default('aes128gcm')->after('auth');
            $table->string('user_agent', 255)->nullable()->after('browser');
            $table->timestamp('last_used_at')->nullable()->after('user_agent');

            $table->index(['scope', 'user_id']);
        });

        DB::table('push_subscriptions')->orderBy('id')->each(function ($row) {
            DB::table('push_subscriptions')->where('id', $row->id)->update([
                'endpoint_hash' => hash('sha256', $row->endpoint),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('push_subscriptions', function (Blueprint $table) {
            $table->dropIndex(['scope', 'user_id']);
            $table->dropUnique(['endpoint_hash']);
            $table->dropColumn(['scope', 'endpoint_hash', 'content_encoding', 'user_agent', 'last_used_at']);
        });
    }
};
