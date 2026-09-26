<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $perms = [
        ['name' => 'crm.view',   'display_name' => 'View CRM',   'group' => 'CRM'],
        ['name' => 'crm.manage', 'display_name' => 'Manage CRM (edit contacts, leads, segments, tasks)', 'group' => 'CRM'],
    ];

    public function up(): void
    {
        foreach ($this->perms as $p) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $p['name']],
                array_merge($p, ['created_at' => now(), 'updated_at' => now()])
            );
        }
        $ids = DB::table('permissions')->whereIn('name', ['crm.view', 'crm.manage'])->pluck('id', 'name');

        // Managers get the whole CRM; staff can view by default (an admin can grant crm.manage per user/role).
        foreach (['manager' => ['crm.view', 'crm.manage'], 'staff' => ['crm.view']] as $role => $names) {
            foreach ($names as $n) {
                DB::table('role_permissions')->insertOrIgnore(['role' => $role, 'permission_id' => $ids[$n]]);
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->whereIn('name', ['crm.view', 'crm.manage'])->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
