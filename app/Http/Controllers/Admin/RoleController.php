<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::orderBy('is_system', 'desc')->orderBy('id')->get();
        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        $permissions = Permission::orderBy('group')->orderBy('display_name')->get()->groupBy('group');
        return view('admin.roles.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'display_name'     => 'required|string|max:100|unique:roles,display_name',
            'description'      => 'nullable|string|max:255',
            'can_access_admin' => 'boolean',
        ]);

        $slug = $this->uniqueSlug(Str::slug($request->display_name, '_'));

        $role = Role::create([
            'name'             => $slug,
            'display_name'     => $request->display_name,
            'description'      => $request->description,
            'can_access_admin' => $request->boolean('can_access_admin'),
            'is_system'        => false,
        ]);

        $this->syncPermissions($role->name, $request->input('permissions', []));

        return redirect()->route('admin.roles.index')->with('success', "Role '{$role->display_name}' created successfully.");
    }

    public function edit(Role $role)
    {
        $permissions = Permission::orderBy('group')->orderBy('display_name')->get()->groupBy('group');
        $assigned    = DB::table('role_permissions')->where('role', $role->name)->pluck('permission_id')->toArray();
        return view('admin.roles.edit', compact('role', 'permissions', 'assigned'));
    }

    public function update(Request $request, Role $role)
    {
        if ($role->name === 'admin') {
            return redirect()->route('admin.roles.index')->with('error', 'Admin role cannot be modified.');
        }

        if (!$role->is_system) {
            $request->validate([
                'display_name'     => 'required|string|max:100|unique:roles,display_name,' . $role->id,
                'description'      => 'nullable|string|max:255',
                'can_access_admin' => 'boolean',
            ]);
            $role->update([
                'display_name'     => $request->display_name,
                'description'      => $request->description,
                'can_access_admin' => $request->boolean('can_access_admin'),
            ]);
        }

        $this->syncPermissions($role->name, $request->input('permissions', []));

        return redirect()->route('admin.roles.index')->with('success', "{$role->display_name} permissions updated.");
    }

    public function destroy(Role $role)
    {
        if ($role->is_system) {
            return back()->with('error', 'System roles cannot be deleted.');
        }

        $userCount = User::where('role', $role->name)->count();

        DB::transaction(function () use ($role, $userCount) {
            if ($userCount > 0) {
                // Reassign users to customer before deleting
                User::where('role', $role->name)->update(['role' => 'customer']);
            }

            DB::table('role_permissions')->where('role', $role->name)->delete();
            $role->delete();
        });

        return redirect()->route('admin.roles.index')
            ->with('success', "Role '{$role->display_name}' deleted." . ($userCount > 0 ? " {$userCount} user(s) moved to Customer." : ''));
    }

    private function uniqueSlug(string $slug): string
    {
        $original = $slug;
        $i = 1;
        while (Role::where('name', $slug)->exists()) {
            $slug = $original . '_' . $i++;
        }
        return $slug;
    }

    private function syncPermissions(string $roleName, array $permissionIds): void
    {
        DB::transaction(function () use ($roleName, $permissionIds) {
            DB::table('role_permissions')->where('role', $roleName)->delete();
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->insertOrIgnore([
                    'role'          => $roleName,
                    'permission_id' => (int) $permissionId,
                ]);
            }
        });
    }
}
