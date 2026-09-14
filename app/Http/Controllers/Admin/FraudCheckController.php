<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourierFraudCheck;
use App\Models\Order;
use App\Services\CourierFraudCheckService;
use Illuminate\Http\Request;

class FraudCheckController extends Controller
{
    public function index(Request $request)
    {
        $result = null;
        $phone = $request->query('phone');

        if ($phone) {
            $result = app(CourierFraudCheckService::class)->lastCheck($phone);
        }

        $recentChecks = CourierFraudCheck::with('checkedBy:id,name', 'order:id,order_number')
            ->when($request->filled('risk'), fn ($q) => $q->where('risk_level', $request->risk))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total_checks' => CourierFraudCheck::count(),
            'high_risk'    => CourierFraudCheck::where('risk_level', 'high')->count(),
            'checked_today'=> CourierFraudCheck::whereDate('created_at', today())->count(),
        ];

        return view('admin.fraud-checker.index', [
            'phone' => $phone,
            'result' => $result,
            'recentChecks' => $recentChecks,
            'stats' => $stats,
            'apiConfigured' => CourierFraudCheckService::isEnabled(),
        ]);
    }

    public function check(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string|min:6|max:20',
            'order_id' => 'nullable|integer|exists:orders,id',
            'force' => 'nullable|boolean',
        ]);

        $check = app(CourierFraudCheckService::class)->check(
            $validated['phone'],
            $validated['order_id'] ?? null,
            $request->boolean('force'),
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => $check->success,
                'error' => $check->error,
                'phone' => $check->phone,
                'total_orders' => $check->total_orders,
                'total_delivered' => $check->total_delivered,
                'total_cancelled' => $check->total_cancelled,
                'success_rate' => $check->success_rate,
                'risk_level' => $check->risk_level,
                'style' => $check->getRiskStyle(),
                'breakdown' => $check->breakdown,
                'checked_at' => $check->created_at->diffForHumans(),
            ]);
        }

        return redirect()->route('admin.fraud-checker.index', ['phone' => $validated['phone']]);
    }

    /**
     * Called from the order detail page — same underlying check, just scoped to that
     * order's shipping phone so the button there doesn't need its own form field.
     */
    public function checkOrder(Request $request, Order $order)
    {
        if (! $order->shipping_phone) {
            return back()->with('error', 'This order has no shipping phone number to check.');
        }

        $check = app(CourierFraudCheckService::class)->check(
            $order->shipping_phone,
            $order->id,
            $request->boolean('force', true),
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => $check->success,
                'error' => $check->error,
                'total_orders' => $check->total_orders,
                'total_delivered' => $check->total_delivered,
                'total_cancelled' => $check->total_cancelled,
                'success_rate' => $check->success_rate,
                'risk_level' => $check->risk_level,
                'style' => $check->getRiskStyle(),
                'breakdown' => $check->breakdown,
                'checked_at' => $check->created_at->diffForHumans(),
            ]);
        }

        return back()->with($check->success ? 'success' : 'error',
            $check->success
                ? 'Courier fraud check complete: ' . $check->getRiskStyle()['label']
                : 'Courier fraud check failed: ' . $check->error
        );
    }
}
