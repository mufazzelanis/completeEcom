<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\Crm\CrmContacts;
use App\Services\Crm\CrmMetrics;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Keeps the CRM in step with orders. After-commit for the same reason as OrderAlertObserver:
 * checkout writes the order inside a transaction, and CRM bookkeeping must never be able to
 * fail (or roll back) a customer's purchase — hence the blanket catch.
 */
class CrmOrderObserver
{
    public bool $afterCommit = true;

    public function created(Order $order): void
    {
        $this->guard(function () use ($order) {
            $contacts = app(CrmContacts::class);
            $contact = $contacts->forOrder($order);
            if (! $contact) {
                return;
            }
            app(CrmMetrics::class)->refreshContact($contact);
            $contacts->logSystem($contact, 'Placed order ' . $order->order_number, '৳' . number_format((float) $order->total) . ' · ' . strtoupper((string) $order->payment_method), ['order_id' => $order->id]);
        });
    }

    public function updated(Order $order): void
    {
        if (! $order->crm_contact_id || ! ($order->wasChanged('status') || $order->wasChanged('total'))) {
            return;
        }

        $this->guard(function () use ($order) {
            $contact = $order->crmContact;
            if (! $contact) {
                return;
            }
            app(CrmMetrics::class)->refreshContact($contact);
            if ($order->wasChanged('status')) {
                app(CrmContacts::class)->logSystem($contact, 'Order ' . $order->order_number . ' → ' . $order->status, null, ['order_id' => $order->id]);
            }
        });
    }

    private function guard(callable $fn): void
    {
        try {
            $fn();
        } catch (Throwable $e) {
            Log::warning('CRM order sync failed: ' . $e->getMessage());
        }
    }
}
