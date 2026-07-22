<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // Never show the currently logged-in admin in the list
        $query->where('id', '!=', auth()->id());

        $users = $query->withCount('orders')->latest()->paginate(15);
        $roles = Role::orderBy('display_name')->get();
        return view('admin.users.index', compact('users', 'roles'));
    }

    public function create()
    {
        $roles = Role::orderBy('is_system', 'desc')->orderBy('display_name')->get();
        return view('admin.users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role'     => 'required|exists:roles,name',
            'phone'    => 'nullable|string|max:20',
        ]);

        User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'role'      => $request->role,
            'phone'     => $request->phone,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    public function show(User $user)
    {
        $user->load('orders');
        $pointTransactions = $user->pointTransactions()->with('order')->latest()->paginate(15);
        return view('admin.users.show', compact('user', 'pointTransactions'));
    }

    public function edit(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('profile.edit')->with('error', 'Manage your own account from your profile page.');
        }

        $roles = Role::orderBy('is_system', 'desc')->orderBy('display_name')->get();
        $permissions = Permission::orderBy('group')->orderBy('display_name')->get()->groupBy('group');
        $userPermissions = $user->userPermissions()->with('permission')->get()->keyBy('permission_id');
        $effectivePermissions = $user->effectivePermissions();
        return view('admin.users.edit', compact('user', 'roles', 'permissions', 'userPermissions', 'effectivePermissions'));
    }

    public function update(Request $request, User $user)
    {
        // This tool manages OTHER users only — self-management (including role/permissions)
        // happens via /profile, so this account can never grant itself extra access here.
        if ($user->id === auth()->id()) {
            return redirect()->route('profile.edit')->with('error', 'Manage your own account from your profile page.');
        }

        $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email,' . $user->id,
            'role'      => 'required|exists:roles,name',
            'phone'     => 'nullable|string|max:20',
            'is_active' => 'boolean',
            'password'  => ['nullable', 'confirmed', Rules\Password::defaults()],
        ]);

        // Prevent removing the last admin
        if ($user->isAdmin() && $request->role !== 'admin') {
            $adminCount = User::where('role', 'admin')->count();
            if ($adminCount <= 1) {
                return back()->with('error', 'Cannot change the role of the last admin.');
            }
        }

        $data = [
            'name'      => $request->name,
            'email'     => $request->email,
            'role'      => $request->role,
            'phone'     => $request->phone,
            'is_active' => $request->boolean('is_active'),
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        // Save per-user permission overrides
        $this->saveUserPermissions($user, $request->input('user_permissions', []));

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }
        if ($user->isAdmin() && User::where('role', 'admin')->count() <= 1) {
            return back()->with('error', 'Cannot delete the last admin.');
        }
        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'User deleted.');
    }

    private function saveUserPermissions(User $user, array $overrides): void
    {
        // $overrides: ['permission_id' => 'grant'|'deny'|'inherit']
        DB::transaction(function () use ($user, $overrides) {
            $user->userPermissions()->delete();
            foreach ($overrides as $permissionId => $type) {
                if ($type === 'inherit') continue;
                UserPermission::create([
                    'user_id'       => $user->id,
                    'permission_id' => $permissionId,
                    'type'          => $type,
                ]);
            }
        });
    }
}
