@props([
    'label',
    'name',
    'type' => 'text',
    'icon' => null,       // mail | user | lock | null
    'errorKey' => null,   // defaults to $name — override when the field posts under a different key
])
@php
    $id = $attributes->get('id', $name);
    $isPassword = $type === 'password';
    // Blade's <x-component> tag compiler can't parse an @if/@endif sitting inside the
    // opening tag's own attribute list (it silently fails to recognize the tag at all,
    // leaving it as dead literal text) — so the type-toggle binding below has to be a
    // single always-present expression rather than a conditionally-included attribute.
    $typeBinding = $isPassword ? "show ? 'text' : 'password'" : "'" . $type . "'";
@endphp

<div>
    <x-input-label :for="$id" :value="$label" />

    <div class="relative mt-1.5" x-data="{ show: false }">
        @if($icon)
        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400 dark:text-gray-500">
            @switch($icon)
                @case('mail')
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    @break
                @case('lock')
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    @break
                @default
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            @endswitch
        </span>
        @endif

        <x-text-input
            :id="$id"
            :name="$name"
            :type="$isPassword ? 'password' : $type"
            :x-bind:type="$typeBinding"
            {{ $attributes->except(['id', 'class'])->merge([
                'class' => trim(($icon ? 'pl-11 ' : '') . ($isPassword ? 'pr-11' : '')),
            ]) }}
        />

        @if($isPassword)
        <button type="button" @click="show = !show" tabindex="-1"
                class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300"
                :aria-label="show ? 'Hide password' : 'Show password'">
            <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <svg x-cloak x-show="show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3.98 8.223A10.477 10.477 0 001.934 12c1.292 4.338 5.31 7.5 10.066 7.5.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
        </button>
        @endif
    </div>

    <x-input-error :messages="$errors->get($errorKey ?? $name)" class="mt-2" />
</div>
