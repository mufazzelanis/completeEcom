<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function index()
    {
        $coupons = Coupon::latest()->paginate(15);
        return view('admin.coupons.index', compact('coupons'));
    }

    public function create()
    {
        return view('admin.coupons.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'code'              => 'required|string|max:50|unique:coupons',
            'type'              => 'required|in:fixed,percentage',
            'value'             => 'required|numeric|min:0',
            'min_order_amount'  => 'nullable|numeric|min:0',
            'max_uses'          => 'nullable|integer|min:1',
            'expires_at'        => 'nullable|date|after:today',
        ]);

        Coupon::create([
            'code'             => strtoupper($request->code),
            'type'             => $request->type,
            'value'            => $request->value,
            'min_order_amount' => $request->min_order_amount ?? 0,
            'max_uses'         => $request->max_uses,
            'expires_at'       => $request->expires_at,
            'is_active'        => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon created successfully.');
    }

    public function edit(Coupon $coupon)
    {
        return view('admin.coupons.edit', compact('coupon'));
    }

    public function update(Request $request, Coupon $coupon)
    {
        $request->validate([
            'code'  => 'required|string|max:50|unique:coupons,code,' . $coupon->id,
            'type'  => 'required|in:fixed,percentage',
            'value' => 'required|numeric|min:0',
        ]);

        $coupon->update([
            'code'             => strtoupper($request->code),
            'type'             => $request->type,
            'value'            => $request->value,
            'min_order_amount' => $request->min_order_amount ?? 0,
            'max_uses'         => $request->max_uses,
            'expires_at'       => $request->expires_at,
            'is_active'        => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon updated successfully.');
    }

    public function destroy(Coupon $coupon)
    {
        $coupon->delete();
        return redirect()->route('admin.coupons.index')->with('success', 'Coupon deleted.');
    }

    public function show(Coupon $coupon)
    {
        return redirect()->route('admin.coupons.edit', $coupon);
    }
}
