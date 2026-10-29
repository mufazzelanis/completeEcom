<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\StockAlert;
use App\Support\PhoneValidator;
use Illuminate\Http\Request;

/** POST /products/{product}/notify — "Notify me when it's back in stock". JSON in and out. */
class StockAlertController extends Controller
{
    public function store(Request $request, Product $product)
    {
        if (ActivityLog::automationSignal($request)) {
            return response()->json(['message' => 'Thanks!']); // bots get a polite no-op
        }

        $phone = PhoneValidator::normalize((string) $request->input('phone'));
        if (!$phone || ($problem = PhoneValidator::problem($phone))) {
            return response()->json(['message' => $problem ?? 'Please enter a valid mobile number, e.g. 01XXXXXXXXX.'], 422);
        }
        if ($product->available_stock > 0) {
            return response()->json(['message' => 'Good news — it\'s in stock right now! Refresh the page to order.', 'in_stock' => true], 409);
        }

        StockAlert::firstOrCreate(
            ['product_id' => $product->id, 'phone' => $phone, 'status' => 'waiting'],
            ['user_id' => auth()->id(), 'ip_address' => $request->ip()]
        );

        return response()->json(['message' => "Done! We'll let you know at {$phone} as soon as it's back in stock."]);
    }
}
