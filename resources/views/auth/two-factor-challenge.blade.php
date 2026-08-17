<x-guest-layout>
    @php
        // Prefill the boxes after a failed attempt instead of making the user retype —
        // but only when the previous submission looks like an OTP (6 digits); a failed
        // recovery-code attempt reopens in recovery mode instead (see x-data below).
        $oldCode = old('code', '');
        $oldIsOtp = (bool) preg_match('/^\d{6}$/', $oldCode);
    @endphp

    <div class="text-center mb-6">
        <div class="mx-auto mb-4 w-14 h-14 rounded-2xl bg-orange-50 dark:bg-orange-500/10 flex items-center justify-center">
            <svg class="w-7 h-7 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
        </div>
        <h1 class="text-xl font-bold text-gray-900 dark:text-gray-100">Verify your identity</h1>
        <p class="mt-1.5 text-sm text-gray-500 dark:text-gray-400">
            @if($maskedEmail ?? null)
                We've sent a 6-digit code to <span class="font-medium text-gray-700 dark:text-gray-300">{{ $maskedEmail }}</span>
            @else
                We've sent a 6-digit code to your email
            @endif
        </p>
    </div>

    @if(session('error'))
        <div class="mb-4 flex items-start gap-2.5 rounded-xl border border-red-200 dark:border-red-900 bg-red-50 dark:bg-red-950/40 px-4 py-3 text-sm text-red-700 dark:text-red-400">
            <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/></svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if($devCode ?? null)
    <div class="mb-5 rounded-xl border border-amber-200 dark:border-amber-900 bg-amber-50 dark:bg-amber-950/40 px-4 py-3 text-sm text-amber-800 dark:text-amber-400">
        <strong>Dev only</strong> (never shown in production) — your code is
        <span class="font-mono font-bold tracking-widest">{{ $devCode }}</span>
    </div>
    @endif

    <form method="POST" action="{{ route('two-factor.verify') }}"
          x-data="{
              mode: {{ Js::from($oldIsOtp || $oldCode === '' ? 'otp' : 'recovery') }},
              digits: {{ Js::from($oldIsOtp ? str_split($oldCode) : ['', '', '', '', '', '']) }},
              recoveryCode: {{ Js::from($oldIsOtp || $oldCode === '' ? '' : $oldCode) }},
              submitting: false,
              secondsLeft: 300,
              resendCooldown: 0,
              timer: null,
              init() {
                  this.timer = setInterval(() => {
                      if (this.secondsLeft > 0) this.secondsLeft--;
                      if (this.resendCooldown > 0) this.resendCooldown--;
                  }, 1000);
                  this.resendCooldown = 30;
                  this.$nextTick(() => this.focusBox(this.digits.findIndex(d => !d) === -1 ? 5 : this.digits.findIndex(d => !d)));
              },
              formatTime(s) {
                  const m = Math.floor(s / 60), r = s % 60;
                  return m + ':' + String(r).padStart(2, '0');
              },
              focusBox(i) {
                  // x-ref can't be parameterized per x-for iteration, so boxes are
                  // addressed by id instead.
                  const el = document.getElementById('otp-box-' + i);
                  if (el) el.focus();
              },
              onInput(i, e) {
                  let v = e.target.value.replace(/\D/g, '');
                  if (v.length > 1) {
                      // Mobile SMS autofill / password managers can drop the whole code into one box.
                      v.split('').slice(0, 6 - i).forEach((ch, j) => { this.digits[i + j] = ch; });
                      this.focusBox(Math.min(i + v.length, 5));
                  } else {
                      this.digits[i] = v;
                      if (v && i < 5) this.focusBox(i + 1);
                  }
                  this.maybeAutoSubmit();
              },
              onBackspace(i, e) {
                  if (!this.digits[i] && i > 0) {
                      e.preventDefault();
                      this.digits[i - 1] = '';
                      this.focusBox(i - 1);
                  }
              },
              onPaste(e) {
                  const text = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
                  if (!text) return;
                  e.preventDefault();
                  text.split('').slice(0, 6).forEach((ch, j) => { this.digits[j] = ch; });
                  this.focusBox(Math.min(text.length, 5));
                  this.maybeAutoSubmit();
              },
              maybeAutoSubmit() {
                  if (this.digits.every(d => d !== '') && !this.submitting) {
                      this.submitting = true;
                      this.$nextTick(() => this.$refs.form.requestSubmit());
                  }
              },
              useRecovery() {
                  this.mode = 'recovery';
                  this.$nextTick(() => this.$refs.recoveryInput.focus());
              },
              useOtp() {
                  this.mode = 'otp';
                  this.$nextTick(() => this.focusBox(0));
              },
          }"
          x-ref="form"
          @submit="submitting = true">
        @csrf

        {{-- OTP mode: six individually-boxed digits, auto-advancing and paste-aware --}}
        <div x-show="mode === 'otp'" x-cloak role="group" aria-label="6-digit verification code">
            <input type="hidden" :name="mode === 'otp' ? 'code' : null" :value="digits.join('')">
            <div class="flex items-center justify-between gap-2" @paste="onPaste($event)">
                <template x-for="(d, i) in digits" :key="i">
                    <input type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="1"
                           :id="'otp-box-' + i" :value="digits[i]"
                           :aria-label="'Digit ' + (i + 1) + ' of 6'"
                           @input="onInput(i, $event)"
                           @keydown.backspace="onBackspace(i, $event)"
                           class="otp-box w-full aspect-square text-center text-xl font-bold rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 focus:border-orange-500 focus:ring-2 focus:ring-orange-500/30 outline-none transition">
                </template>
            </div>
            <x-input-error :messages="$errors->get('code')" class="mt-2" />

            <div class="flex items-center justify-between mt-3 text-xs text-gray-400 dark:text-gray-500">
                <span x-show="secondsLeft > 0" x-text="'Code expires in ' + formatTime(secondsLeft)"></span>
                <span x-show="secondsLeft === 0" class="text-red-500">Code likely expired — request a new one</span>
                <button type="button" @click="useRecovery()" class="text-orange-600 hover:text-orange-700 font-medium">
                    Use a recovery code
                </button>
            </div>
        </div>

        {{-- Recovery-code mode: a plain field, since recovery codes aren't 6-digit numbers --}}
        <div x-show="mode === 'recovery'" x-cloak>
            <x-input-label for="recovery_code" value="Recovery Code" />
            <div class="relative mt-1.5">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400 dark:text-gray-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </span>
                <x-text-input id="recovery_code" x-ref="recoveryInput" x-model="recoveryCode"
                       x-bind:name="mode === 'recovery' ? 'code' : null"
                       type="text" autocomplete="off" placeholder="xxxx-xxxx-xxxx" class="pl-11" />
            </div>
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
            <button type="button" @click="useOtp()" class="mt-3 text-xs text-orange-600 hover:text-orange-700 font-medium">
                &larr; Back to verification code
            </button>
        </div>

        <div class="flex items-center justify-between mt-6">
            <a href="{{ route('two-factor.challenge') }}"
               class="text-sm font-medium"
               :class="resendCooldown > 0 ? 'text-gray-300 dark:text-gray-600 pointer-events-none' : 'text-orange-600 hover:text-orange-700'">
                <span x-show="resendCooldown === 0">Resend code</span>
                <span x-show="resendCooldown > 0" x-text="'Resend code (' + resendCooldown + 's)'"></span>
            </a>
            <x-primary-button type="submit" x-bind:disabled="submitting" class="disabled:opacity-60">
                <svg x-show="submitting" x-cloak class="animate-spin -ml-0.5 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span x-text="submitting ? 'Verifying…' : 'Verify'"></span>
            </x-primary-button>
        </div>
    </form>

    <p class="mt-6 text-center text-xs text-gray-400 dark:text-gray-500">
        Lost access to your email? A recovery code from your account setup will also work.
    </p>
</x-guest-layout>
