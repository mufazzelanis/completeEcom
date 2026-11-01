<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Storefront promotion popups (Marketing → Promo Popups). Image, title and text are each
     * optional — a picture-only, text-only or picture+text popup — and who/where/when it shows
     * is decided by the targeting columns; counters back the admin's per-popup results.
     */
    public function up(): void
    {
        Schema::create('promo_popups', function (Blueprint $table) {
            $table->id();
            $table->string('name');                       // internal, admin only
            $table->boolean('is_active')->default(false);
            $table->unsignedSmallInteger('priority')->default(0);

            // content
            $table->string('image')->nullable();
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->string('button_text')->nullable();
            $table->string('link_url', 1000)->nullable();
            $table->boolean('new_tab')->default(false);
            $table->string('coupon_code', 60)->nullable();
            $table->timestamp('countdown_to')->nullable();
            $table->string('accent_color', 9)->default('#f97316');

            // where / who / when
            $table->json('pages')->nullable();            // all | home | product | category | shop | blog
            $table->json('category_ids')->nullable();     // narrow product/category pages to these
            $table->json('product_ids')->nullable();      // …or to these products
            $table->string('device', 10)->default('all'); // all | mobile | desktop
            $table->string('audience', 10)->default('all'); // all | new | returning
            $table->string('source', 10)->default('all'); // all | facebook | google | direct
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            // trigger + frequency
            $table->string('trigger', 10)->default('delay'); // delay | scroll | exit | views
            $table->unsignedSmallInteger('trigger_value')->default(5);
            $table->string('frequency', 10)->default('session'); // session | daily | days | once
            $table->unsignedSmallInteger('frequency_days')->default(3);

            // results
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('clicks')->default(0);
            $table->unsignedInteger('closes')->default(0);
            $table->unsignedInteger('orders')->default(0);
            $table->decimal('revenue', 12, 2)->default(0);
            $table->timestamp('stats_since')->nullable();

            $table->timestamps();
            $table->index(['is_active', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_popups');
    }
};
