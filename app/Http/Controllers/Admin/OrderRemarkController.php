<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderRemark;
use App\Support\OrderTimeline;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Order remarks (quick view drawer + order page): add, pin, delete. Every response carries the
 * re-rendered remarks list plus the count / latest line / call-back for the orders list badge,
 * so the page never reloads.
 */
class OrderRemarkController extends Controller
{
    public function store(Request $request, Order $order)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(OrderRemark::TYPES))],
            'outcome' => ['nullable', Rule::in(array_keys(OrderRemark::OUTCOMES))],
            'body' => 'nullable|string|max:1000',
            'follow_up_at' => 'nullable|date',
        ]);
        if (blank($data['body'] ?? null) && blank($data['outcome'] ?? null)) {
            return response()->json(['message' => 'Write a remark or pick a call result.'], 422);
        }
        $followUp = filled($data['follow_up_at'] ?? null)
            ? Carbon::parse($data['follow_up_at'], setting('timezone', 'Asia/Dhaka'))->utc()
            : null;

        $order->remarks()->create([
            'user_id' => auth()->id(),
            'type' => $data['type'],
            'outcome' => $data['type'] === 'call' ? ($data['outcome'] ?? null) : null,
            'body' => filled($data['body'] ?? null) ? trim($data['body']) : null,
            'follow_up_at' => ($data['outcome'] ?? null) === 'call_later' ? $followUp : null,
        ]);

        return response()->json($this->payload($order) + ['message' => 'Remark added.']);
    }

    public function pin(OrderRemark $remark)
    {
        $remark->update(['is_pinned' => ! $remark->is_pinned]);

        return response()->json($this->payload($remark->order) + ['message' => $remark->is_pinned ? 'Pinned to the top.' : 'Unpinned.']);
    }

    public function destroy(OrderRemark $remark)
    {
        // your own remarks, or any if you're an admin
        abort_unless($remark->user_id === auth()->id() || auth()->user()->isAdmin(), 403, 'You can only delete your own remarks.');
        $order = $remark->order;
        $remark->delete();

        return response()->json($this->payload($order) + ['message' => 'Remark deleted.']);
    }

    /** List HTML + what the orders-list row shows (count, latest line, call-back). */
    private function payload(Order $order): array
    {
        $timeline = OrderTimeline::for($order);
        $latest = $order->remarks()->with('user:id,name')->first();

        return [
            'html' => view('admin.orders._remarks-list', ['order' => $order] + $timeline)->render(),
            'count' => $timeline['count'],
            'latest' => $latest?->summary(),
            'line' => $latest?->listLine(), // the order lists' last-remark line (_remark-line)
            'followUp' => $latest?->outcome === 'call_later' && $latest->follow_up_at
                ? $latest->follow_up_at->timezone(setting('timezone', 'Asia/Dhaka'))->format('d M, g:i A') : null,
        ];
    }
}
