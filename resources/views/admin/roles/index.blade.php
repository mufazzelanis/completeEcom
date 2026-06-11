@extends('layouts.admin')
@section('title', 'Roles & Permissions')

@section('content')
<div class="flex items-center justify-between mb-6">
    <p class="text-sm text-gray-500">Configure role permissions. Per-user overrides are set on the Edit User page.</p>
    <a href="{{ route('admin.roles.create') }}"
        class="flex items-center space-x-2 bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-indigo-700 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        <span>New Role</span>
    </a>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-5">
    @foreach($roles as $role)
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <div class="flex items-start justify-between">
                <div class="flex items-center space-x-3">
                    <span class="px-3 py-1 rounded-full text-sm font-semibold {{ \App\Models\User::roleBadgeClass($role->name) }}">
                        {{ $role->display_name }}
                    </span>
                    @if($role->is_system)
                        <span class="text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">System</span>
                    @endif
                    @if($role->can_access_admin)
                        <span class="text-xs text-green-600 bg-green-50 px-2 py-0.5 rounded-full">Panel Access</span>
                    @endif
                </div>
                <div class="flex items-center space-x-3 text-sm">
                    @if($role->name !== 'admin')
                        <a href="{{ route('admin.roles.edit', $role->id) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">Edit</a>
                    @endif
                    @if(!$role->is_system)
                        <form action="{{ route('admin.roles.destroy', $role->id) }}" method="POST"
                            onsubmit="return confirm('Delete role \'{{ $role->display_name }}\'? Users will be moved to Customer.')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-700">Delete</button>
                        </form>
                    @endif
                </div>
            </div>

            @if($role->description)
                <p class="text-sm text-gray-500 mt-3">{{ $role->description }}</p>
            @endif

            <div class="flex items-center space-x-3 mt-4 text-xs text-gray-400">
                @if($role->name === 'admin')
                    <span class="bg-green-50 text-green-600 px-2 py-0.5 rounded-full font-medium">All permissions</span>
                @else
                    <span class="bg-indigo-50 text-indigo-600 px-2 py-0.5 rounded-full font-medium">{{ $role->permissionCount() }} permissions</span>
                @endif
                <span class="bg-gray-50 text-gray-500 px-2 py-0.5 rounded-full font-medium">{{ $role->userCount() }} users</span>
            </div>
        </div>
    @endforeach
</div>
@endsection
