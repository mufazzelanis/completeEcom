{{-- Service unavailable (maintenance mode / server overloaded) — same "never touch the
     database or a cached setting" rule as errors/500.blade.php, for the same reason. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>We'll be right back | Mitavin</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px;
               font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Hind Siliguri", Arial, sans-serif;
               background: #f7f8fc; color: #0f172a; }
        .card { max-width: 440px; width: 100%; text-align: center; }
        .icon { width: 64px; height: 64px; margin: 0 auto 20px; border-radius: 50%; background: #fff7ed;
                display: flex; align-items: center; justify-content: center; }
        h1 { font-size: 20px; margin: 0 0 10px; }
        p { color: #64748b; font-size: 15px; line-height: 1.6; margin: 0 0 24px; }
        a.btn { display: inline-block; padding: 11px 22px; border-radius: 10px; font-weight: 700; font-size: 14px;
                text-decoration: none; background: #ea580c; color: #fff; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">
            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <h1>আমরা একটু কাজ করছি</h1>
        <p>সাইটটা সাময়িকভাবে বন্ধ আছে — কিছুক্ষণের মধ্যেই আবার চালু হয়ে যাবে। একটু পরে আবার চেষ্টা করুন।</p>
        <a href="#" class="btn" onclick="location.reload(); return false;">আবার চেষ্টা করুন</a>
    </div>
</body>
</html>
