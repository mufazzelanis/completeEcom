<?php

namespace App\Services\Crm;

use App\Models\Crm\CrmContact;
use App\Models\Crm\CrmSegment;
use Illuminate\Database\Eloquent\Builder;

/**
 * Dynamic segments: a saved list of rules ("spent > 5000 AND last order > 60 days ago")
 * evaluated live against crm_contacts. Rules are validated against FIELDS, so nothing from a
 * request is ever interpolated into SQL — only whitelisted columns and operators are used.
 */
class CrmSegmentEngine
{
    /** type: number | text | enum | bool | tag */
    public const FIELDS = [
        'lifecycle_stage' => ['label' => 'Lifecycle stage', 'type' => 'enum'],
        'rfm_segment' => ['label' => 'RFM segment', 'type' => 'enum'],
        'orders_count' => ['label' => 'Number of orders', 'type' => 'number'],
        'total_spent' => ['label' => 'Total spent (৳)', 'type' => 'number'],
        'avg_order_value' => ['label' => 'Average order value (৳)', 'type' => 'number'],
        'days_since_last_order' => ['label' => 'Days since last order', 'type' => 'number'],
        'days_since_created' => ['label' => 'Days since became a contact', 'type' => 'number'],
        'churn_risk' => ['label' => 'Churn risk (0-100)', 'type' => 'number'],
        'rfm_r' => ['label' => 'Recency score (1-5)', 'type' => 'number'],
        'rfm_f' => ['label' => 'Frequency score (1-5)', 'type' => 'number'],
        'rfm_m' => ['label' => 'Monetary score (1-5)', 'type' => 'number'],
        'city' => ['label' => 'City', 'type' => 'text'],
        'source' => ['label' => 'Source', 'type' => 'enum'],
        'status' => ['label' => 'Contact status', 'type' => 'enum'],
        'tag' => ['label' => 'Has tag', 'type' => 'tag'],
        'is_vip' => ['label' => 'Is VIP', 'type' => 'bool'],
        'has_account' => ['label' => 'Has registered account', 'type' => 'bool'],
        'has_email' => ['label' => 'Has e-mail', 'type' => 'bool'],
        'has_phone' => ['label' => 'Has phone', 'type' => 'bool'],
        'birthday_this_month' => ['label' => 'Birthday is this month', 'type' => 'bool'],
    ];

    public const OPS = [
        'number' => ['eq' => '=', 'neq' => '≠', 'gt' => '>', 'gte' => '≥', 'lt' => '<', 'lte' => '≤'],
        'text' => ['eq' => 'is', 'neq' => 'is not', 'contains' => 'contains'],
        'enum' => ['eq' => 'is', 'neq' => 'is not'],
        'tag' => ['eq' => 'is'],
        'bool' => ['eq' => 'is'],
    ];

    private const COLUMN = [
        'orders_count' => 'orders_count', 'total_spent' => 'total_spent', 'avg_order_value' => 'avg_order_value',
        'churn_risk' => 'churn_risk', 'rfm_r' => 'rfm_r', 'rfm_f' => 'rfm_f', 'rfm_m' => 'rfm_m',
        'lifecycle_stage' => 'lifecycle_stage', 'rfm_segment' => 'rfm_segment', 'city' => 'city',
        'source' => 'source', 'status' => 'status',
    ];

    private const SQL_OPS = ['eq' => '=', 'neq' => '!=', 'gt' => '>', 'gte' => '>=', 'lt' => '<', 'lte' => '<='];

    /** Keep only well-formed rules; used both when saving and when running. */
    public static function sanitize(array $rules): array
    {
        $clean = [];
        foreach ($rules as $r) {
            $field = $r['field'] ?? null;
            $op = $r['op'] ?? 'eq';
            if (! isset(self::FIELDS[$field])) {
                continue;
            }
            $type = self::FIELDS[$field]['type'];
            if (! isset(self::OPS[$type][$op])) {
                $op = 'eq';
            }
            $value = $r['value'] ?? null;
            if ($type === 'number') {
                if (! is_numeric($value)) continue;
                $value = $value + 0;
            } elseif ($type === 'bool') {
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
            } else {
                $value = trim((string) $value);
                if ($value === '') continue;
                $value = mb_substr($value, 0, 100);
            }
            $clean[] = ['field' => $field, 'op' => $op, 'value' => $value];
        }

        return array_slice($clean, 0, 12);
    }

    /** Human-readable form of one rule, e.g. "Total spent (৳) ≥ 5000". */
    public static function describe(array $rule): string
    {
        $f = self::FIELDS[$rule['field']] ?? ['label' => $rule['field'], 'type' => 'text'];
        if ($f['type'] === 'bool') {
            return $f['label'] . ': ' . ($rule['value'] ? 'Yes' : 'No');
        }
        $op = self::OPS[$f['type']][$rule['op']] ?? $rule['op'];
        $value = $rule['field'] === 'lifecycle_stage' ? (\App\Models\Crm\CrmContact::LIFECYCLE[$rule['value']]['label'] ?? $rule['value']) : $rule['value'];

        return $f['label'] . ' ' . $op . ' ' . $value;
    }

