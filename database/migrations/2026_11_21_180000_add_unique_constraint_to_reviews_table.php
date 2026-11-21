<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ProductController::storeReview() checked "does a review already exist" and only
        // created one afterward, with nothing in between to stop two near-simultaneous
        // submits (a double-click, two open tabs) from both passing that check and both
        // inserting — the same race-condition class already fixed for checkout/landing-page
        // stock elsewhere in this app. Any duplicate pair already sitting in the table from
        // that gap is cleaned up first (keeping the oldest row, dropping the rest) so this
        // migration can't fail on production data it has no way to have seen locally.
        $duplicateIds = DB::table('reviews')
            ->select('id')
            ->whereIn('id', function ($q) {
                $q->select('id')
                    ->from('reviews as r2')
                    ->whereColumn('r2.user_id', 'reviews.user_id')
                    ->whereColumn('r2.product_id', 'reviews.product_id')
                    ->where('r2.id', '<', DB::raw('reviews.id'));
            })
            ->pluck('id');

        if ($duplicateIds->isNotEmpty()) {
            DB::table('reviews')->whereIn('id', $duplicateIds)->delete();
        }

        Schema::table('reviews', function (Blueprint $table) {
            $table->unique(['user_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'product_id']);
        });
    }
};
