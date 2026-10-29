<?php

namespace App\Http\Controllers;

use App\Models\NotificationTemplate;
use App\Models\Order;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Notifications\OrderEmail;
use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * POST /email-test — sends sample order emails to an address you choose, to check how they
 * look in a real inbox (Postman / curl friendly, JSON in and out). Off unless EMAIL_TEST_TOKEN
 * is set in .env, and every call must send that token (X-Test-Token header or "token" field).
 *
 * Uses a real order only as sample data: its status is changed in memory, never saved, and no
 * customer notification, in-app message or log entry is created. Subjects start with [TEST].
 */
class EmailPreviewController extends Controller
{
    /** type => [event, recipient, status the sample order shows, extra context] */
    private const TYPES = [
        'placed'                => ['order_placed', 'customer', 'pending', []],
        'processing'            => ['order_status_changed', 'customer', 'processing', []],
        'shipped'               => ['order_status_changed', 'customer', 'shipped', []],
        'delivered'             => ['order_status_changed', 'customer', 'delivered', []],
        'cancelled'             => ['order_status_changed', 'customer', 'cancelled', []],
        'cancelled_by_customer' => ['order_status_changed', 'customer', 'cancelled', ['by_customer' => true]],
        'refunded'              => ['order_status_changed', 'customer', 'refunded', []],
        'admin_new_order'       => ['new_order', 'admin', 'pending', []],
    ];

    public function send(Request $request)
    {
        $token = (string) config('services.email_test.token');
        $given = (string) ($request->header('X-Test-Token') ?: $request->input('token'));
        if ($token === '') {
            return response()->json(['message' => 'Email testing is switched off. Set EMAIL_TEST_TOKEN in .env to turn it on.'], 404);
        }
        if (!hash_equals($token, $given)) {
            return response()->json(['message' => 'Invalid or missing test token.'], 401);
        }

        // always JSON back, even when the client doesn't send Accept: application/json
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email:rfc', 'max:255'],
            'type' => ['nullable', Rule::in(array_merge(['all'], array_keys(self::TYPES)))],
            'order' => ['nullable', 'string', 'max:50'],
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Invalid request.', 'errors' => $validator->errors(), 'types' => array_merge(['all'], array_keys(self::TYPES))], 422);
        }
        $data = $validator->validated();

        $order = Order::with('items.product', 'user')
            ->when($data['order'] ?? null, fn ($q, $number) => $q->where('order_number', $number))
            ->whereHas('items')
            ->latest('id')
            ->first();
        if (!$order) {
            return response()->json(['message' => isset($data['order']) ? "Order {$data['order']} not found." : 'No order with items to use as sample data yet.'], 422);
        }

        $types = ($data['type'] ?? 'all') === 'all' ? array_keys(self::TYPES) : [$data['type']];
        $realStatus = $order->status;
        $results = [];

        foreach ($types as $type) {
            [$event, $recipient, $status, $context] = self::TYPES[$type];
            $order->status = $status; // in memory only — never saved

            $vars = NotificationDispatcher::templateVars($event, $recipient === 'admin' ? null : $order->user, [
                'old_status' => 'Pending',
                'customer' => $order->shipping_name,
                '_order' => $order,
                '_context' => $context,
            ]);
            $template = NotificationTemplate::render($event, 'email', $recipient, $vars);
            if (!$template) {
                $results[] = ['type' => $type, 'sent' => false, 'error' => "No active email template for {$event} ({$recipient})."];
                continue;
            }

            $subject = '[TEST] ' . ($template['subject'] ?? $type);
            try {
                $html = wrap_branded_email(
                    OrderEmail::body($event, $recipient, $template['body'], $order),
                    null,
                    OrderEmail::preheader($event, $order)
                );
                Mail::send([], [], fn (Message $m) => $m->to($data['email'])->subject($subject)->html($html));
                $results[] = ['type' => $type, 'sent' => true, 'subject' => $subject];
            } catch (\Throwable $e) {
                $results[] = ['type' => $type, 'sent' => false, 'error' => $e->getMessage()];
            }
        }
        $order->status = $realStatus;

        $sent = collect($results)->where('sent', true)->count();

        return response()->json([
            'message' => "{$sent} of " . count($results) . " test email(s) sent to {$data['email']}.",
            'sample_order' => $order->order_number,
            'mailer' => config('mail.default'),
            'results' => $results,
        ], $sent ? 200 : 500);
    }
}
