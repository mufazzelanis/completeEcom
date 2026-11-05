<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Every article page view is now written by the server (bots included, named — Googlebot,
     * Amazon AWS server …), not only by the in-page script once a person shows up:
     *   ip / user_agent — who asked, for App\Support\VisitorIntel::verdict()
     *   bot_name / bot_kind — what the User-Agent / browser headers said at that moment
     *   engaged_at — first real touch / mouse / key event; only these are "readers"
     *
     * Rows written before this (the tracker used to report as soon as the page opened, before any
     * interaction) are kept as readers only where a product was clicked or the reading time went
     * past the 30 seconds that version credited without input; the rest become "no interaction".
     */
    public function up(): void
    {
        Schema::table('blog_reads', function (Blueprint $table) {
            $table->string('ip', 45)->nullable()->after('user_id');
            $table->string('user_agent', 500)->nullable()->after('ip');
            $table->string('bot_name', 60)->nullable()->after('user_agent');
            $table->string('bot_kind', 12)->nullable()->after('bot_name');
            $table->timestamp('engaged_at')->nullable()->after('product_clicks');
            $table->index(['read_date', 'engaged_at']);
            $table->index('ip');
        });

        DB::table('blog_reads')->where(fn ($q) => $q->where('product_clicks', '>', 0)->orWhere('active_seconds', '>', 30))
            ->update(['engaged_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('blog_reads', function (Blueprint $table) {
            $table->dropIndex(['read_date', 'engaged_at']);
            $table->dropIndex(['ip']);
            $table->dropColumn(['ip', 'user_agent', 'bot_name', 'bot_kind', 'engaged_at']);
        });
    }
};
