<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\CustomInvoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\AuditLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Admin → Custom Invoices: build an invoice, quotation, proforma or money receipt by hand —
 * pick products from the catalog or type any line — and download / print it as a PDF in the
 * same design as order invoices (Settings → Invoice: logo, colours, terms, signature).
 * Standalone: it never creates an order or moves stock.
 */
class CustomInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = CustomInvoice::query()->latest('issue_date')->latest('id');

        if ($search = trim((string) $request->input('search'))) {
            $like = '%' . addcslashes($search, '%_\\') . '%';
            $query->where(fn ($q) => $q->where('number', 'like', $like)
                ->orWhere('customer_name', 'like', $like)
                ->orWhere('customer_phone', 'like', $like));
        }
        if ($request->filled('status') && isset(CustomInvoice::STATUSES[$request->status])) {
            $query->where('status', $request->status);
        }
        if ($request->filled('type') && isset(CustomInvoice::TYPES[$request->type])) {
            $query->where('type', $request->type);
        }

        $invoices = $query->paginate(25)->withQueryString();

        // money cards — real invoices only (quotations/proformas aren't money owed)
        $billable = CustomInvoice::whereIn('type', ['invoice', 'receipt'])->whereNotIn('status', ['draft', 'cancelled']);
        $stats = [
            'count' => CustomInvoice::count(),
            'billed' => (float) (clone $billable)->sum('total'),
            'collected' => (float) (clone $billable)->sum('paid_amount'),
            'thisMonth' => (float) (clone $billable)->where('issue_date', '>=', now()->startOfMonth()->toDateString())->sum('total'),
        ];
        $stats['due'] = max(0, $stats['billed'] - $stats['collected']);

        return view('admin.custom-invoices.index', compact('invoices', 'stats'));
    }

    public function create(Request $request)
    {
        $invoice = new CustomInvoice([
            'type' => $request->input('type', 'invoice'),
            'status' => 'unpaid',
            'issue_date' => now(),
            'discount_type' => 'fixed',
            'terms' => setting('invoice_terms'),
            'items' => [],
        ]);

        // "Invoice from order": start from an existing order's customer and items
        if ($request->filled('order') && ($order = Order::with('items')->find($request->order))) {
            $invoice->fill([
                'user_id' => $order->user_id,
                'customer_name' => $order->shipping_name,
                'customer_phone' => $order->shipping_phone,
                'customer_email' => $order->shipping_email ?? null,
                'customer_address' => collect([$order->shipping_address, $order->shipping_city])->filter()->implode(', '),
                'shipping' => $order->shipping,
                'discount_value' => $order->discount,
                'notes' => "Order ref: {$order->order_number}",
                'items' => $order->items->map(fn ($i) => [
                    'product_id' => $i->product_id, 'name' => $i->product_name, 'description' => $i->variant_label,
                    'qty' => $i->quantity, 'price' => (float) $i->price, 'discount' => 0,
                ])->all(),
            ]);
        }

        return view('admin.custom-invoices.form', ['invoice' => $invoice, 'nextNumber' => CustomInvoice::nextNumber($invoice->type)]);
    }

    public function store(Request $request)
    {
        $invoice = new CustomInvoice(['created_by' => auth()->id()]);
        $this->fillFromRequest($invoice, $request);

        // two admins saving at the same moment could pick the same number — retry with the next one
        for ($attempt = 0; ; $attempt++) {
            $invoice->number = CustomInvoice::nextNumber($invoice->type, $invoice->issue_date);
            try {
                $invoice->save();
                break;
            } catch (QueryException $e) {
                if ($attempt >= 3 || !str_contains($e->getMessage(), 'Duplicate')) {
                    throw $e;
                }
            }
        }

        AuditLogger::log('custom_invoice.created', "Created {$invoice->typeMeta()[1]} {$invoice->number} for {$invoice->customer_name} (৳" . number_format((float) $invoice->total, 2) . ')', $invoice);

        return redirect()->route('admin.custom-invoices.show', $invoice)->with('success', "{$invoice->typeMeta()[1]} {$invoice->number} saved.");
    }

    public function show(CustomInvoice $customInvoice)
    {
        return view('admin.custom-invoices.show', ['invoice' => $customInvoice]);
    }

    public function edit(CustomInvoice $customInvoice)
    {
        return view('admin.custom-invoices.form', ['invoice' => $customInvoice, 'nextNumber' => $customInvoice->number]);
    }

    public function update(Request $request, CustomInvoice $customInvoice)
    {
        $old = $customInvoice->only(['total', 'paid_amount', 'status']);
        $this->fillFromRequest($customInvoice, $request);
        $customInvoice->save();

        AuditLogger::log('custom_invoice.updated', "Updated {$customInvoice->typeMeta()[1]} {$customInvoice->number}", $customInvoice, $old, $customInvoice->only(['total', 'paid_amount', 'status']));

        return redirect()->route('admin.custom-invoices.show', $customInvoice)->with('success', 'Changes saved.');
    }

    public function destroy(CustomInvoice $customInvoice)
    {
        AuditLogger::log('custom_invoice.deleted', "Deleted {$customInvoice->typeMeta()[1]} {$customInvoice->number} ({$customInvoice->customer_name})", $customInvoice, $customInvoice->only(['number', 'customer_name', 'total']));
        $customInvoice->delete();

        return redirect()->route('admin.custom-invoices.index')->with('success', 'Deleted.');
    }

    /** A new draft copy (new number, today's date, nothing paid) — e.g. turn a quotation into an invoice. */
    public function duplicate(Request $request, CustomInvoice $customInvoice)
    {
        $copy = $customInvoice->replicate(['number', 'created_at', 'updated_at']);
        $copy->type = isset(CustomInvoice::TYPES[$request->input('type')]) ? $request->input('type') : $customInvoice->type;
        $copy->issue_date = now();
        $copy->due_date = null;
        $copy->paid_amount = 0;
        $copy->payment_method = null;
        $copy->status = 'unpaid';
        $copy->created_by = auth()->id();
        $copy->number = CustomInvoice::nextNumber($copy->type);
        $copy->save();

        AuditLogger::log('custom_invoice.created', "Created {$copy->typeMeta()[1]} {$copy->number} from {$customInvoice->number}", $copy);

        return redirect()->route('admin.custom-invoices.edit', $copy)->with('success', "Copied to {$copy->number} — review and save.");
    }

    /** Quick "record a payment" from the invoice page. */
    public function payment(Request $request, CustomInvoice $customInvoice)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['nullable', 'string', 'max:40'],
        ]);
        $old = $customInvoice->only(['paid_amount', 'status']);
        $customInvoice->paid_amount = min((float) $customInvoice->total, (float) $customInvoice->paid_amount + (float) $data['amount']);
        $customInvoice->payment_method = $data['payment_method'] ?: $customInvoice->payment_method;
        if ($customInvoice->status === 'draft') {
            $customInvoice->status = 'unpaid';
        }
        $customInvoice->syncPaymentStatus()->save();

        AuditLogger::log('custom_invoice.payment', "Payment of ৳" . number_format((float) $data['amount'], 2) . " recorded on {$customInvoice->number}", $customInvoice, $old, $customInvoice->only(['paid_amount', 'status']));

        return back()->with('success', 'Payment recorded.');
    }

    public function status(Request $request, CustomInvoice $customInvoice)
    {
        $request->validate(['status' => ['required', Rule::in(array_keys(CustomInvoice::STATUSES))]]);
        $old = $customInvoice->status;
        $customInvoice->status = $request->status;
        if ($request->status === 'paid') {
            $customInvoice->paid_amount = $customInvoice->total;
        } elseif (in_array($request->status, ['unpaid', 'partial'], true)) {
            $customInvoice->syncPaymentStatus();
        }
        $customInvoice->save();

        AuditLogger::log('custom_invoice.status', "{$customInvoice->number} marked {$customInvoice->statusMeta()[0]}", $customInvoice, ['status' => $old], ['status' => $customInvoice->status]);

        return back()->with('success', 'Marked ' . $customInvoice->statusMeta()[0] . '.');
    }

    /** PDF — ?inline=1 opens it in the browser (to print), otherwise downloads. */
    public function pdf(Request $request, CustomInvoice $customInvoice)
    {
        $pdf = Pdf::loadView('admin.custom-invoices.pdf', ['invoice' => $customInvoice])->setPaper('a4', 'portrait');
        $this->registerInvoiceFont($pdf);
        $name = strtolower($customInvoice->typeMeta()[1]) . "-{$customInvoice->number}.pdf";

        return $request->boolean('inline') ? $pdf->stream($name) : $pdf->download($name);
    }

    /** Builder look-ups: catalog products (word search) and existing customers with their address. */
    public function lookup(Request $request)
    {
        $q = trim((string) $request->input('q'));
        if (mb_strlen($q) < 2) {
            return response()->json(['products' => [], 'customers' => []]);
        }

        $products = $request->input('kind') === 'customers' ? collect() : Product::query()
            ->where(fn ($w) => $w->search($q)->orWhere('products.sku', $q)->orWhere('products.barcode', $q))
            ->orderByRelevance($q)
            ->limit(8)
            ->get(['id', 'name', 'sku', 'price', 'sale_price', 'image', 'stock'])
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'price' => (float) ($p->sale_price && $p->sale_price < $p->price ? $p->sale_price : $p->price),
                'regular' => (float) $p->price,
                'stock' => (int) $p->stock,
                'image' => $p->image ? image_thumb($p->image, 200) : null,
            ]);

        $customers = $request->input('kind') === 'products' ? collect() : User::query()
            ->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('phone', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%"))
            ->limit(6)
            ->get(['id', 'name', 'phone', 'email'])
            ->map(function ($u) {
                $address = Address::where('user_id', $u->id)->orderByDesc('is_default')->latest('id')->first();
                $line = $address ? collect([$address->address_line1, $address->address_line2, $address->city])->filter()->implode(', ') : null;
                if (!$line && ($order = Order::where('user_id', $u->id)->latest('id')->first(['shipping_address', 'shipping_city']))) {
                    $line = collect([$order->shipping_address, $order->shipping_city])->filter()->implode(', ');
                }

                return ['id' => $u->id, 'name' => $u->name, 'phone' => $u->phone, 'email' => $u->email, 'address' => $line];
            });

        return response()->json(['products' => $products, 'customers' => $customers]);
    }

    private function fillFromRequest(CustomInvoice $invoice, Request $request): void
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(CustomInvoice::TYPES))],
            'status' => ['required', Rule::in(array_keys(CustomInvoice::STATUSES))],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:40'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_address' => ['nullable', 'string', 'max:1000'],
            'issue_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'items' => ['required', 'array', 'min:1', 'max:200'],
            'items.*.product_id' => ['nullable', 'integer'],
            'items.*.name' => ['nullable', 'string', 'max:255'],
            'items.*.description' => ['nullable', 'string', 'max:500'],
            'items.*.qty' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'items.*.price' => ['nullable', 'numeric', 'min:0', 'max:100000000'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'discount_type' => ['required', Rule::in(['fixed', 'percent'])],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'shipping' => ['nullable', 'numeric', 'min:0'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'terms' => ['nullable', 'string', 'max:3000'],
            'accent_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ], [
            'items.required' => 'Add at least one item.',
            'customer_name.required' => 'Customer name is required.',
        ]);

        $invoice->fill(collect($data)->except('items')->map(fn ($v, $k) => in_array($k, ['discount_value', 'shipping', 'tax_rate', 'paid_amount'], true) ? (float) ($v ?? 0) : $v)->all());
        $invoice->items = $data['items'];
        $invoice->recalculate();

        if (!$invoice->items) {
            throw \Illuminate\Validation\ValidationException::withMessages(['items' => 'Add at least one item with a name and quantity.']);
        }
        $invoice->syncPaymentStatus();
    }

    /** Hind Siliguri for Bangla names/addresses — same as OrderController::registerInvoiceFont. */
    private function registerInvoiceFont($pdf): void
    {
        $fontMetrics = $pdf->getDomPDF()->getFontMetrics();
        foreach (['normal', 'italic'] as $style) {
            $fontMetrics->registerFont(['family' => 'Hind Siliguri', 'style' => $style, 'weight' => 'normal'], storage_path('fonts/HindSiliguri-Regular.ttf'));
            $fontMetrics->registerFont(['family' => 'Hind Siliguri', 'style' => $style, 'weight' => 'bold'], storage_path('fonts/HindSiliguri-Bold.ttf'));
        }
    }
}
