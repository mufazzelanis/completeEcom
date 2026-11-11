<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Every landing page order needs a full address now; the per-page switches are gone. */
    public function up(): void
    {
        DB::table('landing_pages')->update(['collect_address' => true, 'require_address' => true]);
        Schema::table('landing_pages', function (Blueprint $table) {
            $table->boolean('collect_address')->default(true)->change();
            $table->boolean('require_address')->default(true)->change();
        });
    }

    public function down(): void
    {
        // nothing to undo: the old values are not kept
    }
};
