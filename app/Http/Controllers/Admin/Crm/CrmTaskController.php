<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Crm\CrmContact;
use App\Models\Crm\CrmTask;
use App\Services\Crm\CrmContacts;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CrmTaskController extends Controller
{
    public function index(Request $request)
    {
        $view = $request->input('view', 'mine');
        $q = CrmTask::query()->with(['contact:id,name,phone', 'lead:id,name', 'assignee:id,name']);

        match ($view) {
            'all' => $q->open(),
            'overdue' => $q->open()->where('due_at', '<', now()),
            'today' => $q->open()->whereBetween('due_at', [now()->startOfDay(), now()->endOfDay()]),
            'done' => $q->where('status', 'done')->where('completed_at', '>=', now()->subDays(30)),
            default => $q->open()->where('assigned_to', $request->user()->id),
        };
        if ($request->filled('assignee')) {
            $request->input('assignee') === 'none' ? $q->whereNull('assigned_to') : $q->where('assigned_to', (int) $request->input('assignee'));
        }

        $q = $view === 'done' ? $q->orderByDesc('completed_at') : $q->orderByRaw('due_at IS NULL')->orderBy('due_at');

        $base = CrmTask::open();
        $counts = [
            'mine' => (clone $base)->where('assigned_to', $request->user()->id)->count(),
            'all' => (clone $base)->count(),
            'overdue' => (clone $base)->where('due_at', '<', now())->count(),
            'today' => (clone $base)->whereBetween('due_at', [now()->startOfDay(), now()->endOfDay()])->count(),
        ];

        return view('admin.crm.tasks.index', [
            'tasks' => $q->paginate(30)->withQueryString(),
            'view' => $view,
            'counts' => $counts,
            'staff' => CrmContacts::staff(),
            'prefillContact' => $request->filled('contact') ? CrmContact::find((int) $request->input('contact'), ['id', 'name']) : null,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $task = CrmTask::create($data + ['created_by' => $request->user()->id, 'status' => 'open']);

        return back()->with('success', 'Task added' . ($task->due_at ? ' — due ' . $task->due_at->format('d M, h:i A') : '') . '.');
    }

    public function update(Request $request, CrmTask $task)
    {
        $task->update($this->validated($request) + ['reminded_at' => null]);

        return back()->with('success', 'Task updated.');
    }

    public function complete(Request $request, CrmTask $task)
    {
        $task->update(['status' => 'done', 'completed_at' => now(), 'completed_by' => $request->user()->id]);
        if ($task->contact_id) {
            $task->contact?->activities()->create([
                'user_id' => $request->user()->id, 'type' => 'note', 'subject' => 'Task done: ' . $task->title,
                'meta' => ['system' => true], 'occurred_at' => now(),
            ]);
        }

        return $request->wantsJson() ? response()->json(['ok' => true]) : back()->with('success', 'Task completed.');
    }

    public function reopen(CrmTask $task)
    {
        $task->update(['status' => 'open', 'completed_at' => null, 'completed_by' => null]);

        return back()->with('success', 'Task reopened.');
    }

    public function destroy(CrmTask $task)
    {
        $task->delete();

        return back()->with('success', 'Task deleted.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:250',
            'description' => 'nullable|string|max:3000',
            'type' => ['required', Rule::in(array_keys(CrmTask::TYPES))],
            'priority' => ['required', Rule::in(array_keys(CrmTask::PRIORITIES))],
            'due_at' => 'nullable|date',
            'assigned_to' => ['nullable', 'integer', Rule::in(CrmContacts::staff()->pluck('id')->all())],
            'contact_id' => 'nullable|integer|exists:crm_contacts,id',
            'lead_id' => 'nullable|integer|exists:crm_leads,id',
        ]);

        return $data + ['description' => null, 'due_at' => null, 'assigned_to' => null, 'contact_id' => null, 'lead_id' => null];
    }
}
