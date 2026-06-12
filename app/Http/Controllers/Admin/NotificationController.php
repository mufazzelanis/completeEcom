<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $stats = [
            'email'     => NotificationLog::where('channel', 'email')->count(),
            'sms'       => NotificationLog::where('channel', 'sms')->count(),
            'push'      => NotificationLog::where('channel', 'push')->count(),
            'whatsapp'  => NotificationLog::where('channel', 'whatsapp')->count(),
            'failed'    => NotificationLog::where('status', 'failed')->count(),
            'today'     => NotificationLog::whereDate('sent_at', today())->count(),
        ];

        $recentLogs = NotificationLog::with('user')
            ->latest('sent_at')
            ->take(20)
            ->get();

        return view('admin.notifications.index', compact('stats', 'recentLogs'));
    }

    public function templates()
    {
        $templates = NotificationTemplate::orderBy('event_type')->orderBy('channel')->get();
        $eventTypes = array_keys(config('notifications.events', []));
        $channels   = config('notifications.channels', []);

        return view('admin.notifications.templates', compact('templates', 'eventTypes', 'channels'));
    }

    public function createTemplate()
    {
        $eventTypes = array_keys(config('notifications.events', []));
        $channels   = config('notifications.channels', []);

        return view('admin.notifications.template-form', compact('eventTypes', 'channels'));
    }

    public function storeTemplate(Request $request)
    {
        $data = $request->validate([
            'event_type' => 'required|string|max:60',
            'channel'    => 'required|in:email,sms,push,whatsapp',
            'recipient'  => 'required|in:customer,admin',
            'subject'    => 'nullable|string|max:255',
            'body'       => 'required|string',
            'push_title' => 'nullable|string|max:255',
            'is_active'  => 'boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        NotificationTemplate::create($data);

        return redirect()->route('admin.notifications.templates')->with('success', 'Template created.');
    }

    public function editTemplate(NotificationTemplate $template)
    {
        $eventTypes = array_keys(config('notifications.events', []));
        $channels   = config('notifications.channels', []);

        return view('admin.notifications.template-form', compact('template', 'eventTypes', 'channels'));
    }

    public function updateTemplate(Request $request, NotificationTemplate $template)
    {
        $data = $request->validate([
            'subject'    => 'nullable|string|max:255',
            'body'       => 'required|string',
            'push_title' => 'nullable|string|max:255',
            'is_active'  => 'boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        $template->update($data);

        return redirect()->route('admin.notifications.templates')->with('success', 'Template updated.');
    }

    public function destroyTemplate(NotificationTemplate $template)
    {
        $template->delete();
        return back()->with('success', 'Template deleted.');
    }

    public function logs(Request $request)
    {
        $query = NotificationLog::with('user')->latest('sent_at');

        if ($request->filled('channel')) {
            $query->where('channel', $request->channel);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('event')) {
            $query->where('event_type', $request->event);
        }

        $logs = $query->paginate(50);

        return view('admin.notifications.logs', compact('logs'));
    }

    public function settings()
    {
        return view('admin.notifications.settings');
    }
}
