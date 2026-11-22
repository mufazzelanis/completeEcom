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

            // Returns (approving/rejecting restores or withholds stock — a money/inventory
            // decision, same tier as coupons/vendors, not just "view orders")
            ['name' => 'returns.view',        'display_name' => 'View Returns',        'group' => 'Returns'],
            ['name' => 'returns.manage',      'display_name' => 'Approve / Reject Returns', 'group' => 'Returns'],

            // Coupons
            ['name' => 'coupons.view',        'display_name' => 'View Coupons',        'group' => 'Coupons'],
            ['name' => 'coupons.create',      'display_name' => 'Create Coupons',      'group' => 'Coupons'],
            ['name' => 'coupons.edit',        'display_name' => 'Edit Coupons',        'group' => 'Coupons'],
            ['name' => 'coupons.delete',      'display_name' => 'Delete Coupons',      'group' => 'Coupons'],

            // Reviews
            ['name' => 'reviews.view',        'display_name' => 'View Reviews',        'group' => 'Reviews'],
            ['name' => 'reviews.edit',        'display_name' => 'Edit Reviews',        'group' => 'Reviews'],
            ['name' => 'reviews.approve',     'display_name' => 'Approve Reviews',     'group' => 'Reviews'],
            ['name' => 'reviews.delete',      'display_name' => 'Delete Reviews',      'group' => 'Reviews'],

            // Vendors
            ['name' => 'vendors.view',        'display_name' => 'View Vendors',        'group' => 'Vendors'],
            ['name' => 'vendors.edit',        'display_name' => 'Edit Vendor Profiles', 'group' => 'Vendors'],
            ['name' => 'vendors.approve',     'display_name' => 'Approve Vendors',     'group' => 'Vendors'],
            ['name' => 'vendors.suspend',     'display_name' => 'Suspend Vendors',     'group' => 'Vendors'],

            // Reports (financial/analytics — previously ungated beyond generic admin access)
            ['name' => 'reports.view',        'display_name' => 'View Reports',        'group' => 'Reports'],

            // Payments (viewing transactions vs verifying/refunding are separate — refunding moves real money)
            ['name' => 'payments.view',       'display_name' => 'View Payments',       'group' => 'Payments'],
            ['name' => 'payments.manage',     'display_name' => 'Verify / Refund Payments', 'group' => 'Payments'],

            // Settings (payment gateway keys, SMTP credentials, etc. — system-level, admin only by default)
            ['name' => 'settings.manage',     'display_name' => 'Manage Settings',     'group' => 'Settings'],

            // CRM
            ['name' => 'crm.view',            'display_name' => 'View CRM',            'group' => 'CRM'],
            ['name' => 'crm.manage',          'display_name' => 'Manage CRM (edit contacts, leads, segments, tasks)', 'group' => 'CRM'],

            // Custom Invoices (hand-written invoices + the customer/product lookup behind
            // them) — previously reachable by any account with plain admin-panel access,
            // regardless of which permissions were actually granted to their role.
            ['name' => 'invoices.manage',     'display_name' => 'Create / Manage Custom Invoices', 'group' => 'Invoices'],
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
                'returns.view', 'returns.manage',
                'coupons.view', 'coupons.create', 'coupons.edit', 'coupons.delete',
                'reviews.view', 'reviews.edit', 'reviews.approve', 'reviews.delete',
                'vendors.view', 'vendors.edit',
                'reports.view',
                'payments.view', 'payments.manage',
                'crm.view', 'crm.manage',
                'invoices.manage',
                // settings.manage intentionally excluded — payment gateway keys, SMTP
                // credentials etc. stay admin-only, same tier as users.delete/vendors.approve.
            ],
            'staff' => [
                'dashboard.view',
                'products.view',
                'categories.view',
                'orders.view', 'orders.update',
                'returns.view',
                'reviews.view', 'reviews.approve',
                'crm.view',
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

        // Any OTHER existing custom role (created by hand via Admin → Roles, outside the
        // two defaults above — this project has one, "anis") is left alone by the loop
        // above, which only ever touches 'manager'/'staff'. Several admin routes that
        // used to have no permission check at all (search/suggest, audit logs, live
        // visitors, checkout leads, stock alerts, fraud checker, custom invoices) are
        // now gated with permission:dashboard.view / orders.view / products.view /
        // invoices.manage — granting every custom role that already has can_access_admin
        // the dashboard/orders/products permissions it's missing (not invoices.manage,
        // which is new and should be an explicit choice) means this round's RBAC
        // tightening can't silently 403 an account that worked yesterday.
        $backfillIds = DB::table('permissions')->whereIn('name', ['dashboard.view', 'orders.view', 'products.view'])->pluck('id', 'name');
        $customRoles = DB::table('roles')->where('can_access_admin', true)->whereNotIn('name', ['admin', 'manager', 'staff'])->pluck('name');
        foreach ($customRoles as $role) {
            $has = DB::table('role_permissions')->where('role', $role)->whereIn('permission_id', $backfillIds)->pluck('permission_id')->all();
            foreach ($backfillIds as $name => $id) {
                if (! in_array($id, $has, true)) {
                    DB::table('role_permissions')->insertOrIgnore(['role' => $role, 'permission_id' => $id]);
                }
            }
        }
    }
}
