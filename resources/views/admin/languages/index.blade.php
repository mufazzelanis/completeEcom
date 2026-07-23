@extends('layouts.admin')
@section('title', 'Languages')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Languages</h1>
    <a href="{{ route('admin.translations.index') }}" class="text-sm text-orange-600 hover:text-orange-800 font-medium">Manage Translations &rarr;</a>
</div>

@if(session('success'))<div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">{{ session('success') }}</div>@endif
@if(session('error'))<div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">{{ session('error') }}</div>@endif
@if($errors->any())<div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">{{ $errors->first() }}</div>@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 dark:text-gray-100 mb-4">Add Language</h3>
        <form action="{{ route('admin.languages.store') }}" method="POST" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Language Code (ISO 639-1)</label>
                <input type="text" name="code" maxlength="10" placeholder="e.g. fr" required
                    class="w-full border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">English Name</label>
                <input type="text" name="name" placeholder="French" required
                    class="w-full border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Native Name</label>
                <input type="text" name="native_name" placeholder="Français" required
                    class="w-full border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Flag Emoji (optional)</label>
                <input type="text" name="flag_emoji" maxlength="10" placeholder="🇫🇷"
                    class="w-full border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Direction</label>
                <select name="direction" class="w-full border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                    <option value="ltr">Left to Right (LTR)</option>
                    <option value="rtl">Right to Left (RTL)</option>
                </select>
            </div>
            <button type="submit" class="w-full bg-orange-600 text-white py-2.5 rounded-xl text-sm font-semibold hover:bg-orange-700 transition">Add Language</button>
        </form>
    </div>

    <div class="lg:col-span-2">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-100 dark:border-gray-700 text-xs text-gray-500 dark:text-gray-400 uppercase">
                    <tr>
                        <th class="px-6 py-3 text-left">Language</th>
                        <th class="px-6 py-3 text-center">Direction</th>
                        <th class="px-6 py-3 text-center">Status</th>
                        <th class="px-6 py-3 text-center">Default</th>
                        <th class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                    @forelse($languages as $lang)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40" x-data="{ editing: false }">
                        <td class="px-6 py-3">
                            <div x-show="!editing" class="flex items-center gap-2">
                                <span class="text-lg">{{ $lang->flag_emoji }}</span>
                                <div>
                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $lang->name }} <span class="text-gray-400 font-mono text-xs">({{ $lang->code }})</span></p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $lang->native_name }}</p>
                                </div>
                            </div>
                            <form x-show="editing" x-cloak action="{{ route('admin.languages.update', $lang) }}" method="POST" class="flex flex-wrap items-center gap-2">
                                @csrf @method('PATCH')
                                <input type="text" name="flag_emoji" value="{{ $lang->flag_emoji }}" class="w-14 border border-gray-200 dark:border-gray-700 dark:bg-gray-900 rounded-lg px-2 py-1 text-sm">
                                <input type="text" name="name" value="{{ $lang->name }}" placeholder="Name" class="w-28 border border-gray-200 dark:border-gray-700 dark:bg-gray-900 rounded-lg px-2 py-1 text-sm">
                                <input type="text" name="native_name" value="{{ $lang->native_name }}" placeholder="Native name" class="w-28 border border-gray-200 dark:border-gray-700 dark:bg-gray-900 rounded-lg px-2 py-1 text-sm">
                                <select name="direction" class="border border-gray-200 dark:border-gray-700 dark:bg-gray-900 rounded-lg px-2 py-1 text-sm">
                                    <option value="ltr" @selected($lang->direction==='ltr')>LTR</option>
                                    <option value="rtl" @selected($lang->direction==='rtl')>RTL</option>
                                </select>
                                <button type="submit" class="text-xs bg-orange-600 text-white px-3 py-1.5 rounded-lg">Save</button>
                                <button type="button" @click="editing=false" class="text-xs text-gray-400">✕</button>
                            </form>
                        </td>
                        <td class="px-6 py-3 text-center text-xs text-gray-500 dark:text-gray-400 uppercase">{{ $lang->direction }}</td>
                        <td class="px-6 py-3 text-center">
                            <form action="{{ route('admin.languages.toggle', $lang) }}" method="POST" onsubmit="return {{ $lang->is_default && $lang->is_active ? 'confirm(\'This is the default language and cannot be disabled directly. Set another language as default first.\')' : 'true' }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="text-xs px-2.5 py-1 rounded-full font-medium {{ $lang->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                    {{ $lang->is_active ? 'Active' : 'Inactive' }}
                                </button>
                            </form>
                        </td>
                        <td class="px-6 py-3 text-center">
                            @if($lang->is_default)
                                <span class="text-xs px-2.5 py-1 rounded-full font-medium bg-orange-100 text-orange-700">Default</span>
                            @else
                                <form action="{{ route('admin.languages.set-default', $lang) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="text-xs text-gray-500 hover:text-orange-600 underline">Make Default</button>
                                </form>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-right flex items-center justify-end gap-3">
                            <button @click="editing=!editing" x-show="!editing" class="text-orange-600 text-sm hover:text-orange-800">Edit</button>
                            @unless($lang->is_default)
                            <form action="{{ route('admin.languages.destroy', $lang) }}" method="POST" onsubmit="return confirm('Delete this language? Its translations will remain but the switcher will no longer offer it.')">
                                @csrf @method('DELETE')
                                <button class="text-red-500 text-sm hover:text-red-700">Delete</button>
                            </form>
                            @endunless
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-6 py-12 text-center text-gray-400 text-sm">No languages yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <p class="text-xs text-gray-400 mt-3">The storefront language switcher only appears once "Enable Multi-Language Support" is turned on in <a href="{{ route('admin.settings.show', 'localization') }}" class="text-orange-600 hover:underline">Localization Settings</a>, and only when 2 or more languages are active here.</p>
    </div>
</div>
@endsection
