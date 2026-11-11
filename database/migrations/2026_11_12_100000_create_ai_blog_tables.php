<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * AI Blog: bilingual posts (Bangla + English on one post, each at its own URL) and the
 * autopilot's topic queue + run log (what each post cost, for the monthly budget).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            // the language title/content are written in; alt_* hold the other language's version
            $table->string('lang', 2)->default('en')->after('slug');
            $table->string('alt_title')->nullable()->after('meta_keywords');
            $table->text('alt_excerpt')->nullable()->after('alt_title');
            $table->longText('alt_content')->nullable()->after('alt_excerpt');
            $table->string('alt_meta_title')->nullable()->after('alt_content');
            $table->string('alt_meta_description', 500)->nullable()->after('alt_meta_title');
            $table->boolean('ai_generated')->default(false)->after('alt_meta_description');
            $table->text('review_notes')->nullable()->after('ai_generated');
        });

        // existing posts written in Bangla get the right language (and <html lang>) from day one
        DB::table('blog_posts')->select('id', 'title')->orderBy('id')->each(function ($p) {
            if (preg_match('/\p{Bengali}/u', (string) $p->title)) {
                DB::table('blog_posts')->where('id', $p->id)->update(['lang' => 'bn']);
            }
        });

        Schema::create('ai_blog_topics', function (Blueprint $table) {
            $table->id();
            $table->string('title');                              // the topic / working title
            $table->string('keyword')->nullable();                 // what people type into Google
            $table->string('post_type', 20)->default('guide');     // AiBlog\Writer::POST_TYPES
            $table->string('lang', 2)->nullable();                 // null = the default language setting
            $table->json('product_ids')->nullable();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('blog_category_id')->nullable()->constrained()->nullOnDelete();
            $table->text('note')->nullable();                      // "Mitavin Note": the shop's own experience
            $table->timestamp('not_before')->nullable();           // don't write before this time
            $table->boolean('run_now')->default(false);            // admin pressed "Write now"
            $table->string('status', 20)->default('queued');       // queued / writing / done / failed / skipped
            $table->string('slot_key', 30)->nullable()->index();   // "2026-11-12 10:00": the slot that used it
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->text('error')->nullable();
            $table->foreignId('blog_post_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->index(['status', 'not_before']);
        });

        Schema::create('ai_blog_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_blog_topic_id')->nullable()->constrained()->nullOnDelete();
            $table->string('purpose', 20);                         // write / translate / suggest / test
            $table->string('provider', 20);
            $table->string('model', 60);
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->decimal('cost_usd', 10, 5)->default(0);
            $table->boolean('ok')->default(true);
            $table->text('error')->nullable();
            $table->unsignedInteger('duration_ms')->default(0);
            $table->timestamps();
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_blog_runs');
        Schema::dropIfExists('ai_blog_topics');
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropColumn(['lang', 'alt_title', 'alt_excerpt', 'alt_content', 'alt_meta_title', 'alt_meta_description', 'ai_generated', 'review_notes']);
        });
    }
};
