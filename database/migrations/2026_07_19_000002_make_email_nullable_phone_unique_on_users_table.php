<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('UPDATE users SET email = NULL WHERE email = ""');
        DB::statement('UPDATE users SET phone = NULL WHERE phone = ""');

        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('phone')->nullable()->change();
        });

        // Drop old unique indexes if they exist, then recreate
        $indexes = DB::select("SHOW INDEX FROM users WHERE Key_name != 'PRIMARY'");
        $indexNames = array_column($indexes, 'Key_name');

        if (in_array('users_email_unique', $indexNames)) {
            DB::statement('ALTER TABLE users DROP INDEX users_email_unique');
        }
        if (in_array('users_phone_unique', $indexNames)) {
            DB::statement('ALTER TABLE users DROP INDEX users_phone_unique');
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unique('email');
            $table->unique('phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->unique()->change();
            $table->string('phone')->nullable()->change();
        });
    }
};
