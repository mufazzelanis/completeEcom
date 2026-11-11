<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            // site = Settings → Shipping (Inside/Outside Dhaka, default area, free threshold);
            // custom = the page's own delivery_zones; none = no delivery charge on the page
            $table->string('delivery_mode', 10)->default('site')->after('delivery_zones');
        });
        // existing pages keep the totals they show today: their own zones, or no charge
        DB::table('landing_pages')->update(['delivery_mode' => 'none']);
        DB::table('landing_pages')->whereNotNull('delivery_zones')->whereNotIn('delivery_zones', ['[]', '', 'null'])->update(['delivery_mode' => 'custom']);
    }

    public function down(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            $table->dropColumn('delivery_mode');
        });
    }
};
