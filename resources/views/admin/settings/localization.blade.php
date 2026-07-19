@extends('admin.settings.layout')
@section('settings-title', 'Localization')

@section('settings-content')
<form method="POST" action="{{ route('admin.settings.update', 'localization') }}">
@csrf @method('PATCH')

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Regional Settings</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Default Language</label>
            <select name="default_language" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
                <option value="en" @selected(setting('default_language','en')==='en')>English</option>
                <option value="bn" @selected(setting('default_language','en')==='bn')>Bengali (বাংলা)</option>
                <option value="ar" @selected(setting('default_language','en')==='ar')>Arabic</option>
                <option value="fr" @selected(setting('default_language','en')==='fr')>French</option>
                <option value="de" @selected(setting('default_language','en')==='de')>German</option>
                <option value="hi" @selected(setting('default_language','en')==='hi')>Hindi</option>
                <option value="es" @selected(setting('default_language','en')==='es')>Spanish</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Time Zone</label>
            <select name="timezone" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
                @foreach(timezone_identifiers_list() as $tz)
                <option value="{{ $tz }}" @selected(setting('timezone', 'Asia/Dhaka')===$tz)>{{ $tz }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Date Format</label>
            <select name="date_format" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
                @php $df = setting('date_format', 'd M Y'); @endphp
                <option value="d M Y" @selected($df==='d M Y')>{{ now()->format('d M Y') }} (d M Y)</option>
                <option value="d/m/Y" @selected($df==='d/m/Y')>{{ now()->format('d/m/Y') }} (d/m/Y)</option>
                <option value="m/d/Y" @selected($df==='m/d/Y')>{{ now()->format('m/d/Y') }} (m/d/Y)</option>
                <option value="Y-m-d" @selected($df==='Y-m-d')>{{ now()->format('Y-m-d') }} (Y-m-d)</option>
                <option value="D, d M Y" @selected($df==='D, d M Y')>{{ now()->format('D, d M Y') }} (D, d M Y)</option>
                <option value="l, F j, Y" @selected($df==='l, F j, Y')>{{ now()->format('l, F j, Y') }}</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Time Format</label>
            <select name="time_format" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
                @php $tf = setting('time_format', 'h:i A'); @endphp
                <option value="h:i A" @selected($tf==='h:i A')>{{ now()->format('h:i A') }} (12-hour)</option>
                <option value="H:i" @selected($tf==='H:i')>{{ now()->format('H:i') }} (24-hour)</option>
                <option value="h:i:s A" @selected($tf==='h:i:s A')>{{ now()->format('h:i:s A') }} (12-hour with seconds)</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Country</label>
            <input type="text" name="country" value="{{ setting('country', 'Bangladesh') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">State / Region</label>
            <input type="text" name="state_region" value="{{ setting('state_region', '') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500"
                   placeholder="Dhaka Division">
        </div>
    </div>
    <div class="flex items-center gap-2 pt-2">
        <input type="hidden" name="multi_language_enabled" value="0">
        <input type="checkbox" name="multi_language_enabled" id="multi_lang" value="1" class="rounded text-orange-600"
               @checked(setting('multi_language_enabled','0') == '1')>
        <label for="multi_lang" class="text-sm text-gray-700">Enable Multi-Language Support</label>
    </div>
</div>

<div class="flex justify-end">
    <button type="submit" class="px-6 py-2 bg-orange-600 text-white rounded-lg text-sm font-semibold hover:bg-orange-700 transition">Save Localization</button>
</div>
</form>
@endsection
