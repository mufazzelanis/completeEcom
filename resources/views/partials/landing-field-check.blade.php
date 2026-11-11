{{-- Landing page order form: name and full address checked as the customer types and again on
     submit, in Bangla, before anything is sent. Mirrors LandingPageController@order and
     App\Support\AddressCheck (the server still re-checks). Include it inside the <form>. --}}
<script>
(function (script) {
    var form = script.closest('form');
    if (!form || form.dataset.fieldCheck) return;
    form.dataset.fieldCheck = '1';
    var letters = function (s) { var m = s.match(/\p{L}/gu); return m ? m.length : 0; };
    var rules = {
        name: function (v) {
            if (!v) return 'আপনার নাম লিখুন।';
            if (letters(v) < 2) return 'আপনার নাম লিখুন (শুধু সংখ্যা বা চিহ্ন নয়)।';
            return '';
        },
        address: function (v) {
            if (!v) return 'ডেলিভারির জন্য সম্পূর্ণ ঠিকানা দিন।';
            var parts = v.split(/[\s,।\-\/]+/).filter(Boolean);
            var only = (v.toLowerCase().match(/\p{L}/gu) || []).filter(function (c, i, a) { return a.indexOf(c) === i; });
            if (v.length < 10 || parts.length < 2 || letters(v) < 4 || only.length <= 2) return @json(\App\Support\AddressCheck::MESSAGE);
            return '';
        },
    };
    Object.keys(rules).forEach(function (name) {
        var input = form.querySelector('[name="' + name + '"]');
        if (!input) return;
        var msg = document.createElement('p');
        msg.setAttribute('aria-live', 'polite');
        msg.style.cssText = 'margin:6px 0 0;font-size:.82rem;line-height:1.45;color:#e11d48;display:none';
        var wrap = input.parentNode.classList.contains('relative') || input.parentNode.classList.contains('lt-input-wrap') ? input.parentNode : input;
        wrap.insertAdjacentElement('afterend', msg);
        var touched = false;
        var run = function (show) {
            var problem = rules[name](input.value.trim().replace(/\s+/g, ' '));
            input.setCustomValidity(problem);
            msg.textContent = problem;
            msg.style.display = problem && (show || touched) ? 'block' : 'none';
            input.style.borderColor = problem && (show || touched) ? '#f43f5e' : '';
            return problem;
        };
        input.addEventListener('input', function () { run(false); });
        input.addEventListener('blur', function () { if (input.value.trim()) { touched = true; run(true); } });
        input.addEventListener('invalid', function () { touched = true; run(true); });
        run(false);
    });
    // the browser's own bubble would show the first problem only; ours already sits under each field
    form.addEventListener('submit', function (e) {
        var bad = Object.keys(rules).map(function (n) { var i = form.querySelector('[name="' + n + '"]'); return i && i.validationMessage ? i : null; }).filter(Boolean);
        if (bad.length) {
            e.preventDefault(); e.stopImmediatePropagation();
            bad.forEach(function (i) { i.dispatchEvent(new Event('invalid')); });
            bad[0].focus(); bad[0].scrollIntoView({ block: 'center', behavior: 'smooth' });
        }
    }, true);
})(document.currentScript);
</script>
