<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use Illuminate\Http\Request;

class VendorRegistrationController extends Controller
{
    public function create()
    {
        $vendor = auth()->user()->vendor;

        return view('vendor-registration.create', compact('vendor'));
    }

    public function store(Request $request)
    {
        if (auth()->user()->vendor) {
            return redirect()->route('vendor.apply')->with('error', 'You have already applied to become a seller.');
        }

        $request->validate([
            'business_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'description' => 'nullable|string|max:2000',
        ]);

        Vendor::create($request->only(['business_name', 'phone', 'email', 'description']) + [
            'user_id' => auth()->id(),
            'status' => 'pending',
        ]);

        return redirect()->route('vendor.apply')->with('success', 'Your seller application has been submitted and is pending review.');
    }
}
