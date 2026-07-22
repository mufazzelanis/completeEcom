@extends('layouts.admin')
@section('title', 'Edit Role: ' . $role->display_name)

@section('content')
<div class="max-w-3xl">
    <a href="{{ route('admin.roles.index') }}" class="text-indigo-600 hover:text-indigo-700 text-sm flex items-center space-x-2 mb-6">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        <span>Back to Roles</span>
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

    <form action="{{ route('admin.roles.update', $role->id) }}" method="POST">
        @csrf @method('PUT')
        <div class="space-y-6">

            {{-- Role Info --}}
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h2 class="text-base font-semibold text-gray-800 mb-4">Role Details</h2>
                @if(!$role->is_system)
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Role Name *</label>
                            <input type="text" name="display_name" value="{{ old('display_name', $role->display_name) }}"
                                class="w-full border rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 {{ $errors->has('display_name') ? 'border-red-400' : 'border-gray-200' }}">
                            @error('display_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <input type="text" name="description" value="{{ old('description', $role->description) }}"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div class="flex items-center space-x-3">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="can_access_admin" value="1" {{ $role->can_access_admin ? 'checked' : '' }} class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                            </label>
                            <div>
                                <span class="text-sm font-medium text-gray-700">Admin Panel Access</span>
                                <p class="text-xs text-gray-400">Users with this role can log into the admin panel.</p>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="flex items-center space-x-3">
                        <span class="px-3 py-1 rounded-full text-sm font-semibold {{ \App\Models\User::roleBadgeClass($role->name) }}">{{ $role->display_name }}</span>
                        <span class="text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">System role — name cannot be changed</span>
                    </div>
                    @if($role->description)
                        <p class="text-sm text-gray-500 mt-2">{{ $role->description }}</p>
                    @endif
                @endif
            </div>

            {{-- Permissions --}}
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 mb-4 text-xs text-amber-700">
                    <strong>Not yet enforced:</strong> these permission checkboxes are saved but not currently checked anywhere in the admin panel. Any role with "Admin Panel Access" enabled has full access to every admin page, regardless of which permissions are selected here.
                </div>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base font-semibold text-gray-800">Permissions</h2>
                    <div class="flex items-center space-x-3 text-sm">
                        <button type="button" onclick="toggleAll(true)" class="text-indigo-600 hover:text-indigo-800 font-medium">Select All</button>
                        <span class="text-gray-300">|</span>
                        <button type="button" onclick="toggleAll(false)" class="text-gray-500 hover:text-gray-700 font-medium">Deselect All</button>
                    </div>
                </div>

                @foreach($permissions as $group => $groupPerms)
                    <div class="mb-5">
                        <div class="flex items-center space-x-3 mb-2">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $group }}</p>
                            <div class="flex-1 h-px bg-gray-100"></div>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach($groupPerms as $perm)
                                <label class="flex items-center space-x-3 bg-gray-50 hover:bg-indigo-50 rounded-xl px-4 py-3 cursor-pointer transition group">
                                    <input type="checkbox" name="permissions[]" value="{{ $perm->id }}"
                                        class="perm-checkbox w-4 h-4 text-indigo-600 rounded focus:ring-indigo-500"
                                        {{ in_array($perm->id, $assigned) ? 'checked' : '' }}>
                                    <span class="text-sm text-gray-700 group-hover:text-gray-900">{{ $perm->display_name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="flex justify-end space-x-3">
                <a href="{{ route('admin.roles.index') }}"
                    class="px-6 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50 transition">Cancel</a>
                <button type="submit"
                    class="px-6 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-medium hover:bg-indigo-700 transition">Save Changes</button>
            </div>
        </div>
    </form>
</div>

<script>
function toggleAll(state) {
    document.querySelectorAll('.perm-checkbox').forEach(cb => cb.checked = state);
}
</script>
@endsection
