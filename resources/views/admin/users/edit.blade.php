@extends('layouts.admin')
@section('title', 'Edit User')

@section('content')
<div class="max-w-3xl">
    <a href="{{ route('admin.users.index') }}" class="text-indigo-600 hover:text-indigo-700 text-sm flex items-center space-x-2 mb-6">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        <span>Back to Users</span>
    </a>

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li class="text-sm text-red-600">{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.users.update', $user->id) }}" method="POST">
        @csrf @method('PUT')

        <div class="space-y-6">
            {{-- Basic Info --}}
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h2 class="text-base font-semibold text-gray-800 mb-4">Basic Information</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}"
                            class="w-full border rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 {{ $errors->has('name') ? 'border-red-400' : 'border-gray-200' }}">
                        @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}"
                            class="w-full border rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 {{ $errors->has('email') ? 'border-red-400' : 'border-gray-200' }}">
                        @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                        <input type="text" name="phone" value="{{ old('phone', $user->phone) }}"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Role *</label>
                        <select name="role"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            @foreach($roles as $r)
                                <option value="{{ $r->name }}" {{ old('role', $user->role) === $r->name ? 'selected' : '' }}>
                                    {{ $r->display_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">New Password <span class="text-gray-400">(leave blank to keep current)</span></label>
                        <input type="password" name="password"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @error('password')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                        <input type="password" name="password_confirmation"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div class="col-span-2 flex items-center space-x-3">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" {{ $user->is_active ? 'checked' : '' }} class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                        </label>
                        <span class="text-sm text-gray-700">Active</span>
                    </div>
                </div>
            </div>

            {{-- Permission Overrides --}}
            @if(!$user->isAdmin())
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <div class="flex items-center justify-between mb-1">
                    <h2 class="text-base font-semibold text-gray-800">Permission Overrides</h2>
                    <span class="text-xs text-gray-400">Overrides apply on top of the user's role permissions</span>
                </div>
                <p class="text-xs text-gray-500 mb-4">
                    <span class="inline-flex items-center space-x-1"><span class="w-2 h-2 bg-green-400 rounded-full inline-block"></span><span>Green = granted by role</span></span>
                    &nbsp;·&nbsp;
                    <span class="inline-flex items-center space-x-1"><span class="w-2 h-2 bg-gray-300 rounded-full inline-block"></span><span>Gray = not in role</span></span>
                </p>
                @foreach($permissions as $group => $groupPerms)
                    <div class="mb-5">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ $group }}</p>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach($groupPerms as $perm)
                                @php
                                    $override = $userPermissions[$perm->id] ?? null;
                                    $inRole = in_array($perm->name, $effectivePermissions);
                                    $currentType = $override ? $override->type : 'inherit';
                                @endphp
                                <div class="flex items-center justify-between bg-gray-50 rounded-lg px-3 py-2">
                                    <span class="text-sm text-gray-700 flex items-center space-x-2">
                                        <span class="w-2 h-2 rounded-full {{ $inRole ? 'bg-green-400' : 'bg-gray-300' }}"></span>
                                        <span>{{ $perm->display_name }}</span>
                                    </span>
                                    <select name="user_permissions[{{ $perm->id }}]"
                                        class="text-xs border border-gray-200 rounded-lg px-2 py-1 focus:outline-none focus:ring-1 focus:ring-indigo-500 bg-white">
                                        <option value="inherit" {{ $currentType === 'inherit' ? 'selected' : '' }}>Inherit</option>
                                        <option value="grant"   {{ $currentType === 'grant'   ? 'selected' : '' }}>Grant</option>
                                        <option value="deny"    {{ $currentType === 'deny'    ? 'selected' : '' }}>Deny</option>
                                    </select>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
            @endif

            <div class="flex justify-end space-x-3">
                <a href="{{ route('admin.users.index') }}"
                    class="px-6 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50 transition">Cancel</a>
                <button type="submit"
                    class="px-6 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-medium hover:bg-indigo-700 transition">Update User</button>
            </div>
        </div>
    </form>
</div>
@endsection
