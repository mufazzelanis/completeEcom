<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-visitor product view history — the "what has this person actually looked at"
     * signal behind the homepage personalized section (HomeSection::getPersonalizedProducts()).
     * One row per (visitor, product) pair, upserted on repeat views (view_count bumped,
     * viewed_at refreshed) rather than appending a new row every time, so a product someone
     * keeps coming back to ranks by genuine recency/frequency instead of the table filling
     * with duplicate rows for the same pair. user_id/session_id follow the exact same
     * nullable-pair shape as Cart/Wishlist (never both set at once — a guest's rows get
     * reassigned to user_id on login, see AuthenticatedSessionController's existing
     * mergeGuestCart/mergeGuestWishlist, extended with mergeGuestProductViews).
     */
    public function up(): void
    {
        Schema::create('product_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id', 100)->nullable();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('view_count')->default(1);
            $table->timestamp('viewed_at');

            $table->index(['user_id', 'viewed_at']);
            $table->index(['session_id', 'viewed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_views');
    }
};
