<?php

namespace App\Http\Controllers;

use App\Models\NotificationPreference;
use App\Models\UserNotification;
use Illuminate\Http\Request;

class CustomerNotificationController extends Controller
{
    public function index()
    {
        $notifications = UserNotification::where('user_id', auth()->id())
            ->latest()
            ->paginate(20);

        // Mark all as read on visit
        UserNotification::where('user_id', auth()->id())
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return view('account.notifications.index', compact('notifications'));
    }

    public function markRead(UserNotification $notification)
    {
        abort_unless($notification->user_id === auth()->id(), 403);
        $notification->update(['is_read' => true, 'read_at' => now()]);
        return response()->json(['ok' => true]);
    }

    public function markAllRead()
    {
        UserNotification::where('user_id', auth()->id())
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);
        return back()->with('success', 'All notifications marked as read.');
    }

    public function preferences()
    {
        $prefs = NotificationPreference::forUser(auth()->id());
        return view('account.notifications.preferences', compact('prefs'));
    }

    public function updatePreferences(Request $request)
    {
        $channels = ['email', 'sms', 'push', 'whatsapp'];
        $groups   = ['order', 'return', 'ticket', 'promo'];
        $data     = [];

        foreach ($channels as $channel) {
            foreach ($groups as $group) {
                $data["{$channel}_{$group}"] = $request->boolean("{$channel}_{$group}");
            }
        }

        NotificationPreference::updateOrCreate(
            ['user_id' => auth()->id()],
            $data
        );

        return back()->with('success', 'Notification preferences saved.');
    }
}
