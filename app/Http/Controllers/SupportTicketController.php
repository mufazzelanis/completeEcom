<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\UserNotification;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Http\Request;

class SupportTicketController extends Controller
{
    public function index()
    {
        $tickets = SupportTicket::where('user_id', auth()->id())
            ->latest()
            ->paginate(10);
        return view('account.support.index', compact('tickets'));
    }

    public function create()
    {
        $orders = Order::where('user_id', auth()->id())->latest()->take(20)->get(['id', 'order_number', 'created_at']);
        return view('account.support.create', compact('orders'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject'  => 'required|string|max:255',
            'category' => 'required|in:order,payment,product,delivery,account,other',
            'priority' => 'required|in:low,medium,high,urgent',
            'order_id' => 'nullable|exists:orders,id',
            'message'  => 'required|string|min:10|max:2000',
        ]);

        // Verify the order belongs to the user
        if (!empty($data['order_id'])) {
            abort_unless(Order::where('id', $data['order_id'])->where('user_id', auth()->id())->exists(), 403);
        }

        $ticket = SupportTicket::create([
            'user_id'  => auth()->id(),
            'order_id' => $data['order_id'] ?? null,
            'subject'  => $data['subject'],
            'category' => $data['category'],
            'priority' => $data['priority'],
        ]);

        SupportTicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id'   => auth()->id(),
            'is_staff'  => false,
            'message'   => $data['message'],
        ]);

        NotificationDispatcher::admin('new_ticket', [
            'ticket_number' => $ticket->ticket_number,
            'subject'       => $ticket->subject,
            'customer'      => auth()->user()->name,
        ]);

        return redirect()->route('account.support.show', $ticket)->with('success', 'Ticket submitted. We\'ll respond soon.');
    }

    public function show(SupportTicket $ticket)
    {
        abort_unless($ticket->user_id === auth()->id(), 403);
        $ticket->load('messages.user', 'order');
        return view('account.support.show', compact('ticket'));
    }

    public function reply(Request $request, SupportTicket $ticket)
    {
        abort_unless($ticket->user_id === auth()->id(), 403);
        abort_unless(in_array($ticket->status, ['open', 'pending']), 422, 'Ticket is closed.');

        $request->validate(['message' => 'required|string|min:5|max:2000']);

        SupportTicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id'   => auth()->id(),
            'is_staff'  => false,
            'message'   => $request->message,
        ]);

        $ticket->increment('reply_count');
        $ticket->update(['last_reply_at' => now(), 'status' => 'open']);

        NotificationDispatcher::admin('ticket_reply_received', [
            'ticket_number' => $ticket->ticket_number,
            'subject'       => $ticket->subject,
            'customer'      => auth()->user()->name,
        ]);

        return back()->with('success', 'Reply sent.');
    }

    public function close(SupportTicket $ticket)
    {
        abort_unless($ticket->user_id === auth()->id(), 403);
        $ticket->update(['status' => 'closed']);
        return back()->with('success', 'Ticket closed.');
    }
}
