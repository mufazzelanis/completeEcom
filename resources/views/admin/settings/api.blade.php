@extends('admin.settings.layout')
@section('settings-title', 'API & Integrations')

@section('settings-content')

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">API Keys</h2>
    <p class="text-sm text-gray-500">These are read-only reference keys. Manage sensitive keys via your <code class="bg-gray-100 px-1 rounded">.env</code> file.</p>
    <div class="space-y-3">
        @foreach([
            'APP_KEY'      => 'Application Key',
            'APP_ENV'      => 'Environment',
            'APP_URL'      => 'Application URL',
            'MAIL_MAILER'  => 'Mail Driver',
            'QUEUE_CONNECTION' => 'Queue Driver',
            'CACHE_STORE'  => 'Cache Driver',
        ] as $key => $label)
        <div class="flex items-center gap-3 py-2 border-b border-gray-50 last:border-0">
            <span class="text-sm text-gray-600 w-40">{{ $label }}</span>
            <code class="flex-1 text-xs bg-gray-100 px-3 py-1.5 rounded font-mono text-gray-700 truncate">
                {{ $key === 'APP_KEY' ? '(hidden)' : env($key, '—') }}
            </code>
        </div>
        @endforeach
    </div>
</div>

<form method="POST" action="{{ route('admin.settings.update', 'api') }}">
@csrf @method('PATCH')

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Webhooks</h2>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Webhook Secret (for incoming webhooks)</label>
        <input type="text" name="webhook_secret" value="{{ setting('webhook_secret', '') }}"
               class="w-full border rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-orange-500"
               placeholder="Leave blank to auto-generate" autocomplete="off">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Order Webhook URL (POST new orders to)</label>
        <input type="url" name="order_webhook_url" value="{{ setting('order_webhook_url', '') }}"
               class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500"
               placeholder="https://your-erp.com/webhook/orders">
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Third-Party Integrations</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Mailchimp API Key</label>
            <input type="password" name="mailchimp_api_key" value="{{ setting('mailchimp_api_key', '') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500"
                   placeholder="••••••••" autocomplete="new-password">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Mailchimp Audience ID</label>
            <input type="text" name="mailchimp_audience_id" value="{{ setting('mailchimp_audience_id', '') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Google Maps API Key</label>
            <input type="text" name="google_maps_key" value="{{ setting('google_maps_key', '') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-orange-500"
                   placeholder="AIzaXXXXXX...">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Intercom App ID</label>
            <input type="text" name="intercom_app_id" value="{{ setting('intercom_app_id', '') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-5">
    <div class="pb-2 border-b">
        <h2 class="text-base font-semibold text-gray-900">Social Login</h2>
        <p class="text-xs text-gray-400 mt-1">Powers the "Continue with ..." buttons on the login/register pages (see Auth\SocialAuthController). A provider's button only appears once it's switched on below AND both its fields are filled in. Values saved here take priority over GOOGLE_CLIENT_ID/etc. in <code class="bg-gray-100 px-1 rounded">.env</code>, so either works — this is just the option that doesn't need server (SSH) access.</p>
    </div>

    @foreach([
        'google' => 'Google',
        'facebook' => 'Facebook',
        'linkedin' => 'LinkedIn',
    ] as $provider => $label)
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 {{ !$loop->last ? 'pb-5 border-b border-gray-50' : '' }}">
        <div class="md:col-span-2 flex items-center justify-between gap-3">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="hidden" name="{{ $provider }}_login_enabled" value="0">
                <input type="checkbox" name="{{ $provider }}_login_enabled" value="1" class="rounded text-orange-600"
                       @checked(setting("{$provider}_login_enabled", '1') == '1')>
                <span class="text-sm font-medium text-gray-700">{{ $label }} login enabled</span>
            </label>
            <span class="text-xs text-gray-500">
                Redirect URI: <code class="bg-gray-100 px-1.5 py-0.5 rounded font-mono text-[11px] select-all">{{ url("/auth/{$provider}/callback") }}</code>
            </span>
        </div>
        {{-- Switching this off (e.g. while a provider's own developer console is mid-setup
             or temporarily broken, like a fresh app stuck in Facebook's review/propagation
             delay) hides its button immediately without losing the saved Client ID/Secret —
             flip it back on the moment the provider's side is sorted, no re-entering needed. --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $label }} Client ID</label>
            <input type="text" name="{{ $provider }}_client_id" value="{{ setting("{$provider}_client_id", '') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-orange-500" autocomplete="off">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $label }} Client Secret</label>
            <input type="password" name="{{ $provider }}_client_secret" value="{{ setting("{$provider}_client_secret", '') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-orange-500"
                   placeholder="{{ setting("{$provider}_client_secret") ? '••••••••' : '' }}" autocomplete="new-password">
        </div>
    </div>
    @endforeach
</div>

<div class="flex justify-end">
    <button type="submit" class="px-6 py-2 bg-orange-600 text-white rounded-lg text-sm font-semibold hover:bg-orange-700 transition">Save API Settings</button>
</div>
</form>
@endsection
