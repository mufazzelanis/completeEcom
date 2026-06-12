@extends('layouts.account')
@section('title', 'Address Book')

@section('content')
<div class="flex items-center justify-between mb-5">
    <h1 class="text-xl font-bold text-gray-800">Address Book</h1>
    <a href="{{ route('account.addresses.create') }}" class="flex items-center gap-2 bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Add Address
    </a>
</div>

@if($addresses->isEmpty())
<div class="bg-white rounded-2xl shadow-sm p-16 text-center">
    <svg class="w-16 h-16 text-gray-200 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
    <p class="text-gray-500 text-sm">No addresses saved yet.</p>
    <a href="{{ route('account.addresses.create') }}" class="mt-4 inline-block bg-indigo-600 text-white px-5 py-2 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition">Add your first address</a>
</div>
@else
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    @foreach($addresses as $address)
    <div class="bg-white rounded-2xl shadow-sm p-5 relative {{ $address->is_default ? 'ring-2 ring-indigo-500' : '' }}">
        @if($address->is_default)
        <span class="absolute top-4 right-4 text-xs bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full font-semibold">Default</span>
        @endif
        <div class="text-sm text-gray-700 space-y-0.5 mb-4 pr-16">
            <p class="font-semibold text-gray-900">{{ $address->name }}</p>
            <p>{{ $address->phone }}</p>
            <p>{{ $address->address_line1 }}</p>
            @if($address->address_line2)<p>{{ $address->address_line2 }}</p>@endif
            <p>{{ $address->city }}@if($address->state), {{ $address->state }}@endif @if($address->zip) {{ $address->zip }}@endif</p>
            <p>{{ $address->country }}</p>
        </div>
        <div class="flex items-center gap-3 border-t border-gray-100 pt-3">
            <a href="{{ route('account.addresses.edit', $address) }}" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">Edit</a>
            @if(!$address->is_default)
            <form action="{{ route('account.addresses.default', $address) }}" method="POST">
                @csrf @method('PATCH')
                <button class="text-xs text-gray-500 hover:text-gray-700 font-medium">Set Default</button>
            </form>
            <form action="{{ route('account.addresses.destroy', $address) }}" method="POST" onsubmit="return confirm('Remove this address?')">
                @csrf @method('DELETE')
                <button class="text-xs text-red-500 hover:text-red-700 font-medium">Remove</button>
            </form>
            @endif
        </div>
    </div>
    @endforeach
</div>
@endif
@endsection
