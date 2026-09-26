<?php

return [

    /*
    | How often (seconds) an open admin page asks the server whether anything new arrived.
    | Every admin page polls — deliberately plain HTTP rather than websockets, since this app
    | runs on shared cPanel hosting where a long-lived socket server isn't available. Phone
    | alerts (Web Push) don't depend on this at all; they arrive even with the app closed.
    */
    'poll_seconds' => (int) env('ADMIN_ALERTS_POLL_SECONDS', 12),

    // Alerts older than this are pruned opportunistically as new ones are created.
    'keep_days' => 60,

    /*
    | VAPID identity for Web Push. Generate the key pair once on the server with
    | `php artisan admin-alerts:setup` (writes these into .env) — never commit them.
    | The subject must be a mailto: or https: URL; push services (Apple's especially)
    | reject anything else, so a plain http://localhost value simply disables phone push.
    */
    'vapid' => [
        'subject'     => env('VAPID_SUBJECT'),
        'public_key'  => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
    ],

];
