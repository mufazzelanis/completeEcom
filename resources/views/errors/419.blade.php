{{-- Page Expired (CSRF token mismatch) — happens when a form sits open long enough for its
     session to expire before submitting (e.g. someone filling out checkout slowly, or a tab
     left open overnight). Same "never touch the database" rule as errors/500.blade.php —
     an expired session is exactly the kind of state a DB-backed settings lookup might also
     be affected by. Encourages going back rather than starting over, since most browsers keep
     what was typed in the previous page's form fields. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Please try again | Mitavin</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px;
               font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Hind Siliguri", Arial, sans-serif;
               background: #f7f8fc; color: #0f172a; }
        .card { max-width: 440px; width: 100%; text-align: center; }
        .icon { width: 64px; height: 64px; margin: 0 auto 20px; border-radius: 50%; background: #eff6ff;
                display: flex; align-items: center; justify-content: center; }
        h1 { font-size: 20px; margin: 0 0 10px; }
        p { color: #64748b; font-size: 15px; line-height: 1.6; margin: 0 0 24px; }
        .actions { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }
        a.btn { display: inline-block; padding: 11px 22px; border-radius: 10px; font-weight: 700; font-size: 14px;
                text-decoration: none; }
        a.btn.primary { background: #ea580c; color: #fff; }
        a.btn.ghost { background: #fff; color: #0f172a; border: 1px solid #e2e8f0; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">
            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l2 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <h1>পেজটা অনেকক্ষণ খোলা ছিল</h1>
        <p>নিরাপত্তার জন্য এই পেজের সেশন শেষ হয়ে গেছে। পিছনে গিয়ে আবার "অর্ডার করুন" চাপুন — আপনার লেখা তথ্য সাধারণত থেকে যায়, আবার টাইপ করতে হবে না।</p>
        <div class="actions">
            <a href="#" class="btn ghost" onclick="history.back(); return false;">পিছনে যান</a>
            <a href="/" class="btn primary">হোমপেজে যান</a>
        </div>
    </div>
</body>
</html>
