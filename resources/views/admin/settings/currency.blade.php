@extends('admin.settings.layout')
@section('settings-title', 'Currency Settings')

@section('settings-content')
<form method="POST" action="{{ route('admin.settings.update', 'currency') }}">
@csrf @method('PATCH')

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Default Currency</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Currency Code</label>
            <input type="text" name="currency_code" value="{{ setting('currency_code', 'BDT') }}"
                   maxlength="5"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500"
                   placeholder="BDT">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Currency Symbol</label>
            <input type="text" name="currency_symbol" value="{{ setting('currency_symbol', '৳') }}"
                   maxlength="5"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500"
                   placeholder="৳">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Currency Name</label>
            <input type="text" name="currency_name" value="{{ setting('currency_name', 'Bangladeshi Taka') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Symbol Position</label>
            <select name="currency_position" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
                <option value="left" @selected(setting('currency_position','left')==='left')>Left (৳100)</option>
                <option value="right" @selected(setting('currency_position','left')==='right')>Right (100৳)</option>
                <option value="left_space" @selected(setting('currency_position','left')==='left_space')>Left with space (৳ 100)</option>
                <option value="right_space" @selected(setting('currency_position','left')==='right_space')>Right with space (100 ৳)</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Decimal Places</label>
            <select name="decimal_places" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
                @for($i=0; $i<=4; $i++)
                <option value="{{ $i }}" @selected((int)setting('decimal_places','0')===$i)>{{ $i }}</option>
                @endfor
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Thousand Separator</label>
            <select name="thousand_separator" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
                <option value="," @selected(setting('thousand_separator',',')===',')>Comma (1,000)</option>
                <option value="." @selected(setting('thousand_separator',',')==='.')>Period (1.000)</option>
                <option value=" " @selected(setting('thousand_separator',',' )===' ')>Space (1 000)</option>
                <option value="" @selected(setting('thousand_separator',',')===''  )>None (1000)</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Decimal Separator</label>
            <select name="decimal_separator" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
                <option value="." @selected(setting('decimal_separator','.')==='.')>Period (1.00)</option>
                <option value="," @selected(setting('decimal_separator','.')===',' )>Comma (1,00)</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Exchange Rate (vs USD)</label>
            <input type="number" name="exchange_rate" value="{{ setting('exchange_rate', '110') }}"
                   step="0.01" min="0"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
        </div>
    </div>

    @php $preview = format_currency(12345.6); @endphp
    <div class="mt-2 p-3 bg-orange-50 rounded-lg">
        <p class="text-sm text-orange-700">Preview: <strong>{{ $preview }}</strong></p>
    </div>

    <div class="flex items-center gap-2 pt-2">
        <input type="hidden" name="multi_currency_enabled" value="0">
        <input type="checkbox" name="multi_currency_enabled" id="multi_curr" value="1" class="rounded text-orange-600"
               @checked(setting('multi_currency_enabled','0') == '1')>
        <label for="multi_curr" class="text-sm text-gray-700">Enable Multi-Currency Support</label>
    </div>
</div>

<div class="flex justify-end">
    <button type="submit" class="px-6 py-2 bg-orange-600 text-white rounded-lg text-sm font-semibold hover:bg-orange-700 transition">Save Currency</button>
</div>
</form>
@endsection
