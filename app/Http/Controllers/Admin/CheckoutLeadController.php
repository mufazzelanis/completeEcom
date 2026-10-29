<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CheckoutLead;
use App\Models\NotificationLog;
use App\Models\Setting;
use App\Services\AuditLogger;
use App\Services\Notifications\Channels\SmsChannel;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Admin → Sales → Incomplete Checkouts: shoppers who entered a phone number at checkout but
 * didn't order, with their cart — call, WhatsApp or SMS them, note what happened. Optional
 * automatic SMS a set number of minutes later (off by default; command checkout-leads:remind).
 */
class CheckoutLeadController extends Controller
{
    public const DEFAULT_SMS = 'Hi {name}, you left {items} in your cart at {site}. Complete your order here: {link} — or call {phone}.';

    public function index(Request $request)
    {
        $query = CheckoutLead::query()->latest('last_activity_at');

        $status = $request->input('status', 'open');
        if ($status === 'open') {
            $query->whereIn('status', ['open', 'contacted']);
        } elseif (isset(CheckoutLead::STATUSES[$status])) {
            $query->where('status', $status);
        }
        if ($search = trim((string) $request->input('search'))) {
            $like = '%' . addcslashes($search, '%_\\') . '%';
            $query->where(fn ($q) => $q->where('phone', 'like', $like)->orWhere('name', 'like', $like));
        }

        $leads = $query->paginate(25)->withQueryString();

        $monthStart = now()->startOfMonth();
        $stats = [
            'open' => CheckoutLead::whereIn('status', ['open', 'contacted'])->count(),
            'openValue' => (float) CheckoutLead::whereIn('status', ['open', 'contacted'])->sum('subtotal'),
            'recovered' => CheckoutLead::where('status', 'recovered')->where('updated_at', '>=', $monthStart)->count(),
            'recoveredValue' => (float) CheckoutLead::where('checkout_leads.status', 'recovered')->where('checkout_leads.updated_at', '>=', $monthStart)
                ->join('orders', 'orders.id', '=', 'checkout_leads.order_id')->sum('orders.total'),
            'total' => CheckoutLead::where('created_at', '>=', $monthStart)->count(),
        ];
        $stats['rate'] = $stats['total'] ? round($stats['recovered'] / $stats['total'] * 100) : 0;

        return view('admin.checkout-leads.index', [
            'leads' => $leads,
            'stats' => $stats,
            'status' => $status,
            'smsReady' => self::smsReady(),
            'smsTemplate' => setting('checkout_lead_sms_text') ?: self::DEFAULT_SMS,
            'autoSms' => setting('checkout_lead_auto_sms', '0') === '1',
            'autoSmsMinutes' => (int) setting('checkout_lead_auto_sms_minutes', 30),
        ]);
    }

    public function update(Request $request, CheckoutLead $lead)
    {
        $data = $request->validate([
            'status' => ['nullable', Rule::in(array_keys(CheckoutLead::STATUSES))],
            'note' => ['nullable', 'string', 'max:500'],
        ]);
        if (isset($data['status'])) {
            $lead->status = $data['status'];
            if ($data['status'] === 'contacted') {
                $lead->contacted_at = now();
            }
        }
        if ($request->has('note')) {
            $lead->note = $data['note'];
        }
        $lead->save();

        return $request->expectsJson()
            ? response()->json(['ok' => true, 'status' => $lead->status, 'label' => $lead->statusMeta()[0], 'color' => $lead->statusMeta()[1]])
            : back()->with('success', 'Updated.');
    }

    public function sms(CheckoutLead $lead, SmsChannel $sms)
    {
        if (!self::smsReady()) {
            return back()->with('error', 'No SMS gateway is set up yet.');
        }
        $text = self::smsText($lead);
        $sms->send($lead->user_id, 'checkout_lead', $lead->phone, $text);
        $log = NotificationLog::where('event_type', 'checkout_lead')->where('recipient', $lead->phone)->latest('id')->first();
        $ok = $log && $log->status === 'sent';
        if ($ok) {
            $lead->forceFill(['sms_sent_at' => now(), 'status' => $lead->status === 'open' ? 'contacted' : $lead->status, 'contacted_at' => $lead->contacted_at ?? now()])->save();
            AuditLogger::log('checkout_lead.sms', "Reminder SMS sent to {$lead->phone}", $lead);
        }

        return back()->with($ok ? 'success' : 'error', $ok ? "SMS sent to {$lead->phone}." : 'SMS could not be sent: ' . ($log?->error ?: 'unknown error'));
    }

    public function settings(Request $request)
    {
        $data = $request->validate([
            'auto_sms' => ['nullable', 'boolean'],
            'minutes' => ['required', 'integer', 'min:15', 'max:1440'],
            'text' => ['required', 'string', 'max:300'],
        ]);
        Setting::setMany([
            'checkout_lead_auto_sms' => $request->boolean('auto_sms') ? '1' : '0',
            'checkout_lead_auto_sms_minutes' => (string) $data['minutes'],
            'checkout_lead_sms_text' => $data['text'],
        ], 'orders');

        return back()->with('success', 'Reminder settings saved.');
    }

    /** An SMS gateway is really configured — not just the config default driver name. */
    public static function smsReady(): bool
    {
        return match (config('notifications.sms.driver')) {
            'twilio' => filled(config('notifications.sms.sid')) && filled(config('notifications.sms.token')) && filled(config('notifications.sms.from')),
            'http' => filled(config('notifications.sms.http_url')),
            default => false,
        };
    }

    /** The reminder text with {name} {items} {site} {link} {phone} filled in. */
    public static function smsText(CheckoutLead $lead): string
    {
        $items = collect($lead->items);
        $first = $items->first()['name'] ?? 'your items';
        $itemsText = \Illuminate\Support\Str::limit($first, 40) . ($items->count() > 1 ? ' +' . ($items->count() - 1) . ' more' : '');
        $productUrl = !empty($items->first()['slug']) ? route('products.show', $items->first()['slug']) : url('/');

        return strtr(setting('checkout_lead_sms_text') ?: self::DEFAULT_SMS, [
            '{name}' => trim(strtok((string) $lead->name, ' ')) ?: 'there',
            '{items}' => $itemsText,
            '{site}' => setting('site_name', config('app.name')),
            '{link}' => $productUrl,
            '{phone}' => setting('company_phone') ?: '',
        ]);
    }
}
