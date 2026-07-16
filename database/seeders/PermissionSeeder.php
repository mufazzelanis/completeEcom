<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Dashboard
            ['name' => 'dashboard.view',      'display_name' => 'View Dashboard',      'group' => 'Dashboard'],

            // Users
            ['name' => 'users.view',          'display_name' => 'View Users',          'group' => 'Users'],
            ['name' => 'users.create',        'display_name' => 'Create Users',        'group' => 'Users'],
            ['name' => 'users.edit',          'display_name' => 'Edit Users',          'group' => 'Users'],
            ['name' => 'users.delete',        'display_name' => 'Delete Users',        'group' => 'Users'],
            ['name' => 'users.manage_roles',  'display_name' => 'Manage Roles',        'group' => 'Users'],

            // Products
            ['name' => 'products.view',       'display_name' => 'View Products',       'group' => 'Products'],
            ['name' => 'products.create',     'display_name' => 'Create Products',     'group' => 'Products'],
            ['name' => 'products.edit',       'display_name' => 'Edit Products',       'group' => 'Products'],
            ['name' => 'products.delete',     'display_name' => 'Delete Products',     'group' => 'Products'],

            // Categories
            ['name' => 'categories.view',     'display_name' => 'View Categories',     'group' => 'Categories'],
            ['name' => 'categories.create',   'display_name' => 'Create Categories',   'group' => 'Categories'],
            ['name' => 'categories.edit',     'display_name' => 'Edit Categories',     'group' => 'Categories'],
            ['name' => 'categories.delete',   'display_name' => 'Delete Categories',   'group' => 'Categories'],

            // Orders
            ['name' => 'orders.view',         'display_name' => 'View Orders',         'group' => 'Orders'],
            ['name' => 'orders.update',       'display_name' => 'Update Orders',       'group' => 'Orders'],
            ['name' => 'orders.delete',       'display_name' => 'Delete Orders',       'group' => 'Orders'],

            // Coupons
            ['name' => 'coupons.view',        'display_name' => 'View Coupons',        'group' => 'Coupons'],
            ['name' => 'coupons.create',      'display_name' => 'Create Coupons',      'group' => 'Coupons'],
            ['name' => 'coupons.edit',        'display_name' => 'Edit Coupons',        'group' => 'Coupons'],
            ['name' => 'coupons.delete',      'display_name' => 'Delete Coupons',      'group' => 'Coupons'],

            // Reviews
            ['name' => 'reviews.view',        'display_name' => 'View Reviews',        'group' => 'Reviews'],
            ['name' => 'reviews.approve',     'display_name' => 'Approve Reviews',     'group' => 'Reviews'],
            ['name' => 'reviews.delete',      'display_name' => 'Delete Reviews',      'group' => 'Reviews'],

            // Vendors
            ['name' => 'vendors.view',        'display_name' => 'View Vendors',        'group' => 'Vendors'],
            ['name' => 'vendors.approve',     'display_name' => 'Approve Vendors',     'group' => 'Vendors'],
            ['name' => 'vendors.suspend',     'display_name' => 'Suspend Vendors',     'group' => 'Vendors'],
        ];

        foreach ($permissions as $perm) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $perm['name']],
                array_merge($perm, ['created_at' => now(), 'updated_at' => now()])
            );
        }

        // ── Default role permissions ──────────────────────────────────────────

        $allIds = DB::table('permissions')->pluck('id', 'name');

        $roleDefaults = [
            'manager' => [
                'dashboard.view',
                'users.view', 'users.create', 'users.edit',
                'products.view', 'products.create', 'products.edit', 'products.delete',
                'categories.view', 'categories.create', 'categories.edit', 'categories.delete',
                'orders.view', 'orders.update',
                'coupons.view', 'coupons.create', 'coupons.edit', 'coupons.delete',
                'reviews.view', 'reviews.approve', 'reviews.delete',
                'vendors.view',
            ],
            'staff' => [
                'dashboard.view',
                'products.view',
                'categories.view',
                'orders.view', 'orders.update',
                'reviews.view', 'reviews.approve',
            ],
        ];

        foreach ($roleDefaults as $role => $names) {
            DB::table('role_permissions')->where('role', $role)->delete();
            foreach ($names as $name) {
                if (isset($allIds[$name])) {
                    DB::table('role_permissions')->insertOrIgnore([
                        'role' => $role,
                        'permission_id' => $allIds[$name],
                    ]);
                }
            }
        }
    }
}
