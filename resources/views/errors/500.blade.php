{{--
    Server error (500) and 503 (maintenance/overloaded) share this view. Deliberately minimal
    and self-contained — this fires exactly when something has already gone wrong (a DB outage,
    a crash), so unlike errors/404.blade.php it must NEVER query the database, call a cached
    setting, or load an external font: any of those could be the very thing that's broken, and
    a page that crashes while rendering "something went wrong" is the worst possible outcome.
    Every value below is a hardcoded literal, nothing dynamic.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Something went wrong | Mitavin</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px;
               font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Hind Siliguri", Arial, sans-serif;
               background: #f7f8fc; color: #0f172a; }
        .card { max-width: 440px; width: 100%; text-align: center; }
        .icon { width: 64px; height: 64px; margin: 0 auto 20px; border-radius: 50%; background: #fff1f0;
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
            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
        </div>
        <h1>কিছু একটা সমস্যা হয়েছে</h1>
        <p>দুঃখিত, এই মুহূর্তে পেজটা লোড করা যায়নি। চিন্তা করবেন না — আপনার কোনো তথ্য হারায়নি এবং কোনো টাকা কাটা হয়নি। একটু পরে আবার চেষ্টা করুন।</p>
        <div class="actions">
            <a href="#" class="btn ghost" onclick="location.reload(); return false;">আবার চেষ্টা করুন</a>
            <a href="/" class="btn primary">হোমপেজে যান</a>
        </div>
    </div>
</body>
</html>
