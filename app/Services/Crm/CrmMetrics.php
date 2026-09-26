<?php

namespace App\Services\Crm;

use App\Models\Crm\CrmContact;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Turns raw orders into the numbers the CRM runs on: lifetime value, purchase rhythm,
 * lifecycle stage, churn risk and the RFM score/segment.
 *
 * Metrics live denormalized on crm_contacts so directory filters, segments and charts are
 * plain indexed queries. They are refreshed per contact right after an order changes, and
 * fully (including the RFM quintile cut-offs) by the nightly `crm:refresh`.
 */
class CrmMetrics
{
    /** Orders that never turned into revenue don't count as purchases. */
    public const EXCLUDED_STATUSES = ['cancelled', 'refunded'];

    private const CUTOFF_CACHE = 'crm.rfm_cutoffs';

    public static function settings(): array
    {
        return [
            'active_days' => max(7, (int) Setting::get('crm_active_days', 60)),   // ordered within this → active
            'lost_days' => max(30, (int) Setting::get('crm_lost_days', 180)),      // no order for this long → lost
            'vip_spend' => (float) Setting::get('crm_vip_spend', 20000),           // lifetime spend that auto-flags VIP (0 = off)
        ];
    }

    /** Recalculate one contact (cheap; used from order hooks and after manual edits). */
    public function refreshContact(CrmContact $contact): CrmContact
    {
        $cutoffs = Cache::get(self::CUTOFF_CACHE) ?: $this->computeCutoffs();
        $agg = $this->aggregates([$contact->id])[$contact->id] ?? null;
        $this->apply($contact, $agg, $cutoffs, self::settings());

        return $contact;
    }

    /** Recalculate everyone, re-deriving the RFM quintile cut-offs from the current customer base. */
    public function refreshAll(): int
    {
        $cutoffs = $this->computeCutoffs();
        $settings = self::settings();
        $count = 0;

        CrmContact::query()->select('crm_contacts.*')->chunkById(500, function ($chunk) use ($cutoffs, $settings, &$count) {
            $aggs = $this->aggregates($chunk->pluck('id')->all());
            foreach ($chunk as $contact) {
                $this->apply($contact, $aggs[$contact->id] ?? null, $cutoffs, $settings);
                $count++;
            }
        });

        return $count;
    }

