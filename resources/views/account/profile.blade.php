@extends('layouts.account')
@section('title', 'My Profile')

@section('content')
<h1 class="text-xl font-bold text-gray-800 mb-5">My Profile</h1>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    {{-- Avatar + quick info --}}
    <div class="bg-white rounded-2xl shadow-sm p-6 text-center">
        <div x-data="{ preview: null }" class="mb-4">
            <img :src="preview || '{{ $user->avatar_url }}'" class="w-24 h-24 rounded-full object-cover mx-auto mb-3 border-4 border-indigo-100" id="avatar-preview">
            <p class="font-semibold text-gray-800">{{ $user->name }}</p>
            <p class="text-sm text-gray-400">{{ $user->email }}</p>
            @if($user->created_at)
            <p class="text-xs text-gray-400 mt-1">Member since {{ $user->created_at->format('M Y') }}</p>
            @endif
        </div>
        <label class="cursor-pointer inline-flex items-center gap-2 bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition text-sm font-medium px-4 py-2 rounded-xl">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            Upload Photo
            <input type="file" name="avatar" form="profile-form" accept="image/*" class="sr-only"
                onchange="const reader = new FileReader(); reader.onload = e => document.getElementById('avatar-preview').src = e.target.result; reader.readAsDataURL(this.files[0])">
        </label>
        <p class="text-xs text-gray-400 mt-2">JPG, PNG up to 2MB</p>
    </div>

    {{-- Personal Info --}}
    <div class="lg:col-span-2 space-y-4">
        <form id="profile-form" action="{{ route('account.profile.update') }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-2xl shadow-sm p-6 space-y-4">
            @csrf @method('PATCH')
            <h2 class="font-semibold text-gray-800">Personal Information</h2>

            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2 sm:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="col-span-2 sm:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                    <input type="text" name="phone" value="{{ old('phone', $user->phone) }}"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth</label>
                    <input type="date" name="date_of_birth" value="{{ old('date_of_birth', $user->date_of_birth?->format('Y-m-d')) }}"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                    <select name="gender" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">Prefer not to say</option>
                        @foreach(['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $val => $label)
                        <option value="{{ $val }}" {{ old('gender', $user->gender) === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Bio</label>
                    <textarea name="bio" rows="3" maxlength="500" placeholder="Tell us a little about yourself..."
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none">{{ old('bio', $user->bio) }}</textarea>
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2.5 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition">Save Changes</button>
            </div>
        </form>

        {{-- Change Password --}}
        <form action="{{ route('account.password.update') }}" method="POST" class="bg-white rounded-2xl shadow-sm p-6 space-y-4">
            @csrf @method('PATCH')
            <h2 class="font-semibold text-gray-800">Change Password</h2>
            <div class="grid grid-cols-1 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                    <input type="password" name="current_password" required
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('current_password')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                        <input type="password" name="password" required minlength="8"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @error('password')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                        <input type="password" name="password_confirmation" required
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>
            </div>
            <button type="submit" class="bg-gray-800 text-white px-6 py-2.5 rounded-xl text-sm font-semibold hover:bg-gray-900 transition">Update Password</button>
        </form>
    </div>
</div>
@endsection
