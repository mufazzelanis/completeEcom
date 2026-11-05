<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Views recorded before blog_reads had an ip column can never be looked up — without this they
 * would sit in the dashboard's "looking up the network…" note forever. Marks them settled
 * (not a data centre), so they count as people / no interaction like any other unknown network.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('blog_reads')->whereNull('ip')->whereNull('net_hosting')->update(['net_hosting' => false]);
    }

    public function down(): void
    {
        // nothing to undo: these rows had no network either way
    }
};
