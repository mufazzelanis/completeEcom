<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * facebook_conversion_logs.sent_at was always filled by the column default (CURRENT_TIMESTAMP),
 * i.e. the MySQL server's own clock — live a US timezone, 5 hours behind the UTC the app stores
 * everything in, so Admin → Facebook Conversions API showed every event 5 hours early. New rows
 * are stamped by the app (FacebookConversionLog::booted); this moves the existing ones to UTC
 * using the database's own current offset (0 on a UTC server, so nothing changes there).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        $offset = (int) DB::selectOne('SELECT TIMESTAMPDIFF(SECOND, UTC_TIMESTAMP(), NOW()) AS s')->s;
        if ($offset !== 0) {
            DB::update('UPDATE facebook_conversion_logs SET sent_at = sent_at - INTERVAL ? SECOND', [$offset]);
        }
    }

    public function down(): void
    {
        // one-way data fix
    }
};
