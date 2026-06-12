<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationTemplate;

class NotificationSeederController extends Controller
{
    public function seed()
    {
        $templates = self::defaultTemplates();
        $count = 0;

        foreach ($templates as $t) {
            NotificationTemplate::firstOrCreate(
                ['event_type' => $t['event_type'], 'channel' => $t['channel'], 'recipient' => $t['recipient']],
                $t
            );
            $count++;
        }

        return redirect()->route('admin.notifications.templates')
            ->with('success', "Seeded {$count} default templates.");
    }

    public static function defaultTemplates(): array
    {
        return [
            // ── order_placed ─────────────────────────────────────────────
            ['event_type' => 'order_placed', 'channel' => 'email', 'recipient' => 'customer',
             'subject'    => 'Order Confirmed – {{order_number}}',
             'body'       => "<p>Hi {{customer}},</p><p>Thank you for your order! Your order <strong>{{order_number}}</strong> has been received and is being processed.</p><p>Order Total: <strong>{{total}}</strong></p><p><a href=\"{{url}}\">View Order</a></p>",
             'push_title' => null, 'is_active' => true],

            ['event_type' => 'order_placed', 'channel' => 'sms', 'recipient' => 'customer',
             'subject'    => null,
             'body'       => 'ShopVista: Your order {{order_number}} ({{total}}) has been placed. Track: {{url}}',
             'push_title' => null, 'is_active' => true],

            ['event_type' => 'order_placed', 'channel' => 'push', 'recipient' => 'customer',
             'subject'    => null, 'push_title' => 'Order Confirmed!',
             'body'       => 'Your order {{order_number}} has been received. Total: {{total}}',
             'is_active'  => true],

            ['event_type' => 'order_placed', 'channel' => 'whatsapp', 'recipient' => 'customer',
             'subject'    => null,
             'body'       => "Hi {{customer}}! 🎉\n\nYour order *{{order_number}}* has been confirmed.\nTotal: *{{total}}*\n\nTrack your order: {{url}}",
             'push_title' => null, 'is_active' => true],

            // ── order_status_changed ──────────────────────────────────────
            ['event_type' => 'order_status_changed', 'channel' => 'email', 'recipient' => 'customer',
             'subject'    => 'Order {{order_number}} is now {{new_status}}',
             'body'       => "<p>Hi {{customer}},</p><p>Your order <strong>{{order_number}}</strong> status has been updated from <em>{{old_status}}</em> to <strong>{{new_status}}</strong>.</p><p><a href=\"{{url}}\">View Order Details</a></p>",
             'push_title' => null, 'is_active' => true],

            ['event_type' => 'order_status_changed', 'channel' => 'sms', 'recipient' => 'customer',
             'subject'    => null,
             'body'       => 'ShopVista: Order {{order_number}} is now {{new_status}}. Track: {{url}}',
             'push_title' => null, 'is_active' => true],

            ['event_type' => 'order_status_changed', 'channel' => 'push', 'recipient' => 'customer',
             'subject'    => null, 'push_title' => 'Order Update',
             'body'       => 'Order {{order_number}} → {{new_status}}',
             'is_active'  => true],

            ['event_type' => 'order_status_changed', 'channel' => 'whatsapp', 'recipient' => 'customer',
             'subject'    => null,
             'body'       => "Hi {{customer}}!\n\nYour order *{{order_number}}* status: *{{old_status}}* → *{{new_status}}*\n\nDetails: {{url}}",
             'push_title' => null, 'is_active' => true],

            // ── return_status_changed ─────────────────────────────────────
            ['event_type' => 'return_status_changed', 'channel' => 'email', 'recipient' => 'customer',
             'subject'    => 'Return #{{return_number}} Update',
             'body'       => "<p>Hi {{customer}},</p><p>Your return request <strong>#{{return_number}}</strong> for order <strong>{{order_number}}</strong> is now <strong>{{status}}</strong>.</p>",
             'push_title' => null, 'is_active' => true],

            ['event_type' => 'return_status_changed', 'channel' => 'push', 'recipient' => 'customer',
             'subject'    => null, 'push_title' => 'Return Update',
             'body'       => 'Return #{{return_number}} is now {{status}}.',
             'is_active'  => true],

            // ── ticket_replied ────────────────────────────────────────────
            ['event_type' => 'ticket_replied', 'channel' => 'email', 'recipient' => 'customer',
             'subject'    => 'Reply to your ticket {{ticket_number}}',
             'body'       => "<p>Hi {{customer}},</p><p>Our support team has replied to your ticket <strong>{{ticket_number}}</strong>: <em>{{subject}}</em>.</p><p><a href=\"{{url}}\">View Reply</a></p>",
             'push_title' => null, 'is_active' => true],

            ['event_type' => 'ticket_replied', 'channel' => 'push', 'recipient' => 'customer',
             'subject'    => null, 'push_title' => 'Support Reply',
             'body'       => 'New reply on ticket {{ticket_number}}',
             'is_active'  => true],

            // ── new_order (admin) ─────────────────────────────────────────
            ['event_type' => 'new_order', 'channel' => 'email', 'recipient' => 'admin',
             'subject'    => 'New Order {{order_number}} – {{total}}',
             'body'       => "<p>New order received!</p><p>Order: <strong>{{order_number}}</strong><br>Customer: <strong>{{customer}}</strong><br>Total: <strong>{{total}}</strong></p>",
             'push_title' => null, 'is_active' => true],

            ['event_type' => 'new_order', 'channel' => 'push', 'recipient' => 'admin',
             'subject'    => null, 'push_title' => 'New Order!',
             'body'       => 'Order {{order_number}} from {{customer}} – {{total}}',
             'is_active'  => true],

            // ── low_stock (admin) ─────────────────────────────────────────
            ['event_type' => 'low_stock', 'channel' => 'email', 'recipient' => 'admin',
             'subject'    => 'Low Stock Alert: {{product_name}}',
             'body'       => "<p><strong>{{product_name}}</strong> (SKU: {{sku}}) is running low. Current stock: <strong>{{stock}}</strong> units.</p>",
             'push_title' => null, 'is_active' => true],

            // ── fraud_flagged (admin) ─────────────────────────────────────
            ['event_type' => 'fraud_flagged', 'channel' => 'email', 'recipient' => 'admin',
             'subject'    => 'Fraud Alert: Order {{order_number}}',
             'body'       => "<p>Order <strong>{{order_number}}</strong> has been flagged for fraud (score: {{score}}).</p><p>Flags: {{flags}}</p>",
             'push_title' => null, 'is_active' => true],

            // ── new_ticket (admin) ────────────────────────────────────────
            ['event_type' => 'new_ticket', 'channel' => 'email', 'recipient' => 'admin',
             'subject'    => 'New Support Ticket {{ticket_number}}',
             'body'       => "<p>New ticket from <strong>{{customer}}</strong>: <em>{{subject}}</em></p><p>Ticket: <strong>{{ticket_number}}</strong></p>",
             'push_title' => null, 'is_active' => true],

            // ── new_return (admin) ────────────────────────────────────────
            ['event_type' => 'new_return', 'channel' => 'email', 'recipient' => 'admin',
             'subject'    => 'New Return Request – Order {{order_number}}',
             'body'       => "<p>Return request from <strong>{{customer}}</strong> for order <strong>{{order_number}}</strong>.</p><p>Reason: {{reason}}</p>",
             'push_title' => null, 'is_active' => true],
        ];
    }
}
