<?php

namespace App\Console\Commands;

use App\Http\Controllers\Admin\CheckoutLeadController;
use App\Models\CheckoutLead;
use App\Models\NotificationLog;
use App\Models\Order;
use App\Services\Notifications\Channels\SmsChannel;
use Illuminate\Console\Command;

/**
 * Automatic reminder SMS for incomplete checkouts (Admin → Incomplete Checkouts → Reminder
 * settings). Only when switched on there AND an SMS gateway is configured. One SMS per lead,
 * at most one per phone number a day, never to a number that has ordered since, and never for
 * a checkout older than a day (a reminder about last week's cart is spam).
 */
class RemindCheckoutLeads extends Command
{
    protected $signature = 'checkout-leads:remind {--dry-run : List who would get an SMS, send nothing}';

    protected $description = 'Send the reminder SMS to incomplete checkouts (when enabled)';

    public function handle(SmsChannel $sms): int
    {
        if (setting('checkout_lead_auto_sms', '0') !== '1') {
            $this->info('Automatic reminders are off.');

            return self::SUCCESS;
        }
        if (!CheckoutLeadController::smsReady()) {
            $this->warn('No SMS gateway configured (SMS_DRIVER) — nothing sent.');

            return self::SUCCESS;
        }

        $minutes = max(15, (int) setting('checkout_lead_auto_sms_minutes', 30));
        $leads = CheckoutLead::where('status', 'open')
            ->whereNull('sms_sent_at')
            ->whereBetween('last_activity_at', [now()->subDay(), now()->subMinutes($minutes)])
            ->orderBy('last_activity_at')
            ->limit(50)
            ->get();

        $sent = 0;
        foreach ($leads as $lead) {
            $orderedSince = Order::where('shipping_phone', $lead->phone)->where('created_at', '>=', $lead->created_at)->exists();
            $textedToday = CheckoutLead::where('phone', $lead->phone)->where('sms_sent_at', '>=', now()->subDay())->exists();
            if ($orderedSince || $textedToday) {
                if ($orderedSince) {
                    $lead->update(['status' => 'recovered']);
                }
                continue;
            }

            $text = CheckoutLeadController::smsText($lead);
            if ($this->option('dry-run')) {
                $this->line("{$lead->phone}: {$text}");
                continue;
            }
            $sms->send($lead->user_id, 'checkout_lead', $lead->phone, $text);
            $ok = NotificationLog::where('event_type', 'checkout_lead')->where('recipient', $lead->phone)->latest('id')->value('status') === 'sent';
            $lead->forceFill(['sms_sent_at' => now()])->save(); // once only, even if the gateway failed
            $sent += $ok ? 1 : 0;
        }

        $this->info("{$sent} reminder SMS sent.");

        return self::SUCCESS;
    }
}
