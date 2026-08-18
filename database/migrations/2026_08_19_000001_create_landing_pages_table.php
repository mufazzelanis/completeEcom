<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            // The whole point of this feature: a bare top-level URL (mitavin.com/<slug>),
            // not nested under /pages or /shop — see routes/web.php for the catch-all route.
            $table->string('slug')->unique();
            // Optional — links pricing/stock/gallery to a real catalog product. Nullable so a
            // landing page can also exist for something not in the catalog at all.
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('draft'); // draft|published — draft 404s publicly

            // Hero
            $table->string('hero_heading')->nullable();
            $table->string('hero_subheading')->nullable();
            $table->string('hero_image')->nullable();

            // Free-form body — the actual "customize it my own way" surface, same Summernote
            // editor + sanitize-on-write pattern as Page::content (see setContentAttribute
            // below): images, formatting, whatever the admin wants, without me having to
            // build a fixed set of predefined "blocks".
            $table->longText('content')->nullable();

            // Falls back to the linked product's price when null.
            $table->decimal('price_override', 10, 2)->nullable();

            // Header
            $table->string('header_logo')->nullable(); // falls back to the site logo when null
            $table->string('order_button_text')->default('Order Now');

            // Order form: name+phone are always collected (mandatory for any COD fulfillment,
            // not something worth letting an admin accidentally turn off), address is
            // optional-toggleable, and admin-defined extra fields (each with its own
            // required/optional flag) live in order_form_fields.
            $table->boolean('collect_address')->default(true);
            $table->boolean('require_address')->default(false);
            $table->json('order_form_fields')->nullable();

            // Thank-you page shown after a successful order submission.
            $table->string('thank_you_heading')->default('Thank You!');
            $table->text('thank_you_message')->nullable();
            $table->string('thank_you_redirect_url')->nullable();

            // SEO
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('og_image')->nullable();

            $table->unsignedInteger('views_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_pages');
    }
};
