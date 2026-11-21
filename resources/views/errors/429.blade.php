{{-- Too Many Requests — fires when a visitor (or, more often on this site, several
     different customers sharing one mobile carrier's IP) hits a throttled route too many
     times in a short window. Same "never touch the database" rule as errors/500.blade.php. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>One moment please | Mitavin</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px;
               font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Hind Siliguri", Arial, sans-serif;
               background: #f7f8fc; color: #0f172a; }
        .card { max-width: 440px; width: 100%; text-align: center; }
        .icon { width: 64px; height: 64px; margin: 0 auto 20px; border-radius: 50%; background: #fefce8;
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
            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#ca8a04" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2"/><circle cx="12" cy="12" r="9" stroke="#ca8a04" stroke-width="2" fill="none"/></svg>
        </div>
        <h1>একটু অপেক্ষা করুন</h1>
        <p>খুব অল্প সময়ের মধ্যে অনেকবার চেষ্টা হয়েছে। আপনার তথ্য ঠিকই আছে — ৩০-৬০ সেকেন্ড পর আবার চেষ্টা করুন।</p>
        <a href="#" class="btn" onclick="location.reload(); return false;">আবার চেষ্টা করুন</a>
    </div>
</body>
</html>
