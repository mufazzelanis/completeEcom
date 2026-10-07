<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-visitor search history — the other half of the signal behind the homepage
     * personalized section (HomeSection::getPersonalizedProducts()), alongside
     * product_views. Only actually-submitted searches get logged here (ShopController@index,
     * when ?search= is present) — not every autosuggest keystroke, which would be mostly
     * half-typed noise and multiply the row count for no real signal gain.
     */
    public function up(): void
    {
        Schema::create('search_queries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id', 100)->nullable();
            $table->string('query', 150);
            $table->timestamp('searched_at');

            $table->index(['user_id', 'searched_at']);
            $table->index(['session_id', 'searched_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_queries');
    }
};
