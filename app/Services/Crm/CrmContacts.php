<?php

namespace App\Services\Crm;

use App\Models\Crm\CrmActivity;
use App\Models\Crm\CrmContact;
use App\Models\Crm\CrmTag;
use App\Models\Order;
use App\Models\User;
use App\Services\CourierFraudCheckService;
use Illuminate\Support\Str;

/**
 * Finds or creates the CRM contact for a person, however they reached the shop.
 *
 * Identity is decided in this order: linked user account → phone number → e-mail.
 * Phone is the strongest key on this store because most orders are guest checkouts
 * with no account at all, and free-typed numbers ("+880 1712-345678", "০১৭১২...")
 * are normalized to the plain 11-digit local form before matching.
 */
class CrmContacts
{
    public static function normalizePhone(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }
        $n = CourierFraudCheckService::normalizePhone($phone);

        // Anything shorter than a real local number is noise ("N/A", "123") — never use it as an identity.
        return strlen($n) >= 10 && strlen($n) <= 15 ? $n : null;
    }

    public static function normalizeEmail(?string $email): ?string
    {
        $email = $email !== null ? Str::lower(trim($email)) : null;

        return $email && filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    /**
     * Core resolver. Never throws for a merely odd input — returns null when there is nothing
     * usable to identify a person by, so callers (order hooks) can't break checkout.
     */
    public function resolve(array $attrs, string $source = 'manual', ?int $userId = null): ?CrmContact
    {
        $phone = self::normalizePhone($attrs['phone'] ?? null);
        $email = self::normalizeEmail($attrs['email'] ?? null);
        $name = trim((string) ($attrs['name'] ?? ''));

        $contact = null;
        if ($userId) {
            $contact = CrmContact::where('user_id', $userId)->first();
        }
        if (! $contact && $phone) {
            $byPhone = CrmContact::where('phone', $phone)->first();
            // A phone already tied to a *different* account stays with that account.
            if ($byPhone && (! $byPhone->user_id || ! $userId || (int) $byPhone->user_id === (int) $userId)) {
                $contact = $byPhone;
            }
        }
        if (! $contact && $email) {
            $byEmail = CrmContact::where('email', $email)->first();
            if ($byEmail && (! $byEmail->user_id || ! $userId || (int) $byEmail->user_id === (int) $userId)) {
                $contact = $byEmail;
            }
        }

        if (! $contact) {
            if (! $userId && ! $phone && ! $email) {
                return null;
            }

            return CrmContact::create([
                'user_id' => $userId,
                'name' => $name !== '' ? Str::limit($name, 250, '') : ($email ? Str::before($email, '@') : 'Unknown'),
                'phone' => $phone && ! CrmContact::where('phone', $phone)->exists() ? $phone : null,
                'email' => $email,
                'city' => $attrs['city'] ?? null,
                'source' => $source,
            ]);
        }

        // Existing contact: only ever FILL GAPS — never overwrite what staff may have corrected by hand.
        $fill = [];
        if ($userId && ! $contact->user_id && ! CrmContact::where('user_id', $userId)->exists()) {
            $fill['user_id'] = $userId;
        }
        if ($phone && ! $contact->phone && ! CrmContact::where('phone', $phone)->exists()) {
            $fill['phone'] = $phone;
        }
        if ($email && ! $contact->email) {
            $fill['email'] = $email;
        }
        if (! empty($attrs['city']) && ! $contact->city) {
            $fill['city'] = $attrs['city'];
        }
        if ($fill) {
            $contact->update($fill);
        }

        return $contact;
    }

    public function forOrder(Order $order): ?CrmContact
    {
        $contact = $this->resolve([
            'name' => $order->shipping_name,
            'phone' => $order->shipping_phone,
            'email' => $order->guest_email ?: $order->user?->email,
            'city' => $order->shipping_city,
        ], 'order', $order->user_id);

        if ($contact && (int) $order->crm_contact_id !== (int) $contact->id) {
            $order->forceFill(['crm_contact_id' => $contact->id])->saveQuietly();
        }

        return $contact;
    }

    public function forUser(User $user, string $source = 'registration'): ?CrmContact
    {
        return $this->resolve([
            'name' => $user->name,
            'phone' => $user->phone,
            'email' => $user->email,
        ], $source, $user->id);
    }

    public function forEmail(string $email, string $source = 'newsletter', ?string $name = null): ?CrmContact
    {
        return $this->resolve(['name' => $name, 'email' => $email], $source);
    }

    /** Admin-panel users who can own contacts / be assigned tasks. */
    public static function staff(): \Illuminate\Support\Collection
    {
        $roles = array_merge(['admin', 'manager', 'staff'], \Illuminate\Support\Facades\DB::table('roles')->where('can_access_admin', true)->pluck('name')->all());

        return User::whereIn('role', array_unique($roles))->where('is_active', true)->orderBy('name')->get(['id', 'name', 'role']);
    }

    public function tag(CrmContact $contact, string $name, string $color = 'gray'): void
    {
        $tag = CrmTag::firstOrCreate(['name' => Str::limit(trim($name), 60, '')], ['color' => $color]);
        $contact->tags()->syncWithoutDetaching([$tag->id]);
    }

    /** A system-generated line on the timeline (order placed, stage changed, merged …). */
    public function logSystem(CrmContact $contact, string $subject, ?string $body = null, array $meta = []): CrmActivity
    {
        return CrmActivity::create([
            'contact_id' => $contact->id,
            'type' => 'note',
            'subject' => $subject,
            'body' => $body,
            'meta' => ['system' => true] + $meta,
            'occurred_at' => now(),
        ]);
    }
}
