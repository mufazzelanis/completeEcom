@extends('layouts.admin')
@section('title', 'Two-Factor Authentication')

@section('content')
<div class="max-w-xl">
    <h1 class="text-2xl font-bold text-gray-900 mb-2">Set Up Two-Factor Authentication</h1>
    <p class="text-sm text-gray-500 mb-6">Your admin account requires two-factor authentication. Scan the QR code below with an authenticator app (Google Authenticator, Authy, 1Password, etc.), then enter the 6-digit code it shows to finish setup.</p>

    @if(session('error'))<div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">{{ session('error') }}</div>@endif

    <div class="bg-white rounded-2xl shadow-sm p-8 space-y-6">
        <div class="flex flex-col items-center gap-4">
            {{-- Rendered entirely in the browser from the otpauth:// URI already on this
                 authenticated page — the secret is never sent to any third-party service. --}}
            <div id="qr-canvas" class="border border-gray-100 rounded-xl p-3"></div>
            <div class="text-center">
                <p class="text-xs text-gray-400 mb-1">Can't scan? Enter this key manually:</p>
                <p class="font-mono text-sm font-semibold text-gray-800 tracking-widest">{{ $secret }}</p>
            </div>
        </div>

        <form action="{{ route('admin.two-factor.confirm') }}" method="POST" class="max-w-xs mx-auto">
            @csrf
            <label class="block text-sm font-medium text-gray-700 mb-1 text-center">Enter the 6-digit code</label>
            <input type="text" name="code" inputmode="numeric" maxlength="6" autocomplete="one-time-code" autofocus placeholder="123456"
                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-center text-lg tracking-widest font-mono focus:outline-none focus:ring-2 focus:ring-orange-500 @error('code') border-red-400 @enderror">
            @error('code')<p class="text-red-500 text-xs mt-1 text-center">{{ $message }}</p>@enderror
            <button type="submit" class="w-full mt-4 bg-orange-600 text-white py-2.5 rounded-xl text-sm font-semibold hover:bg-orange-700 transition">Verify & Enable</button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
<script>
    QRCode.toCanvas(document.createElement('canvas'), @json($uri), { width: 220 }, function (err, canvas) {
        if (!err) document.getElementById('qr-canvas').appendChild(canvas);
    });
</script>
@endsection
