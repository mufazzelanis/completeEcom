<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Shoppable blog posts + per-read analytics.
     *
     * blog_post_product   — products the author attached to a post (order + an optional "why we picked it" note)
     * blog_reads          — one row per article page view, written by the in-page tracker (JS only, so
     *                       crawlers that never run scripts don't inflate readers): active reading time,
     *                       deepest scroll point, product clicks
     * blog_product_clicks — every product interaction inside a post (open / add to cart / buy now) + where
     *                       on the page it happened (inline block, end-of-post shelf, sticky bar, sidebar)
     * blog_conversions    — orders placed within 7 days of a product click in a post (cookie attribution)
     */
    public function up(): void
    {
        Schema::create('blog_post_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blog_post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('note', 255)->nullable();
            $table->unique(['blog_post_id', 'product_id']);
        });

        Schema::create('blog_reads', function (Blueprint $table) {
            $table->id();
            $table->string('read_key', 36)->unique();
            $table->foreignId('blog_post_id')->constrained()->cascadeOnDelete();
            $table->string('visitor_key', 36)->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->date('read_date');
            $table->string('source', 12)->default('direct');
            $table->string('referrer_host', 120)->nullable();
            $table->string('device', 8)->default('desktop');
            $table->unsignedSmallInteger('active_seconds')->default(0);
            $table->unsignedTinyInteger('max_scroll')->default(0);
            $table->unsignedSmallInteger('product_clicks')->default(0);
            $table->timestamps();

            $table->index(['blog_post_id', 'read_date']);
            $table->index(['read_date', 'blog_post_id']);
            $table->index('updated_at');
        });

        Schema::create('blog_product_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blog_post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('blog_read_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('placement', 12);
            $table->string('action', 8)->default('open');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['blog_post_id', 'created_at']);
            $table->index('created_at');
        });

        Schema::create('blog_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blog_post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('blog_read_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('revenue', 12, 2)->default(0);
            $table->decimal('matched_revenue', 12, 2)->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['blog_post_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_conversions');
        Schema::dropIfExists('blog_product_clicks');
        Schema::dropIfExists('blog_reads');
        Schema::dropIfExists('blog_post_product');
    }
};
