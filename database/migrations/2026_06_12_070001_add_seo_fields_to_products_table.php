<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Basic SEO
            $table->string('focus_keyword', 255)->nullable()->after('meta_description');
            $table->string('canonical_url', 500)->nullable()->after('focus_keyword');
            $table->string('robots_meta', 100)->nullable()->after('canonical_url');
            $table->decimal('sitemap_priority', 3, 2)->default(0.5)->after('robots_meta');
            $table->string('sitemap_changefreq', 20)->default('weekly')->after('sitemap_priority');
            $table->string('redirect_url', 500)->nullable()->after('sitemap_changefreq');
            $table->string('breadcrumb_title', 255)->nullable()->after('redirect_url');
            // Social sharing
            $table->string('og_title', 255)->nullable()->after('breadcrumb_title');
            $table->text('og_description')->nullable()->after('og_title');
            $table->string('og_image', 500)->nullable()->after('og_description');
            $table->string('twitter_card', 50)->default('summary_large_image')->after('og_image');
            $table->string('twitter_title', 255)->nullable()->after('twitter_card');
            $table->text('twitter_description')->nullable()->after('twitter_title');
            $table->string('twitter_image', 500)->nullable()->after('twitter_description');
            // Search visibility
            $table->boolean('noindex')->default(false)->after('twitter_image');
            $table->boolean('nofollow')->default(false)->after('noindex');
            $table->boolean('nosnippet')->default(false)->after('nofollow');
            $table->boolean('noimageindex')->default(false)->after('nosnippet');
            // Schema markup
            $table->string('schema_type', 50)->default('Product')->after('noimageindex');
            $table->string('schema_condition', 20)->default('NewCondition')->after('schema_type');
            $table->string('schema_availability', 50)->default('InStock')->after('schema_condition');
            $table->date('price_valid_until')->nullable()->after('schema_availability');
            $table->string('gtin', 100)->nullable()->after('price_valid_until');
            $table->string('mpn', 100)->nullable()->after('gtin');
            $table->string('country_of_origin', 100)->nullable()->after('mpn');
            // Merchant Center
            $table->string('google_category', 255)->nullable()->after('country_of_origin');
            $table->string('google_product_type', 255)->nullable()->after('google_category');
            $table->string('age_group', 50)->nullable()->after('google_product_type');
            $table->string('gender', 20)->nullable()->after('age_group');
            $table->string('color_description', 100)->nullable()->after('gender');
            $table->string('size_description', 100)->nullable()->after('color_description');
            $table->string('material', 255)->nullable()->after('size_description');
            // Image SEO
            $table->string('image_alt', 255)->nullable()->after('material');
            $table->string('image_title', 255)->nullable()->after('image_alt');
            // AI SEO
            $table->text('ai_summary')->nullable()->after('image_title');
            $table->text('ai_overview')->nullable()->after('ai_summary');
            $table->json('ai_key_features')->nullable()->after('ai_overview');
            $table->json('ai_benefits')->nullable()->after('ai_key_features');
            $table->json('ai_use_cases')->nullable()->after('ai_benefits');
            $table->text('ai_comparison')->nullable()->after('ai_use_cases');
            // Score cache
            $table->tinyInteger('seo_score')->default(0)->after('ai_comparison');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'focus_keyword', 'canonical_url', 'robots_meta', 'sitemap_priority', 'sitemap_changefreq',
                'redirect_url', 'breadcrumb_title',
                'og_title', 'og_description', 'og_image',
                'twitter_card', 'twitter_title', 'twitter_description', 'twitter_image',
                'noindex', 'nofollow', 'nosnippet', 'noimageindex',
                'schema_type', 'schema_condition', 'schema_availability', 'price_valid_until',
                'gtin', 'mpn', 'country_of_origin',
                'google_category', 'google_product_type', 'age_group', 'gender',
                'color_description', 'size_description', 'material',
                'image_alt', 'image_title',
                'ai_summary', 'ai_overview', 'ai_key_features', 'ai_benefits', 'ai_use_cases', 'ai_comparison',
                'seo_score',
            ]);
        });
    }
};
