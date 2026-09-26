{{-- SMS code check for the checkout phone number. Only rendered when Settings → Orders → Phone check = "otp".
     Server re-checks everything in CheckoutController::store; this just makes the step easy. --}}
@if(\App\Http\Controllers\CheckoutPhoneController::required())
<div id="phone-verify" class="mt-2 text-sm" data-verified="{{ \App\Http\Controllers\CheckoutPhoneController::isVerified(old('shipping_phone', auth()->user()?->phone)) ? old('shipping_phone', auth()->user()?->phone) : '' }}">
    <div id="pv-idle" class="flex items-center gap-2">
        <button type="button" id="pv-send" class="px-3 py-1.5 rounded-lg bg-orange-600 text-white text-xs font-semibold hover:bg-orange-700 disabled:opacity-50">Send verification code</button>
        <span class="text-xs text-gray-400 dark:text-gray-500">We'll text a 6-digit code to confirm your number is active.</span>
    </div>
    <div id="pv-code" class="hidden flex items-center gap-2">
        <input type="text" id="pv-input" inputmode="numeric" maxlength="6" autocomplete="one-time-code" placeholder="6-digit code"
            class="w-32 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-1.5 text-sm dark:bg-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-orange-500/30">
        <button type="button" id="pv-verify" class="px-3 py-1.5 rounded-lg bg-gray-800 text-white text-xs font-semibold hover:bg-gray-900 disabled:opacity-50">Verify</button>
        <button type="button" id="pv-resend" class="text-xs text-orange-600 hover:underline">Resend</button>
    </div>
    <p id="pv-ok" class="hidden text-xs font-medium text-green-600">&#10003; Phone number verified</p>
    <p id="pv-msg" class="hidden text-xs mt-1.5 text-red-500"></p>
</div>
<script>
(function () {
    var box = document.getElementById('phone-verify');
    var phone = document.getElementById('shipping_phone');
    if (!box || !phone) return;
    var $ = function (id) { return document.getElementById(id); };
    var form = phone.closest('form');
    var token = form ? form.querySelector('input[name=_token]').value : '';
    var verified = box.dataset.verified || '';
    function digits(v) {
        v = (v || '').replace(/[০-৯]/g, function (c) { return '০১২৩৪৫৬৭৮৯'.indexOf(c); }).replace(/\D+/g, '');
        if (v.indexOf('00880') === 0) v = v.slice(2);
        if (v.indexOf('880') === 0 && v.length === 13) v = '0' + v.slice(3);
        return v;
    }
    function msg(t) { $('pv-msg').textContent = t || ''; $('pv-msg').classList.toggle('hidden', !t); }
    function render() {
        var ok = verified && digits(phone.value) === digits(verified);
        $('pv-ok').classList.toggle('hidden', !ok);
        $('pv-idle').classList.toggle('hidden', ok || !$('pv-code').classList.contains('hidden'));
        if (ok) $('pv-code').classList.add('hidden');
    }
    function post(url, data) {
        return fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token }, body: JSON.stringify(data) })
            .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, body: j }; }); });
    }
    function send() {
        msg('');
        $('pv-send').disabled = true;
        post('{{ route('checkout.phone.send') }}', { phone: phone.value }).then(function (r) {
            $('pv-send').disabled = false;
            if (!r.ok) return msg(r.body.message || 'Could not send the code.');
            if (r.body.verified || r.body.skipped) { verified = phone.value; return render(); }
            $('pv-idle').classList.add('hidden'); $('pv-code').classList.remove('hidden'); $('pv-input').focus();
        }).catch(function () { $('pv-send').disabled = false; msg('Network problem. Please try again.'); });
    }
    $('pv-send').addEventListener('click', send);
    $('pv-resend').addEventListener('click', send);
    $('pv-verify').addEventListener('click', function () {
        msg(''); $('pv-verify').disabled = true;
        post('{{ route('checkout.phone.verify') }}', { phone: phone.value, code: $('pv-input').value }).then(function (r) {
            $('pv-verify').disabled = false;
            if (!r.ok) return msg(r.body.message || 'Wrong code.');
            verified = phone.value; render();
        }).catch(function () { $('pv-verify').disabled = false; msg('Network problem. Please try again.'); });
    });
    phone.addEventListener('input', function () {
        msg(''); $('pv-code').classList.add('hidden'); $('pv-input').value = ''; render();
    });
    render();
})();
</script>
@endif
