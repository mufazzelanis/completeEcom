<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();        // slug stored in users.role
            $table->string('display_name');
            $table->string('description')->nullable();
            $table->boolean('can_access_admin')->default(false);
            $table->boolean('is_system')->default(false); // system roles cannot be deleted
            $table->timestamps();
        });

        // Seed the four built-in roles
        $now = now();
        DB::table('roles')->insert([
            ['name' => 'admin',    'display_name' => 'Admin',    'description' => 'Full access to everything.',               'can_access_admin' => true,  'is_system' => true,  'created_at' => $now, 'updated_at' => $now],
            ['name' => 'manager',  'display_name' => 'Manager',  'description' => 'Manage catalog, orders and coupons.',       'can_access_admin' => true,  'is_system' => true,  'created_at' => $now, 'updated_at' => $now],
            ['name' => 'staff',    'display_name' => 'Staff',    'description' => 'View and process orders.',                  'can_access_admin' => true,  'is_system' => true,  'created_at' => $now, 'updated_at' => $now],
            ['name' => 'customer', 'display_name' => 'Customer', 'description' => 'Regular store customer, no panel access.',  'can_access_admin' => false, 'is_system' => true,  'created_at' => $now, 'updated_at' => $now],
        ]);

        // Change users.role from ENUM to VARCHAR so custom roles can be stored
        DB::statement("ALTER TABLE users MODIFY COLUMN role VARCHAR(50) NOT NULL DEFAULT 'customer'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin','manager','staff','customer') NOT NULL DEFAULT 'customer'");
        Schema::dropIfExists('roles');
    }
};