    public function query(array $rules, string $match = 'all', ?Builder $base = null): Builder
    {
        $q = $base ?: CrmContact::query();
        $rules = self::sanitize($rules);
        $boolean = $match === 'any' ? 'or' : 'and';

        if (! $rules) {
            return $q;
        }

        // Grouped so that OR rules can't escape a filter the caller already put on $base.
        return $q->where(function (Builder $g) use ($rules, $boolean) {
            foreach ($rules as $rule) {
                $g->where(fn (Builder $w) => $this->applyRule($w, $rule), null, null, $boolean);
            }
        });
    }

    private function applyRule(Builder $q, array $rule): void
    {
        ['field' => $field, 'op' => $op, 'value' => $value] = $rule;

        switch ($field) {
            case 'days_since_last_order':
                // "more than N days ago" is an EARLIER date, so the comparison flips. Never-ordered contacts are excluded.
                $this->dateRule($q, 'last_order_at', $op, (int) $value, true);
                return;
            case 'days_since_created':
                $this->dateRule($q, 'created_at', $op, (int) $value, false);
                return;
            case 'tag':
                $q->whereHas('tags', fn ($t) => $t->where('crm_tags.name', $value));
                return;
            case 'is_vip':
                $q->where('is_vip', (bool) $value);
                return;
            case 'has_account':
                $value ? $q->whereNotNull('user_id') : $q->whereNull('user_id');
                return;
            case 'has_email':
                $value ? $q->whereNotNull('email') : $q->whereNull('email');
                return;
            case 'has_phone':
                $value ? $q->whereNotNull('phone') : $q->whereNull('phone');
                return;
            case 'birthday_this_month':
                $value ? $q->whereMonth('birthday', now()->month) : $q->where(fn ($x) => $x->whereNull('birthday')->orWhereMonth('birthday', '!=', now()->month));
                return;
        }

        $col = self::COLUMN[$field];
        if ($op === 'contains') {
            $q->where($col, 'like', '%' . str_replace(['%', '_'], ['\\%', '\\_'], (string) $value) . '%');
        } else {
            $q->where($col, self::SQL_OPS[$op], $value);
        }
    }

    private function dateRule(Builder $q, string $col, string $op, int $days, bool $mustExist): void
    {
        $edge = now()->subDays($days);
        if ($mustExist) {
            $q->whereNotNull($col);
        }
        match ($op) {
            'gt' => $q->where($col, '<', $edge),
            'gte' => $q->where($col, '<=', $edge),
            'lt' => $q->where($col, '>', $edge),
            'lte' => $q->where($col, '>=', $edge),
            'neq' => $q->whereNotBetween($col, [$edge->copy()->startOfDay(), $edge->copy()->endOfDay()]),
            default => $q->whereBetween($col, [$edge->copy()->startOfDay(), $edge->copy()->endOfDay()]),
        };
    }

    public function forSegment(CrmSegment $segment, ?Builder $base = null): Builder
    {
        return $this->query($segment->rules ?? [], $segment->match, $base);
    }

    public function recount(CrmSegment $segment): int
    {
        $n = $this->forSegment($segment)->count();
        $segment->forceFill(['contact_count' => $n, 'counted_at' => now()])->saveQuietly();

        return $n;
    }

    /** Ready-made segments an admin can install with one click. */
    public static function presets(): array
    {
        return [
            ['name' => 'VIP customers', 'color' => 'amber', 'match' => 'any', 'description' => 'Flagged VIP or high lifetime spend.', 'rules' => [['field' => 'is_vip', 'op' => 'eq', 'value' => 1], ['field' => 'total_spent', 'op' => 'gte', 'value' => 20000]]],
            ['name' => 'Win-back: quiet 60+ days', 'color' => 'red', 'match' => 'all', 'description' => 'Bought before, nothing for 60+ days.', 'rules' => [['field' => 'orders_count', 'op' => 'gte', 'value' => 1], ['field' => 'days_since_last_order', 'op' => 'gt', 'value' => 60]]],
            ['name' => 'Repeat buyers', 'color' => 'green', 'match' => 'all', 'description' => 'Two or more orders.', 'rules' => [['field' => 'orders_count', 'op' => 'gte', 'value' => 2]]],
            ['name' => 'One-time buyers', 'color' => 'blue', 'match' => 'all', 'description' => 'Exactly one order — the second-purchase opportunity.', 'rules' => [['field' => 'orders_count', 'op' => 'eq', 'value' => 1]]],
            ['name' => 'High churn risk', 'color' => 'orange', 'match' => 'all', 'description' => 'Churn risk 60+ but not yet lost.', 'rules' => [['field' => 'churn_risk', 'op' => 'gte', 'value' => 60], ['field' => 'lifecycle_stage', 'op' => 'neq', 'value' => 'lost']]],
            ['name' => 'Newsletter only (never ordered)', 'color' => 'indigo', 'match' => 'all', 'description' => 'On the list but never bought.', 'rules' => [['field' => 'orders_count', 'op' => 'eq', 'value' => 0], ['field' => 'has_email', 'op' => 'eq', 'value' => 1]]],
            ['name' => 'Birthday this month', 'color' => 'pink', 'match' => 'all', 'description' => 'Send a greeting and a coupon.', 'rules' => [['field' => 'birthday_this_month', 'op' => 'eq', 'value' => 1]]],
        ];
    }
}
