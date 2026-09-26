<?php

namespace App\Services\Crm;

use App\Models\Crm\CrmContact;
use App\Models\Crm\CrmTask;
use App\Models\NewsletterSubscriber;
use App\Models\Order;
use App\Models\User;

/** Backfill + housekeeping: makes sure every customer, order and subscriber has a contact. */
class CrmSync
{
    public function __construct(private CrmContacts $contacts, private CrmMetrics $metrics)
    {
    }

    /** @return array{orders:int,users:int,subscribers:int,refreshed:int} */
    public function run(): array
    {
        $stats = ['orders' => 0, 'users' => 0, 'subscribers' => 0, 'refreshed' => 0];

        // Oldest first so the earliest order names the contact (later orders only fill gaps).
        Order::whereNull('crm_contact_id')->orderBy('id')->with('user')->chunkById(300, function ($orders) use (&$stats) {
            foreach ($orders as $order) {
                if ($this->contacts->forOrder($order)) {
                    $stats['orders']++;
                }
            }
        });

        User::where('role', 'customer')->orderBy('id')->chunkById(300, function ($users) use (&$stats) {
            foreach ($users as $user) {
                $had = CrmContact::where('user_id', $user->id)->exists();
                if ($this->contacts->forUser($user) && ! $had) {
                    $stats['users']++;
                }
            }
        });

        NewsletterSubscriber::where('is_active', true)->orderBy('id')->chunkById(500, function ($subs) use (&$stats) {
            foreach ($subs as $sub) {
                $email = CrmContacts::normalizeEmail($sub->email);
                if (! $email) {
                    continue;
                }
                $existing = CrmContact::where('email', $email)->exists();
                $contact = $this->contacts->forEmail($email, 'newsletter');
                if ($contact) {
                    $this->contacts->tag($contact, 'Newsletter', 'indigo');
                    if (! $existing) {
                        $stats['subscribers']++;
                    }
                }
            }
        });

        $stats['refreshed'] = $this->metrics->refreshAll();

        return $stats;
    }

    /**
     * Auto follow-up tasks: high-value or churn-risk customers who have gone quiet get exactly
     * ONE open reminder to reach out — never a pile of duplicates on every nightly run.
     */
    public function createWinBackTasks(int $limit = 25): int
    {
        if (! (bool) \App\Models\Setting::get('crm_auto_tasks', 1)) {
            return 0;
        }

        $created = 0;
        $contacts = CrmContact::query()
            ->where('status', 'active')
            ->whereIn('lifecycle_stage', ['at_risk'])
            ->where('orders_count', '>=', 2)
            ->whereDoesntHave('tasks', fn ($t) => $t->where('is_auto', true)->where('status', 'open'))
            ->orderByDesc('total_spent')
            ->limit($limit)
            ->get();

        foreach ($contacts as $c) {
            CrmTask::create([
                'contact_id' => $c->id,
                'assigned_to' => $c->owner_id,
                'title' => 'Win-back: ' . $c->name . ' has gone quiet',
                'description' => 'Repeat customer (৳' . number_format((float) $c->total_spent) . ' lifetime) with no order for ' . ($c->last_order_at ? $c->last_order_at->diffInDays(now()) : '?') . ' days. Reach out with a personal message or offer.',
                'type' => 'call',
                'priority' => $c->total_spent >= 10000 ? 'high' : 'normal',
                'due_at' => now()->addDay()->setTime(11, 0),
                'is_auto' => true,
            ]);
            $created++;
        }

        return $created;
    }
}
