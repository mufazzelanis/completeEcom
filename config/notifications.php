<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Notification Channels
    |--------------------------------------------------------------------------
    */

    'channels' => ['email', 'sms', 'push', 'whatsapp'],

    /*
    |--------------------------------------------------------------------------
    | SMS via Twilio
    |--------------------------------------------------------------------------
    */

    'sms' => [
        'driver'   => env('SMS_DRIVER', 'twilio'), // twilio | log
        'sid'      => env('TWILIO_SID'),
        'token'    => env('TWILIO_TOKEN'),
        'from'     => env('TWILIO_FROM'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Push via FCM (Firebase Cloud Messaging)
    |--------------------------------------------------------------------------
    */

    'push' => [
        'driver'     => env('PUSH_DRIVER', 'fcm'), // fcm | log
        'fcm_url'    => 'https://fcm.googleapis.com/fcm/send',
        'server_key' => env('FCM_SERVER_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp via Twilio or Meta Cloud API
    |--------------------------------------------------------------------------
    */

    'whatsapp' => [
        'driver'         => env('WHATSAPP_DRIVER', 'twilio'), // twilio | meta | log
        // Twilio
        'twilio_from'    => env('TWILIO_WHATSAPP_FROM', 'whatsapp:+14155238886'),
        // Meta Cloud API
        'meta_token'     => env('META_WHATSAPP_TOKEN'),
        'meta_phone_id'  => env('META_WHATSAPP_PHONE_ID'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Notification Recipients
    |--------------------------------------------------------------------------
    */

    'admin_email' => env('ADMIN_NOTIFICATION_EMAIL', env('MAIL_FROM_ADDRESS')),
    'admin_phone' => env('ADMIN_NOTIFICATION_PHONE'),

    /*
    |--------------------------------------------------------------------------
    | Event Types + Default Recipient + Enabled Channels
    |--------------------------------------------------------------------------
    */

    'events' => [
        // Customer events
        'order_placed'          => ['recipient' => 'customer', 'channels' => ['email', 'push']],
        'order_status_changed'  => ['recipient' => 'customer', 'channels' => ['email', 'sms', 'push', 'whatsapp']],
        'return_status_changed' => ['recipient' => 'customer', 'channels' => ['email', 'push']],
        'ticket_replied'        => ['recipient' => 'customer', 'channels' => ['email', 'push']],
        // Admin events
        'new_order'             => ['recipient' => 'admin', 'channels' => ['email', 'push']],
        'low_stock'             => ['recipient' => 'admin', 'channels' => ['email', 'push']],
        'fraud_flagged'         => ['recipient' => 'admin', 'channels' => ['email']],
        'new_ticket'            => ['recipient' => 'admin', 'channels' => ['email', 'push']],
        'ticket_reply_received' => ['recipient' => 'admin', 'channels' => ['email', 'push']],
        'new_return'            => ['recipient' => 'admin', 'channels' => ['email']],
    ],

];