    /** @return array<int, object> keyed by contact id */
    private function aggregates(array $ids): array
    {
        if (! $ids) {
            return [];
        }

        return DB::table('orders')
            ->whereIn('crm_contact_id', $ids)
            ->groupBy('crm_contact_id')
            ->selectRaw("crm_contact_id as id,
                SUM(CASE WHEN status NOT IN ('cancelled','refunded') THEN 1 ELSE 0 END) as valid_count,
                SUM(CASE WHEN status NOT IN ('cancelled','refunded') THEN total ELSE 0 END) as spent,
                SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_count,
                SUM(CASE WHEN status IN ('cancelled','refunded') THEN 1 ELSE 0 END) as cancelled_count,
                MIN(CASE WHEN status NOT IN ('cancelled','refunded') THEN created_at END) as first_at,
                MAX(CASE WHEN status NOT IN ('cancelled','refunded') THEN created_at END) as last_at")
            ->get()
            ->keyBy('id')
            ->all();
    }

    private function apply(CrmContact $c, ?object $a, array $cutoffs, array $s): void
    {
        $valid = (int) ($a->valid_count ?? 0);
        $spent = round((float) ($a->spent ?? 0), 2);
        $first = $a && $a->first_at ? \Illuminate\Support\Carbon::parse($a->first_at) : null;
        $last = $a && $a->last_at ? \Illuminate\Support\Carbon::parse($a->last_at) : null;

        $avgGap = ($valid >= 2 && $first && $last) ? max(1, (int) round($first->diffInDays($last) / ($valid - 1))) : null;
        $daysSince = $last ? (int) $last->diffInDays(now()) : null;

        // Lifecycle
        if ($valid === 0) {
            $stage = 'prospect';
        } elseif ($daysSince >= $s['lost_days']) {
            $stage = 'lost';
        } elseif ($daysSince >= $s['active_days']) {
            $stage = 'at_risk';
        } else {
            $stage = $valid === 1 ? 'new' : 'active';
        }

        // Churn risk: how far past their OWN usual rhythm they are (fixed window for one-time buyers).
        $risk = 0;
        $predicted = null;
        if ($valid > 0) {
            $expected = $avgGap ? max($avgGap, 14) : $s['active_days']; // a sub-2-week rhythm is noise, not a promise
            $ratio = $daysSince / max(1, $expected);
            $risk = $stage === 'lost' ? 100 : (int) max(0, min(99, round(($ratio - 0.5) * 66)));
            if ($avgGap && $last) {
                $predicted = $last->copy()->addDays($avgGap)->toDateString();
            }
        }

        // RFM
        $r = $f = $m = null;
        $segment = null;
        if ($valid > 0) {
            $r = 5 - count(array_filter($cutoffs['r'], fn ($x) => $daysSince > $x));
            $f = 1 + count(array_filter($cutoffs['f'], fn ($x) => $valid > $x));
            $m = 1 + count(array_filter($cutoffs['m'], fn ($x) => $spent > $x));
            $segment = self::segmentFor($r, $f, $m, $valid);
        }

        $updates = [
            'orders_count' => $valid,
            'delivered_count' => (int) ($a->delivered_count ?? 0),
            'cancelled_count' => (int) ($a->cancelled_count ?? 0),
            'total_spent' => $spent,
            'avg_order_value' => $valid ? round($spent / $valid, 2) : 0,
            'first_order_at' => $first,
            'last_order_at' => $last,
            'avg_days_between_orders' => $avgGap,
            'predicted_next_order_on' => $predicted,
            'lifecycle_stage' => $stage,
            'churn_risk' => $risk,
            'rfm_r' => $r, 'rfm_f' => $f, 'rfm_m' => $m,
            'rfm_segment' => $segment,
            'metrics_refreshed_at' => now(),
        ];
        if ($s['vip_spend'] > 0 && $spent >= $s['vip_spend'] && ! $c->is_vip) {
            $updates['is_vip'] = true; // one-way: staff can still un-flag manually and it won't be re-set until spend grows past again
        }

        $c->forceFill($updates)->saveQuietly();
    }

    /** Classic RFM segment names from the three 1–5 scores. */
    public static function segmentFor(int $r, int $f, int $m, int $orders = 0): string
    {
        $fm = (int) round(($f + $m) / 2);

        if ($r >= 4) {
            if ($orders === 1 || ($orders === 0 && $f === 1)) return 'New Customers';
            return $fm >= 4 ? 'Champions' : 'Potential Loyalists';
        }
        if ($r === 3) {
            if ($fm >= 4) return 'Loyal Customers';
            return $fm === 3 ? 'Need Attention' : 'About to Sleep';
        }
        if ($fm >= 4) return "Can't Lose Them";
        if ($fm === 3) return 'At Risk';

        return $fm === 2 ? 'Hibernating' : 'Lost';
    }

    /** Colour + one-line advice per RFM segment, used by the UI. */
    public const SEGMENT_INFO = [
        'Champions' => ['color' => 'green', 'tip' => 'Bought recently, often, and spends the most. Reward them; ask for reviews and referrals.'],
        'Loyal Customers' => ['color' => 'teal', 'tip' => 'Spend well and buy regularly. Upsell and offer loyalty perks.'],
        'Potential Loyalists' => ['color' => 'blue', 'tip' => 'Recent buyers with average spend. Nudge a second and third purchase.'],
        'New Customers' => ['color' => 'sky', 'tip' => 'Just bought for the first time. Send a warm welcome and onboarding.'],
        'Need Attention' => ['color' => 'amber', 'tip' => 'Above-average but slipping. Time-limited offers help.'],
        'About to Sleep' => ['color' => 'yellow', 'tip' => 'Below-average and fading. Re-engage before they go cold.'],
        "Can't Lose Them" => ['color' => 'orange', 'tip' => 'Big spenders who have gone quiet. Personal call or a strong win-back offer.'],
        'At Risk' => ['color' => 'red', 'tip' => 'Used to buy often, silent now. Win-back campaign.'],
        'Hibernating' => ['color' => 'gray', 'tip' => 'Low activity for a long time. Low-cost reactivation only.'],
        'Lost' => ['color' => 'gray', 'tip' => 'Lowest scores on every measure. Do not spend much effort.'],
    ];

    /** Quintile cut-offs (20/40/60/80th percentile) over customers with at least one valid order. */
    public function computeCutoffs(): array
    {
        $rows = DB::table('orders')
            ->whereNotNull('crm_contact_id')
            ->whereNotIn('status', self::EXCLUDED_STATUSES)
            ->groupBy('crm_contact_id')
            ->selectRaw('COUNT(*) as f, SUM(total) as m, MAX(created_at) as last_at')
            ->get();

        $now = now();
        $r = $rows->map(fn ($x) => (int) \Illuminate\Support\Carbon::parse($x->last_at)->diffInDays($now))->all();
        $f = $rows->map(fn ($x) => (int) $x->f)->all();
        $m = $rows->map(fn ($x) => (float) $x->m)->all();

        $cutoffs = ['r' => self::quantiles($r), 'f' => self::quantiles($f), 'm' => self::quantiles($m)];
        Cache::forever(self::CUTOFF_CACHE, $cutoffs);

        return $cutoffs;
    }

    private static function quantiles(array $values): array
    {
        if (! $values) {
            return [0, 0, 0, 0];
        }
        sort($values);
        $n = count($values);

        return array_map(fn ($p) => $values[max(0, min($n - 1, (int) ceil($p * $n) - 1))], [0.2, 0.4, 0.6, 0.8]);
    }
}
