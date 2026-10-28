<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * A hand-written invoice / quotation (Admin → Custom Invoices). Totals are always recomputed
 * on the server from the line items (see recalculate) — the builder's live numbers are only a
 * preview, never trusted.
 */
class CustomInvoice extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'items' => 'array',
        'issue_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'discount' => 'decimal:2',
        'shipping' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    /** type => [title printed on the document, admin label, accent hex] */
    public const TYPES = [
        'invoice'   => ['INVOICE', 'Invoice', '#6366f1'],
        'quotation' => ['QUOTATION', 'Quotation', '#0ea5e9'],
        'proforma'  => ['PROFORMA INVOICE', 'Proforma', '#f97316'],
        'receipt'   => ['MONEY RECEIPT', 'Receipt', '#10b981'],
    ];

    /** status => [label, hex] */
    public const STATUSES = [
        'draft'     => ['Draft', '#94a3b8'],
        'unpaid'    => ['Unpaid', '#ef4444'],
        'partial'   => ['Partially paid', '#f59e0b'],
        'paid'      => ['Paid', '#10b981'],
        'cancelled' => ['Cancelled', '#64748b'],
    ];

    public const PAYMENT_METHODS = ['Cash', 'bKash', 'Nagad', 'Rocket', 'Bank transfer', 'Card', 'Cash on delivery', 'Cheque'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getDueAmountAttribute(): float
    {
        return max(0, round((float) $this->total - (float) $this->paid_amount, 2));
    }

    public function typeMeta(): array
    {
        return self::TYPES[$this->type] ?? self::TYPES['invoice'];
    }

    public function statusMeta(): array
    {
        // a quotation / proforma isn't money owed — "Unpaid" would read like a debt
        if (in_array($this->type, ['quotation', 'proforma'], true) && in_array($this->status, ['unpaid', 'partial', 'paid'], true)) {
            return ['Open', '#0ea5e9'];
        }

        return self::STATUSES[$this->status] ?? self::STATUSES['unpaid'];
    }

    /**
     * Cleans the line items and works out every total from them. Each line: qty × price minus
     * its own discount; then the invoice discount (fixed or %) on the subtotal, tax (%) on what's
     * left, plus shipping. Paid can't exceed the total.
     */
    public function recalculate(): static
    {
        $items = collect($this->items ?? [])
            ->map(fn ($i) => [
                'product_id' => !empty($i['product_id']) ? (int) $i['product_id'] : null,
                'name' => trim((string) ($i['name'] ?? '')),
                'description' => trim((string) ($i['description'] ?? '')) ?: null,
                'qty' => max(0, round((float) ($i['qty'] ?? 0), 2)),
                'price' => max(0, round((float) ($i['price'] ?? 0), 2)),
                'discount' => max(0, round((float) ($i['discount'] ?? 0), 2)),
            ])
            ->filter(fn ($i) => $i['name'] !== '' && $i['qty'] > 0)
            ->map(fn ($i) => $i + ['total' => max(0, round($i['qty'] * $i['price'] - $i['discount'], 2))])
            ->values();

        $subtotal = round($items->sum('total'), 2);
        $discountValue = max(0, (float) $this->discount_value);
        $discount = $this->discount_type === 'percent'
            ? round($subtotal * min(100, $discountValue) / 100, 2)
            : min($subtotal, round($discountValue, 2));
        $taxRate = min(100, max(0, (float) $this->tax_rate));
        $tax = round(($subtotal - $discount) * $taxRate / 100, 2);
        $total = round($subtotal - $discount + $tax + max(0, (float) $this->shipping), 2);

        $this->items = $items->all();
        $this->subtotal = $subtotal;
        $this->discount = $discount;
        $this->tax_rate = $taxRate;
        $this->tax = $tax;
        $this->total = $total;
        $this->paid_amount = min($total, max(0, round((float) $this->paid_amount, 2)));

        return $this;
    }

    /** Status implied by the payment, unless it's a draft or cancelled (those are set by hand). */
    public function syncPaymentStatus(): static
    {
        if (!in_array($this->status, ['draft', 'cancelled'], true)) {
            $this->status = $this->paid_amount <= 0 ? 'unpaid' : ($this->paid_amount >= $this->total ? 'paid' : 'partial');
        }

        return $this;
    }

    /** Next number like "CI-2026-0007" — per type prefix, numbered within the year. */
    public static function nextNumber(string $type, ?\DateTimeInterface $date = null): string
    {
        $prefixes = ['invoice' => 'CI', 'quotation' => 'QT', 'proforma' => 'PI', 'receipt' => 'MR'];
        $prefix = (setting('custom_invoice_prefix') ?: '') . ($prefixes[$type] ?? 'CI') . '-' . ($date ? $date->format('Y') : now()->format('Y')) . '-';

        $last = DB::table('custom_invoices')->where('number', 'like', $prefix . '%')
            ->orderByRaw('LENGTH(number) DESC')->orderByDesc('number')->value('number');
        $n = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad((string) $n, 4, '0', STR_PAD_LEFT);
    }
}
