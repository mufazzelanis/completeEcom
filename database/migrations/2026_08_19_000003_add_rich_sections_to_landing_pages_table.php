<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Every section below is optional and independently on/off — an empty heading, empty
     * repeater array, or false toggle simply makes that section not render (see
     * landing/show.blade.php). Nothing here is required to keep a simple landing page simple;
     * it's all upside for the admin who wants the full high-conversion COD template.
     */
    public function up(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            // Urgency bar (sticky strip above the header — "order in the next 10 minutes...")
            $table->boolean('urgency_bar_enabled')->default(false)->after('hero_image');
            $table->string('urgency_bar_text')->nullable()->after('urgency_bar_enabled');
            $table->unsignedInteger('urgency_bar_minutes')->default(10)->after('urgency_bar_text');

            // Rating line under the hero heading ("4.9/5 — 5,000+ reviews")
            $table->decimal('rating_value', 2, 1)->nullable()->after('hero_subheading');
            $table->unsignedInteger('rating_count')->nullable()->after('rating_value');

            // Small icon+label trust strip — reused verbatim near the hero and near the price box
            $table->json('trust_badges')->nullable()->after('rating_count');

            // "How it works" explainer video, embedded right under the hero
            $table->string('how_it_works_heading')->nullable()->after('trust_badges');
            $table->string('how_it_works_video')->nullable()->after('how_it_works_heading');

            // Benefits grid (icon + title + description tiles)
            $table->string('benefits_heading')->nullable()->after('how_it_works_video');
            $table->json('benefits')->nullable()->after('benefits_heading');

            // "Who is this for" bullet list
            $table->string('who_for_heading')->nullable()->after('benefits');
            $table->json('who_for')->nullable()->after('who_for_heading');

            // Testimonials: a video carousel and/or a screenshot (chat/review) carousel
            $table->string('testimonials_heading')->nullable()->after('who_for');
            $table->json('testimonial_videos')->nullable()->after('testimonials_heading');
            $table->json('testimonial_images')->nullable()->after('testimonial_videos');

            // Pricing / special-offer box: itemized lines + a struck-through compare price,
            // shown against the existing price_override/product price as the final total.
            $table->string('offer_badge_text')->nullable()->after('price_override');
            $table->json('pricing_items')->nullable()->after('offer_badge_text');
            $table->decimal('compare_at_price', 10, 2)->nullable()->after('pricing_items');

            // FAQ accordion
            $table->string('faqs_heading')->nullable()->after('testimonial_images');
            $table->json('faqs')->nullable()->after('faqs_heading');

            // Certificate / credential image strip (ISO certs, award photos, etc.)
            $table->string('certificates_heading')->nullable()->after('faqs');
            $table->string('certificates_subheading')->nullable()->after('certificates_heading');
            $table->json('certificates')->nullable()->after('certificates_subheading');

            // Delivery zones with their own shipping charge (e.g. Inside Dhaka ৳60 / Outside
            // ৳100), shown as radio buttons on the order form. Empty = no zone picker, no
            // shipping charge added (today's behavior, unchanged).
            $table->json('delivery_zones')->nullable()->after('require_address');
        });
    }

    public function down(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            $table->dropColumn([
                'urgency_bar_enabled', 'urgency_bar_text', 'urgency_bar_minutes',
                'rating_value', 'rating_count', 'trust_badges',
                'how_it_works_heading', 'how_it_works_video',
                'benefits_heading', 'benefits',
                'who_for_heading', 'who_for',
                'testimonials_heading', 'testimonial_videos', 'testimonial_images',
                'offer_badge_text', 'pricing_items', 'compare_at_price',
                'faqs_heading', 'faqs',
                'certificates_heading', 'certificates_subheading', 'certificates',
                'delivery_zones',
            ]);
        });
    }
};
