<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('home_sections', function (Blueprint $table) {
            // Replaces the single category_id as the source of truth for filtering —
            // lets one section pull products from several categories at once (e.g.
            // "Advance Shop" = Diabetics + Skin Care + Baby combined), not just one.
            // category_id itself is left in place (nullable already) purely so any
            // code still reading $section->category (the old single-category
            // relation, e.g. the admin list's "→ Category Name" label) keeps working
            // for old rows; it's no longer written by the form after this migration.
            $table->json('category_ids')->nullable()->after('category_id');
        });

        // Backfill: every existing section that had a single category_id becomes a
        // one-element category_ids array, so nothing already configured changes
        // behavior after deploy. (Table is small — a handful of homepage sections —
        // so a plain get()/foreach is simplest, no need for chunking.)
        foreach (DB::table('home_sections')->whereNotNull('category_id')->get() as $row) {
            DB::table('home_sections')->where('id', $row->id)->update([
                'category_ids' => json_encode([(int) $row->category_id]),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('home_sections', function (Blueprint $table) {
            $table->dropColumn('category_ids');
        });
    }
};
