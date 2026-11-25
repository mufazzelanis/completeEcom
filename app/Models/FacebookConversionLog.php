<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacebookConversionLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'event_name', 'event_id', 'pixel_id', 'landing_page_id', 'product_id', 'items', 'value', 'source_url', 'ip_address', 'user_agent',
        'match_keys', 'status', 'http_status', 'response', 'sent_at',
    ];

    /**
     * The customer-matching keys Meta accepts, in the order shown: [label, icon, weight, what it
     * is / why it may be missing]. Weights are a rough guide to how much each key helps Meta find
     * the person (email and phone most, IP + browser least) — this app's own estimate, not Meta's
     * Event Match Quality, which Meta only shows per event type in Events Manager.
     */
    public const MATCH_KEYS = [
        'em' => ['Email', '✉️', 3.0, 'The customer\'s email — sent once they log in or order with one.'],
        'ph' => ['Phone', '📱', 3.0, 'The customer\'s phone — sent when logged in, and with every order.'],
        'fbc' => ['Ad click', '🎯', 1.5, 'The Facebook ad click (fbclid) — only when the visitor came from an ad.'],
        'fbp' => ['Browser ID', '🍪', 1.0, 'The Meta pixel\'s cookie in this browser — missing if the visitor blocks the pixel.'],
        'external_id' => ['Visitor ID', '🆔', 1.0, 'This shop\'s own id for the customer (account, or an anonymous one for guests).'],
        'fn' => ['First name', '👤', 0.4, 'From the account or the order form.'],
        'ln' => ['Last name', '👤', 0.3, 'From the account or the order form.'],
        'ct' => ['City', '🏙️', 0.3, 'From the order\'s delivery address.'],
        'st' => ['Region', '🗺️', 0.2, 'From the order\'s delivery address.'],
        'zp' => ['Postcode', '📮', 0.2, 'From the order\'s delivery address.'],
        'country' => ['Country', '🌍', 0.2, 'From the visitor\'s IP location.'],
        'ip' => ['IP address', '🌐', 0.6, 'Always sent.'],
        'ua' => ['Browser', '💻', 0.4, 'Always sent.'],
    ];

    /** user_data field → MATCH_KEYS key */
    public const USER_DATA_FIELDS = ['client_ip_address' => 'ip', 'client_user_agent' => 'ua'];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    /** The MATCH_KEYS present in a Conversions API user_data block, stored as ",em,ph,fbp,". */
    public static function matchKeysFrom(array $userData): ?string
    {
        $keys = [];
        foreach ($userData as $field => $value) {
            $key = self::USER_DATA_FIELDS[$field] ?? $field;
            if (isset(self::MATCH_KEYS[$key]) && $value !== null && $value !== '' && $value !== []) {
                $keys[] = $key;
            }
        }

        return $keys ? ',' . implode(',', array_unique($keys)) . ',' : null;
    }

    /** null = logged before key tracking existed */
    public function matchKeyList(): ?array
    {
        return $this->match_keys === null ? null : array_values(array_filter(explode(',', $this->match_keys)));
    }

    /** 0–10 estimate of how well Meta can recognise the customer (see MATCH_KEYS). */
    public static function scoreFor(array $keys): float
    {
        return round(min(10, array_sum(array_map(fn ($k) => self::MATCH_KEYS[$k][2] ?? 0, $keys))), 1);
    }

    /** [label, colour, bars 1–4] for a score */
    public static function grade(float $score): array
    {
        return match (true) {
            $score >= 7 => ['Great', '#16a34a', 4],
            $score >= 4.5 => ['Good', '#0ea5e9', 3],
            $score >= 3 => ['OK', '#f59e0b', 2],
            default => ['Weak', '#ef4444', 1],
        };
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /** The column's DB default (CURRENT_TIMESTAMP) is the MySQL server's own clock — live, a US
     *  timezone, 5 hours behind the UTC the app stores and reads — so the app stamps it itself. */
    protected static function booted(): void
    {
        static::creating(fn ($log) => $log->sent_at ??= now());
    }

    public function landingPage()
    {
        return $this->belongsTo(LandingPage::class);
    }
}
