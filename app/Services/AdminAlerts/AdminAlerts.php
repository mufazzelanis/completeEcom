<?php

namespace App\Services\AdminAlerts;

use App\Models\AdminAlert;
use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

use function Illuminate\Support\defer;

/**
 * The single entry point for "tell the admins about X": saves one in-app alert per
 * eligible admin-panel user (drives the header bell, toast and sound) and, after the HTTP
 * response has already gone out, pushes it to their phones via Web Push.
 *
 * Extending it to a new event (a new review, a low-stock warning...) is one new entry in
 * TYPES plus one AdminAlerts::notify() call at the place it happens.
 */
class AdminAlerts
{
    /**
     * type => who should receive it. 'permission' uses the app's existing permission
     * system (admins always pass); 'roles' is for events that have no dedicated permission
     * yet (the newsletter list has none) and limits them to the owner/manager tier so a
     * junior staff account isn't spammed with things outside its remit.
     */
    public const TYPES = [
        'order'      => ['permission' => 'orders.view', 'roles' => null,                    'urgency' => 'high'],
        'subscriber' => ['permission' => null,          'roles' => ['admin', 'manager'],    'urgency' => 'normal'],
        'crm_task'   => ['permission' => 'crm.view',    'roles' => null,                    'urgency' => 'normal'],
        'crm_lead'   => ['permission' => 'crm.view',    'roles' => null,                    'urgency' => 'normal'],
        'test'       => ['permission' => null,          'roles' => null,                    'urgency' => 'high'],
    ];

    /**
     * Never throws — callers are checkout and a public signup form, and neither may ever
     * fail or slow down because an internal notification couldn't be delivered.
     *
     * @param  ?Collection<int, User>  $onlyTo  restrict recipients (used by the "send test" button)
     */
    public static function notify(string $type, string $title, ?string $body = null, ?string $url = null, array $data = [], ?Collection $onlyTo = null): void
    {
        try {
            $recipients = $onlyTo ?? static::recipientsFor($type);

            if ($recipients->isEmpty()) {
                return;
            }

            $now = now();
            $alerts = $recipients->map(fn (User $user) => AdminAlert::create([
                'user_id' => $user->id,
                'type'    => $type,
                'title'   => mb_substr($title, 0, 255),
                'body'    => $body !== null ? mb_substr($body, 0, 500) : null,
                'url'     => $url,
                'data'    => $data ?: null,
            ]));

            if (random_int(1, 50) === 1) {
                AdminAlert::where('created_at', '<', $now->copy()->subDays((int) config('admin_alerts.keep_days', 60)))->delete();
            }

            // Runs after the response is sent (immediately in CLI contexts): a slow push
            // service must never add latency to the customer's "Place Order" click.
            // always:true so an order that committed but hit an error later in the same
            // request still buzzes the phone — by then the alert is already saved.
            defer(fn () => static::pushToPhones($alerts, $type), always: true);
        } catch (Throwable $e) {
            Log::warning("Admin alert '{$type}' could not be created: " . $e->getMessage());
        }
    }

    /** @return Collection<int, User> */
    public static function recipientsFor(string $type): Collection
    {
        $rule = static::TYPES[$type] ?? ['permission' => null, 'roles' => null];

        // Query only admin-panel role holders — never scan the customers table.
        $adminRoles = array_merge(
            ['admin', 'manager', 'staff'],
            DB::table('roles')->where('can_access_admin', true)->pluck('name')->all()
        );

        return User::whereIn('role', array_unique($adminRoles))
            ->where('is_active', true)
            ->get()
            ->filter(fn (User $user) => $user->canAccessAdmin()
                && ($rule['roles'] === null || in_array($user->role, $rule['roles'], true))
                && ($rule['permission'] === null || $user->hasPermission($rule['permission'])))
            ->values();
    }

    /** @param Collection<int, AdminAlert> $alerts */
    protected static function pushToPhones(Collection $alerts, string $type): void
    {
        try {
            if ($alerts->isEmpty() || ! WebPushSender::isConfigured()) {
                return;
            }

            $subs = PushSubscription::where('scope', 'admin')
                ->whereIn('user_id', $alerts->pluck('user_id'))
                ->get()
                ->groupBy('user_id');

            $messages = collect();
            foreach ($alerts as $alert) {
                foreach ($subs->get($alert->user_id, collect()) as $sub) {
                    $messages->push(['subscription' => $sub, 'payload' => static::payloadFor($alert)]);
                }
            }

            (new WebPushSender)->send($messages, static::TYPES[$type]['urgency'] ?? 'normal');
        } catch (Throwable $e) {
            Log::warning('Admin alert push dispatch failed: ' . $e->getMessage());
        }
    }

    /**
     * Kept deliberately small (push payloads are capped around 4KB) and free of anything
     * sensitive beyond what the alert itself shows — no phone number or address, since a
     * phone's lock screen may be visible to whoever is standing next to the owner.
     */
    public static function payloadFor(AdminAlert $alert): array
    {
        $icon = setting_file_url('site_logo') ?: setting_file_url('favicon');

        return [
            'title' => $alert->title,
            'body'  => $alert->body,
            'url'   => route('admin.alerts.open', $alert),
            'tag'   => 'admin-alert-' . $alert->id,
            'type'  => $alert->type,
            'icon'  => $icon,
            'alert_id' => $alert->id,
        ];
    }
}
