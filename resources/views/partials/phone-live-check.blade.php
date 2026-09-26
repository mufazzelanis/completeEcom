{{-- Live Bangladeshi-mobile check while the customer types. Mirrors App\Support\PhoneValidator (the server still re-checks).
     Usage: @include('partials.phone-live-check', ['selector' => '#shipping_phone', 'bn' => false]) --}}
@if(\App\Support\PhoneValidator::mode() !== 'off')
<script>
(function () {
    var input = document.querySelector(@json($selector));
    if (!input) return;
    var bn = @json((bool) ($bn ?? false));
    var T = bn ? {
        start: 'নম্বর 01 দিয়ে শুরু হবে (যেমন 01712345678)',
        op: 'এটি সঠিক মোবাইল অপারেটরের নম্বর নয় (013–019)',
        long: 'নম্বরটি ১১ ডিজিটের বেশি হয়েছে',
        fake: 'নম্বরটি ভুয়া মনে হচ্ছে — আপনার আসল চালু মোবাইল নম্বর দিন',
        more: function (n) { return 'আরও ' + n + ' ডিজিট লাগবে'; },
        ok: 'নম্বরটি ঠিক আছে', bad: 'সঠিক ১১ ডিজিটের মোবাইল নম্বর দিন'
    } : {
        start: 'Number must start with 01 (e.g. 01712345678)',
        op: 'Not a valid mobile operator prefix (013–019)',
        long: 'That is more than 11 digits',
        fake: 'This number looks fake — please enter your real, working mobile number',
        more: function (n) { return n + ' more digit' + (n > 1 ? 's' : '') + ' needed'; },
        ok: 'Number looks good', bad: 'Enter a valid 11-digit mobile number'
    };

    function digits(v) {
        v = (v || '').replace(/[০-৯]/g, function (c) { return '০১২৩৪৫৬৭৮৯'.indexOf(c); }).replace(/\D+/g, '');
        if (v.indexOf('00880') === 0) v = v.slice(2);
        if (v.indexOf('880') === 0) v = '0' + v.slice(3);
        return v;
    }
    function looksFake(local) {
        var t = local.slice(3);
        if (/(\d)\1{5,}/.test(t)) return true;
        var uniq = {}; t.split('').forEach(function (c) { uniq[c] = 1; });
        if (Object.keys(uniq).length <= 2) return true;
        for (var i = 0; i <= t.length - 6; i++) {
            var up = true, down = true;
            for (var j = 1; j < 6; j++) {
                var d = +t[i + j] - +t[i + j - 1];
                up = up && d === 1; down = down && d === -1;
            }
            if (up || down) return true;
        }
        return false;
    }
    // returns {state: 'empty'|'typing'|'bad'|'ok', text}
    function check(raw) {
        var v = (raw || '').replace(/[০-৯]/g, function (c) { return '০১২৩৪৫৬৭৮৯'.indexOf(c); }).replace(/\D+/g, '');
        if (v === '') return { state: 'empty' };
        if (/^(8|88|0|00|008|0088)$/.test(v)) return { state: 'typing' };      // still typing a country prefix
        var d = digits(raw);
        if (d.length >= 2 && d.slice(0, 2) !== '01') return { state: 'bad', text: T.start };
        if (d.length >= 3 && !/^01[3-9]/.test(d)) return { state: 'bad', text: T.op };
        if (d.length > 11) return { state: 'bad', text: T.long };
        if (d.length < 11) return { state: 'typing', text: d.length >= 3 ? T.more(11 - d.length) : '' };
        if (looksFake(d)) return { state: 'bad', text: T.fake };
        return { state: 'ok', text: T.ok };
    }

    var msg = document.createElement('p');
    msg.className = 'text-xs mt-1.5 hidden';
    msg.setAttribute('aria-live', 'polite');
    var anchor = input.parentNode.classList.contains('relative') ? input.parentNode : input;
    anchor.insertAdjacentElement('afterend', msg);

    function render() {
        var r = check(input.value);
        msg.textContent = r.text || '';
        msg.className = 'text-xs mt-1.5 ' + (!r.text ? 'hidden' : r.state === 'bad' ? 'text-red-500' : r.state === 'ok' ? 'text-green-600' : 'text-gray-400');
        input.classList.toggle('!border-red-400', r.state === 'bad');
        input.classList.toggle('!border-green-500', r.state === 'ok');
        return r;
    }
    input.addEventListener('input', render);
    input.addEventListener('blur', function () {
        var r = check(input.value);
        if (r.state === 'typing' && input.value.trim() !== '') {
            msg.textContent = T.bad; msg.className = 'text-xs mt-1.5 text-red-500';
        }
    });
    if (input.value) render();

    // Stop the submit (and the "placing order" spinner) if the number is clearly not valid.
    var form = input.closest('form');
    if (form) form.addEventListener('submit', function (e) {
        if (input.value.trim() === '' || input.disabled) return;   // blank: leave to the field's own required rule
        if (check(input.value).state !== 'ok') {
            e.preventDefault(); e.stopImmediatePropagation();
            msg.textContent = check(input.value).text || T.bad; msg.className = 'text-xs mt-1.5 text-red-500';
            input.focus(); input.scrollIntoView({ block: 'center', behavior: 'smooth' });
        }
    }, true);
})();
</script>
@endif
