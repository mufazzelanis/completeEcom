<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-visit landing-page funnel tracking — the landing-page counterpart to blog_reads/
     * blog_product_clicks (see 2026_11_04_000002_create_blog_commerce_tables.php), adapted for
     * a single-page order funnel instead of an article: scroll depth + active time (same
     * GREATEST()-updated columns), plus the funnel milestones a landing page actually cares
     * about (reached the order form, started filling it in) and which CTA button got clicked.
     *
     * landing_page_visits     — one row per page view, written by LandingPageVisit::begin()
     *                           then kept current by the in-page tracker (LandingPageTrackController)
     * landing_page_cta_clicks — every "Order Now" tap, labeled by which button (hero/offer/
     *                           sticky bar) so they're not all one indistinguishable number
     */
    public function up(): void
    {
        Schema::create('landing_page_visits', function (Blueprint $table) {
            $table->id();
            $table->string('visit_key', 36)->unique();
            $table->foreignId('landing_page_id')->constrained()->cascadeOnDelete();
            $table->string('visitor_key', 36)->index();
            $table->date('visit_date');
            $table->string('source', 12)->default('direct');
            $table->string('referrer_host', 120)->nullable();
            $table->string('device', 8)->default('desktop');
            $table->unsignedSmallInteger('active_seconds')->default(0);
            $table->unsignedTinyInteger('max_scroll')->default(0);
            $table->unsignedSmallInteger('cta_clicks')->default(0);
            // Real touch/mouse/key event happened — bots/crawlers that merely load the page
            // never get one, same "engaged_at" concept as blog_reads.
            $table->timestamp('engaged_at')->nullable();
            // Funnel milestones, in order: the order form scrolled into view, then the visitor
            // actually touched one of its fields (a much stronger buy-intent signal than just
            // "scrolled past it") — each stamped once, the first time it happens.
            $table->timestamp('reached_form_at')->nullable();
            $table->timestamp('form_start_at')->nullable();
            // Set once this visit's order actually gets placed — the visit_key rides along as
            // a hidden field on the order form itself, so attribution never depends on cookies.
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['landing_page_id', 'visit_date']);
            $table->index('updated_at');
        });

        Schema::create('landing_page_cta_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('landing_page_id')->constrained()->cascadeOnDelete();
            $table->foreignId('landing_page_visit_id')->nullable()->constrained()->nullOnDelete();
            $table->string('placement', 20);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['landing_page_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_page_cta_clicks');
        Schema::dropIfExists('landing_page_visits');
    }
};
