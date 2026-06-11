<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentMethodController extends Controller
{
    public function index()
    {
        $methods = PaymentMethod::orderBy('sort_order')->orderBy('name')->get();
        return view('admin.payment_methods.index', compact('methods'));
    }

    public function create()
    {
        return view('admin.payment_methods.create');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($request->name);
        PaymentMethod::create($data);
        return redirect()->route('admin.payment-methods.index')->with('success', 'Payment method created.');
    }

    public function edit(PaymentMethod $paymentMethod)
    {
        return view('admin.payment_methods.edit', compact('paymentMethod'));
    }

    public function update(Request $request, PaymentMethod $paymentMethod)
    {
        $data = $this->validated($request, $paymentMethod->id);
        $paymentMethod->update($data);
        return redirect()->route('admin.payment-methods.index')->with('success', 'Payment method updated.');
    }

    public function destroy(PaymentMethod $paymentMethod)
    {
        if ($paymentMethod->payments()->exists()) {
            return back()->with('error', 'Cannot delete — this method has existing payment records.');
        }
        $paymentMethod->delete();
        return redirect()->route('admin.payment-methods.index')->with('success', 'Payment method deleted.');
    }

    public function toggle(PaymentMethod $paymentMethod)
    {
        $paymentMethod->update(['is_active' => !$paymentMethod->is_active]);
        return back()->with('success', $paymentMethod->name . ' ' . ($paymentMethod->is_active ? 'disabled' : 'enabled') . '.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $request->validate([
            'name'           => 'required|string|max:100',
            'type'           => 'required|in:cod,mobile_banking,bank_transfer,card',
            'description'    => 'nullable|string|max:255',
            'account_name'   => 'nullable|string|max:100',
            'account_number' => 'nullable|string|max:50',
            'bank_name'      => 'nullable|string|max:100',
            'branch'         => 'nullable|string|max:100',
            'routing_number' => 'nullable|string|max:50',
            'instructions'   => 'nullable|string|max:2000',
            'charge_type'    => 'required|in:none,fixed,percent',
            'charge_value'   => 'required|numeric|min:0',
            'min_amount'     => 'nullable|numeric|min:0',
            'max_amount'     => 'nullable|numeric|min:0',
            'sort_order'     => 'required|integer|min:0',
            'logo'           => 'nullable|image|max:512',
        ]);

        $data = $request->only([
            'name', 'type', 'description', 'account_name', 'account_number',
            'bank_name', 'branch', 'routing_number', 'instructions',
            'charge_type', 'charge_value', 'min_amount', 'max_amount', 'sort_order',
        ]);

        $data['is_active']   = $request->boolean('is_active');
        $data['min_amount']  = $request->filled('min_amount')  ? $request->min_amount  : null;
        $data['max_amount']  = $request->filled('max_amount')  ? $request->max_amount  : null;
        $data['charge_value'] = $request->filled('charge_value') ? $request->charge_value : 0;

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('payment_methods', 'public');
        }

        return $data;
    }

    private function uniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $base = $slug;
        $i    = 1;
        while (PaymentMethod::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
}
