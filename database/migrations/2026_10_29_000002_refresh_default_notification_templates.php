<?php

use App\Http\Controllers\Admin\NotificationSeederController;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Order emails now get their full layout (status headline, tracker, items, totals, address,
 * buttons) from OrderEmail, so the editable template is just the greeting + message. Templates
 * still holding the old seeded wording (a bare "View Order" link, the "ShopVista:" SMS prefix)
 * are moved to the new defaults; any template an admin has edited is left exactly as it is.
 */
return new class extends Migration
{
    private const OLD = [
        'order_placed|email|customer' => [
            'Order Confirmed – {{order_number}}',
            '<p>Hi {{customer}},</p><p>Thank you for your order! Your order <strong>{{order_number}}</strong> has been received and is being processed.</p><p>Order Total: <strong>{{total}}</strong></p><p><a href="{{url}}">View Order</a></p>',
        ],
        'order_status_changed|email|customer' => [
            'Order {{order_number}} is now {{new_status}}',
            '<p>Hi {{customer}},</p><p>Your order <strong>{{order_number}}</strong> status has been updated from <em>{{old_status}}</em> to <strong>{{new_status}}</strong>.</p><p><a href="{{url}}">View Order Details</a></p>',
        ],
        'new_order|email|admin' => [
            'New Order {{order_number}} – {{total}}',
            '<p>New order received!</p><p>Order: <strong>{{order_number}}</strong><br>Customer: <strong>{{customer}}</strong><br>Total: <strong>{{total}}</strong></p>',
        ],
    ];

    public function up(): void
    {
        $defaults = collect(NotificationSeederController::defaultTemplates())
            ->keyBy(fn ($t) => "{$t['event_type']}|{$t['channel']}|{$t['recipient']}");

        foreach (self::OLD as $key => [$oldSubject, $oldBody]) {
            [$event, $channel, $recipient] = explode('|', $key);
            $new = $defaults[$key];
            DB::table('notification_templates')
                ->where(compact('channel', 'recipient') + ['event_type' => $event])
                ->where('body', $oldBody)
                ->update(['subject' => $new['subject'], 'body' => $new['body'], 'updated_at' => now()]);
            // subject only, where the body was edited but the subject wasn't
            DB::table('notification_templates')
                ->where(compact('channel', 'recipient') + ['event_type' => $event])
                ->where('subject', $oldSubject)
                ->update(['subject' => $new['subject'], 'updated_at' => now()]);
        }

        // old brand name hard-coded at the start of the seeded SMS texts
        DB::table('notification_templates')->where('channel', 'sms')->where('body', 'like', 'ShopVista:%')
            ->update(['body' => DB::raw("CONCAT('{{site_name}}:', SUBSTRING(body, 11))"), 'updated_at' => now()]);
    }

    public function down(): void
    {
        // wording only — nothing to undo structurally
    }
};
