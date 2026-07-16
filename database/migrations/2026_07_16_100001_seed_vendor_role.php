<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('roles')->insert([
            'name' => 'vendor',
            'display_name' => 'Vendor',
            'description' => 'Marketplace seller — manages own products and orders.',
            'can_access_admin' => false,
            'is_system' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('roles')->where('name', 'vendor')->delete();
    }
};
