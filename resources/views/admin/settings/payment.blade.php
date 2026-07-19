@extends('admin.settings.layout')
@section('settings-title', 'Payment Gateways')

@section('settings-content')
<form method="POST" action="{{ route('admin.settings.update', 'payment') }}">
@csrf @method('PATCH')

@php
$gateways = [
    'cod'         => ['name' => 'Cash on Delivery', 'color' => 'amber',   'icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z', 'fields' => []],
    'bkash'       => ['name' => 'bKash',            'color' => 'pink',    'icon' => 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z', 'fields' => [
        ['key'=>'bkash_merchant_number','label'=>'Merchant Number','type'=>'text','ph'=>'01XXXXXXXXX'],
        ['key'=>'bkash_app_key','label'=>'App Key','type'=>'text','ph'=>'bKash App Key'],
        ['key'=>'bkash_app_secret','label'=>'App Secret','type'=>'password','ph'=>'••••••••'],
        ['key'=>'bkash_username','label'=>'Username','type'=>'text','ph'=>''],
        ['key'=>'bkash_password','label'=>'Password','type'=>'password','ph'=>'••••••••'],
    ]],
    'nagad'       => ['name' => 'Nagad',            'color' => 'orange',  'icon' => 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z', 'fields' => [
        ['key'=>'nagad_merchant_id','label'=>'Merchant ID','type'=>'text','ph'=>''],
        ['key'=>'nagad_merchant_number','label'=>'Merchant Number','type'=>'text','ph'=>'01XXXXXXXXX'],
        ['key'=>'nagad_public_key','label'=>'Public Key','type'=>'textarea','ph'=>''],
        ['key'=>'nagad_private_key','label'=>'Private Key','type'=>'textarea','ph'=>''],
    ]],
    'rocket'      => ['name' => 'Rocket (DBBL)',    'color' => 'purple',  'icon' => 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z', 'fields' => [
        ['key'=>'rocket_merchant_number','label'=>'Merchant Number','type'=>'text','ph'=>'01XXXXXXXXX'],
    ]],
    'bank'        => ['name' => 'Bank Transfer',    'color' => 'blue',    'icon' => 'M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z', 'fields' => [
        ['key'=>'bank_name','label'=>'Bank Name','type'=>'text','ph'=>'Dutch-Bangla Bank'],
        ['key'=>'bank_account_name','label'=>'Account Name','type'=>'text','ph'=>''],
        ['key'=>'bank_account_number','label'=>'Account Number','type'=>'text','ph'=>''],
        ['key'=>'bank_routing_number','label'=>'Routing Number','type'=>'text','ph'=>''],
        ['key'=>'bank_branch','label'=>'Branch Name','type'=>'text','ph'=>'Gulshan Branch'],
    ]],
    'sslcommerz'  => ['name' => 'SSLCommerz',       'color' => 'green',   'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z', 'fields' => [
        ['key'=>'sslcommerz_store_id','label'=>'Store ID','type'=>'text','ph'=>'your_store_id'],
        ['key'=>'sslcommerz_store_password','label'=>'Store Password','type'=>'password','ph'=>'••••••••'],
        ['key'=>'sslcommerz_sandbox','label'=>'Sandbox Mode','type'=>'checkbox','ph'=>''],
    ]],
    'stripe'      => ['name' => 'Stripe',           'color' => 'indigo',  'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z', 'fields' => [
        ['key'=>'stripe_public_key','label'=>'Publishable Key','type'=>'text','ph'=>'pk_live_...'],
        ['key'=>'stripe_secret_key','label'=>'Secret Key','type'=>'password','ph'=>'sk_live_...'],
        ['key'=>'stripe_webhook_secret','label'=>'Webhook Secret','type'=>'password','ph'=>'whsec_...'],
    ]],
    'paypal'      => ['name' => 'PayPal',           'color' => 'sky',     'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z', 'fields' => [
        ['key'=>'paypal_client_id','label'=>'Client ID','type'=>'text','ph'=>''],
        ['key'=>'paypal_secret','label'=>'Secret','type'=>'password','ph'=>''],
        ['key'=>'paypal_sandbox','label'=>'Sandbox Mode','type'=>'checkbox','ph'=>''],
    ]],
];
@endphp

@foreach($gateways as $slug => $gw)
<div class="bg-white rounded-xl shadow-sm border overflow-hidden" x-data="{ open: {{ setting("{$slug}_enabled",'0')==='1' ? 'true' : 'false' }} }">
    <div class="flex items-center justify-between px-5 py-4 border-b">
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-{{ $gw['color'] }}-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $gw['icon'] }}"/>
            </svg>
            <span class="text-sm font-semibold text-gray-900">{{ $gw['name'] }}</span>
        </div>
        <div class="flex items-center gap-3">
            <button type="button" @click="open = !open" class="text-xs text-orange-600 hover:underline" x-text="open ? 'Collapse' : 'Configure'"></button>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="hidden" name="{{ $slug }}_enabled" value="0">
                <input type="checkbox" name="{{ $slug }}_enabled" value="1" class="sr-only peer"
                       @checked(setting("{$slug}_enabled",'0') == '1')
                       @change="open = $event.target.checked">
                <div class="w-10 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition peer-checked:bg-orange-600"></div>
            </label>
        </div>
    </div>

    @if(!empty($gw['fields']))
    <div x-show="open" x-cloak class="px-5 py-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($gw['fields'] as $field)
            @if($field['type'] === 'checkbox')
            <label class="flex items-center gap-2 cursor-pointer col-span-2">
                <input type="hidden" name="{{ $field['key'] }}" value="0">
                <input type="checkbox" name="{{ $field['key'] }}" value="1" class="rounded text-orange-600"
                       @checked(setting($field['key'],'0') == '1')>
                <span class="text-sm text-gray-700">{{ $field['label'] }}</span>
            </label>
            @elseif($field['type'] === 'textarea')
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $field['label'] }}</label>
                <textarea name="{{ $field['key'] }}" rows="3"
                          class="w-full border rounded-lg px-3 py-2 text-xs font-mono focus:ring-2 focus:ring-orange-500"
                          placeholder="{{ $field['ph'] }}">{{ setting($field['key'], '') }}</textarea>
            </div>
            @else
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $field['label'] }}</label>
                <input type="{{ $field['type'] }}" name="{{ $field['key'] }}" value="{{ setting($field['key'], '') }}"
                       class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500"
                       placeholder="{{ $field['ph'] }}" autocomplete="off">
            </div>
            @endif
            @endforeach
        </div>
    </div>
    @endif
</div>
@endforeach

<div class="flex justify-end">
    <button type="submit" class="px-6 py-2 bg-orange-600 text-white rounded-lg text-sm font-semibold hover:bg-orange-700 transition">Save Payment Settings</button>
</div>
</form>
@endsection
