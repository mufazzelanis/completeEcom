<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Review;
use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AccountController extends Controller
{
    public function dashboard()
    {
        $user = auth()->user();

        $stats = [
            'orders'        => Order::where('user_id', $user->id)->count(),
            'pending'       => Order::where('user_id', $user->id)->whereIn('status', ['pending','processing'])->count(),
            'wishlist'      => $user->wishlists()->count(),
            'reviews'       => Review::where('user_id', $user->id)->count(),
            'unread_notifs' => UserNotification::where('user_id', $user->id)->where('is_read', false)->count(),
        ];

        $recentOrders = Order::where('user_id', $user->id)->with('items.product')->latest()->take(5)->get();
        $notifications = UserNotification::where('user_id', $user->id)->latest()->take(5)->get();

        return view('account.dashboard', compact('stats', 'recentOrders', 'notifications'));
    }

    public function profileEdit()
    {
        return view('account.profile', ['user' => auth()->user()]);
    }

    public function profileUpdate(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'name'          => 'required|string|max:100',
            'phone'         => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date|before:today',
            'gender'        => 'nullable|in:male,female,other',
            'bio'           => 'nullable|string|max:500',
            'avatar'        => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('avatar')) {
            if ($user->avatar) Storage::delete($user->avatar);
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        } else {
            unset($data['avatar']);
        }

        $user->update($data);
        return back()->with('success', 'Profile updated successfully.');
    }

    public function passwordUpdate(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password'         => 'required|min:8|confirmed',
        ]);

        $user = auth()->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $user->update(['password' => $request->password]);
        return back()->with('success', 'Password changed successfully.');
    }
}
