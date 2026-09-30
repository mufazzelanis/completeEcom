@extends('layouts.account')
@section('title', 'Two-Factor Authentication')
@section('pageTitle', 'Two-Factor Authentication')

@section('content')
<div class="max-w-xl">
    <a href="{{ route('account.security') }}" class="text-indigo-600 hover:text-indigo-700 text-sm flex items-center gap-1.5 mb-4">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Back to Security
    </a>
    <h1 class="text-xl font-bold text-gray-800 mb-2">Turn On Two-Factor Authentication</h1>
    <p class="text-sm text-gray-500 mb-6">We've sent a 6-digit code to <strong>{{ $email }}</strong> — enter it below to finish setup.</p>

    @if(session('error'))<div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">{{ session('error') }}</div>@endif

    <div class="bg-white rounded-2xl shadow-sm p-8 space-y-6">
        <form action="{{ route('account.two-factor.confirm') }}" method="POST" class="max-w-xs mx-auto">
            @csrf
            <label class="block text-sm font-medium text-gray-700 mb-1 text-center">Enter the 6-digit code</label>
            <input type="text" name="code" inputmode="numeric" maxlength="6" autocomplete="one-time-code" autofocus placeholder="123456"
                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-center text-lg tracking-widest font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('code') border-red-400 @enderror">
            @error('code')<p class="text-red-500 text-xs mt-1 text-center">{{ $message }}</p>@enderror
            <button type="submit" class="w-full mt-4 bg-indigo-600 text-white py-2.5 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition">Verify & Enable</button>
        </form>
        <p class="text-center text-xs text-gray-400">
            Didn't get the email? <a href="{{ route('account.two-factor.show', ['resend' => 1]) }}" class="text-indigo-600 hover:text-indigo-700 font-medium">Send a new code</a>
        </p>
    </div>
</div>
@endsection
