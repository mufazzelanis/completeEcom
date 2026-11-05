<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Who each view really came from, stamped once by visitors:resolve-ips (VisitorIntel::stampBlogReads)
 * instead of being worked out per IP on every dashboard load — which took a minute at 300k views.
 *   net           "Amazon AWS", "Grameenphone" … (null: unknown / private address)
 *   net_hosting   true: data centre · false: home / mobile / unknown · null: not looked up yet
 *   bot_verified  for a bot that claims a name: true = its network matches, false = fake
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_reads', function (Blueprint $table) {
            $table->string('net', 60)->nullable()->after('bot_kind');
            $table->boolean('net_hosting')->nullable()->after('net');
            $table->boolean('bot_verified')->nullable()->after('net_hosting');
            // covers the dashboard's "who visited" GROUP BY completely (read straight from the index,
            // no row lookups) and starts with the old (read_date, engaged_at) index, which it replaces
            $table->index(['read_date', 'engaged_at', 'bot_name', 'bot_kind', 'bot_verified', 'net_hosting', 'net', 'ip', 'blog_post_id'], 'blog_reads_traffic_index');
            $table->dropIndex(['read_date', 'engaged_at']);
            // visitors:resolve-ips (every minute): "recent views whose network isn't stamped yet"
            $table->index(['net_hosting', 'created_at', 'ip'], 'blog_reads_pending_index');
        });
    }

    public function down(): void
    {
        Schema::table('blog_reads', function (Blueprint $table) {
            $table->index(['read_date', 'engaged_at']);
            $table->dropIndex('blog_reads_traffic_index');
            $table->dropIndex('blog_reads_pending_index');
            $table->dropColumn(['net', 'net_hosting', 'bot_verified']);
        });
    }
};
