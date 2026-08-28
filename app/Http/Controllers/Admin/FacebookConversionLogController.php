<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FacebookConversionLog;

class FacebookConversionLogController extends Controller
{
    public function index()
    {
        $logs = FacebookConversionLog::query()
            ->when(request('event_name'), fn ($q) => $q->where('event_name', request('event_name')))
            ->when(request('status'), fn ($q) => $q->where('status', request('status')))
            ->orderByDesc('sent_at')
            ->paginate(50)
            ->withQueryString();

        $eventNames = FacebookConversionLog::query()->distinct()->orderBy('event_name')->pluck('event_name');

        $stats = [
            'sent_today'   => FacebookConversionLog::where('status', 'sent')->whereDate('sent_at', today())->count(),
            'failed_today' => FacebookConversionLog::where('status', 'failed')->whereDate('sent_at', today())->count(),
            'sent_total'   => FacebookConversionLog::where('status', 'sent')->count(),
            'last_sent_at' => FacebookConversionLog::max('sent_at'),
        ];

        return view('admin.facebook-conversion-logs.index', compact('logs', 'eventNames', 'stats'));
    }
}
