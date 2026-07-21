<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Http\Request;

class SupportTicketController extends Controller
{
    public function index(Request $request)
    {
        $query = SupportTicket::with('user')->latest('last_reply_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($qr) use ($q) {
                $qr->where('ticket_number', 'like', "%$q%")
                   ->orWhere('subject', 'like', "%$q%")
                   ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%$q%"));
            });
        }

        $tickets = $query->paginate(20)->withQueryString();

        $stats = [
            'open'    => SupportTicket::where('status', 'open')->count(),
            'pending' => SupportTicket::where('status', 'pending')->count(),
            'total'   => SupportTicket::count(),
        ];

        return view('admin.support_tickets.index', compact('tickets', 'stats'));
    }

    public function show(SupportTicket $ticket)
    {
        $ticket->load('messages.user', 'user', 'order');
        return view('admin.support_tickets.show', compact('ticket'));
    }

    public function reply(Request $request, SupportTicket $ticket)
    {
        $request->validate(['message' => 'required|string|min:1|max:2000']);

        SupportTicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id'   => auth()->id(),
            'is_staff'  => true,
            'message'   => $request->message,
        ]);

        $ticket->increment('reply_count');
        $ticket->update(['last_reply_at' => now(), 'status' => 'pending']);

        NotificationDispatcher::customer('ticket_replied', $ticket->user, [
            'ticket_number' => $ticket->ticket_number,
            'subject'       => $ticket->subject,
            'url'           => route('account.support.show', $ticket),
        ]);

        return back()->with('success', 'Reply sent.');
    }

    public function updateStatus(Request $request, SupportTicket $ticket)
    {
        $request->validate(['status' => 'required|in:open,pending,resolved,closed']);

        $ticket->update(['status' => $request->status]);

        return back()->with('success', 'Ticket status updated.');
    }
}
