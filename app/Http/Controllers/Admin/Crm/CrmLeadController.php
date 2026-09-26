<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Crm\CrmLead;
use App\Services\Crm\CrmContacts;
use App\Services\Crm\CrmMetrics;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CrmLeadController extends Controller
{
    public function index(Request $request)
    {
        $q = CrmLead::query()->with(['owner:id,name', 'contact:id,name']);
        if ($request->filled('owner')) {
            $q->where('owner_id', (int) $request->input('owner'));
        }
        if ($request->filled('source') && isset(CrmLead::SOURCES[$request->input('source')])) {
            $q->where('source', $request->input('source'));
        }
        if ($s = trim((string) $request->input('q'))) {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $s) . '%';
            $q->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('phone', 'like', $like)->orWhere('email', 'like', $like)->orWhere('company', 'like', $like));
        }

        // Closed columns only show the last 30 days so the board stays a working surface, not an archive.
        $q->where(fn ($w) => $w->whereNotIn('stage', ['won', 'lost'])->orWhere('updated_at', '>=', now()->subDays(30)));

        $leads = $q->orderBy('position')->orderByDesc('id')->get()->groupBy('stage');

        return view('admin.crm.leads.index', [
            'leads' => $leads,
            'staff' => CrmContacts::staff(),
        ]);
    }

    public function show(CrmLead $lead)
    {
        $lead->load(['owner:id,name', 'contact', 'activities' => fn ($q) => $q->with('user:id,name')->latest('occurred_at'), 'tasks' => fn ($q) => $q->with('assignee:id,name')->orderByRaw("status = 'open' desc")->orderBy('due_at')]);

        return view('admin.crm.leads.show', ['lead' => $lead, 'staff' => CrmContacts::staff()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $lead = CrmLead::create($data + [
            'stage' => 'new',
            'position' => (int) CrmLead::where('stage', 'new')->max('position') + 1,
        ]);
        $this->linkContact($lead);

        return $request->wantsJson()
            ? response()->json(['ok' => true, 'id' => $lead->id])
            : redirect()->route('admin.crm.leads.index')->with('success', 'Lead added.');
    }

    public function update(Request $request, CrmLead $lead)
    {
        $lead->update($this->validated($request));
        $this->linkContact($lead);

        return back()->with('success', 'Lead updated.');
    }

    /** Drag-and-drop on the board. Returns JSON so the card can snap back if the server refuses. */
    public function move(Request $request, CrmLead $lead)
    {
        // Explicit JSON 422: bootstrap/app.php only renders JSON errors for api/*, so a plain validate() would redirect here.
        $v = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'stage' => ['required', Rule::in(array_keys(CrmLead::STAGES))],
            'position' => 'nullable|integer|min:0',
            'lost_reason' => 'nullable|string|max:200',
        ]);
        if ($v->fails()) {
            return response()->json(['message' => $v->errors()->first(), 'errors' => $v->errors()], 422);
        }
        $data = $v->validated();

        $from = $lead->stage;
        $lead->stage = $data['stage'];
        $lead->position = $data['position'] ?? (int) CrmLead::where('stage', $data['stage'])->max('position') + 1;
        $lead->won_at = $data['stage'] === 'won' ? ($lead->won_at ?? now()) : null;
        $lead->lost_at = $data['stage'] === 'lost' ? ($lead->lost_at ?? now()) : null;
        if ($data['stage'] === 'lost' && ! empty($data['lost_reason'])) {
            $lead->lost_reason = $data['lost_reason'];
        }
        $lead->save();

        if ($from !== $data['stage']) {
            $lead->activities()->create([
                'contact_id' => $lead->contact_id, 'user_id' => $request->user()->id, 'type' => 'note',
                'subject' => 'Stage: ' . CrmLead::STAGES[$from]['label'] . ' → ' . CrmLead::STAGES[$data['stage']]['label'],
                'meta' => ['system' => true], 'occurred_at' => now(),
            ]);
        }

        return response()->json(['ok' => true, 'stage' => $lead->stage]);
    }

    /** Turn a lead into a real customer record (or attach it to the one that already exists). */
    public function convert(Request $request, CrmLead $lead)
    {
        $contact = app(CrmContacts::class)->resolve(['name' => $lead->name, 'phone' => $lead->phone, 'email' => $lead->email], 'lead');
        if (! $contact) {
            return back()->with('error', 'This lead has no usable phone or e-mail, so no customer profile can be created. Add one first.');
        }
        $lead->update(['contact_id' => $contact->id, 'stage' => 'won', 'won_at' => $lead->won_at ?? now(), 'lost_at' => null]);
        $lead->activities()->where('contact_id', null)->update(['contact_id' => $contact->id]);
        app(CrmContacts::class)->logSystem($contact, 'Converted from lead', $lead->interest ? mb_substr($lead->interest, 0, 300) : null, ['lead_id' => $lead->id]);
        app(CrmMetrics::class)->refreshContact($contact);

        return redirect()->route('admin.crm.contacts.show', $contact)->with('success', 'Lead won and saved as a customer profile.');
    }

    public function logActivity(Request $request, CrmLead $lead)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['note', 'call', 'email', 'whatsapp', 'meeting', 'sms'])],
            'subject' => 'nullable|string|max:200',
            'body' => 'required|string|max:5000',
        ]);
        $lead->activities()->create($data + ['contact_id' => $lead->contact_id, 'user_id' => $request->user()->id, 'occurred_at' => now(), 'direction' => $data['type'] === 'note' ? null : 'out']);

        return back()->with('success', 'Logged.');
    }

    public function destroy(CrmLead $lead)
    {
        $lead->delete();

        return redirect()->route('admin.crm.leads.index')->with('success', 'Lead deleted.');
    }

    private function validated(Request $request): array
    {
        $request->merge([
            'phone' => CrmContacts::normalizePhone($request->input('phone')),
            'email' => CrmContacts::normalizeEmail($request->input('email')) ?: null,
        ]);

        return $request->validate([
            'name' => 'required|string|max:250',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'company' => 'nullable|string|max:200',
            'source' => ['required', Rule::in(array_keys(CrmLead::SOURCES))],
            'value' => 'nullable|numeric|min:0|max:99999999',
            'expected_close_on' => 'nullable|date',
            'interest' => 'nullable|string|max:5000',
            'owner_id' => ['nullable', 'integer', Rule::in(CrmContacts::staff()->pluck('id')->all())],
        ]) + ['phone' => null, 'email' => null, 'company' => null, 'value' => 0, 'expected_close_on' => null, 'interest' => null, 'owner_id' => null];
    }

    /** If the person is already a known customer, hook the lead onto that profile so history is shared. */
    private function linkContact(CrmLead $lead): void
    {
        if ($lead->contact_id) {
            return;
        }
        $phone = CrmContacts::normalizePhone($lead->phone);
        $email = CrmContacts::normalizeEmail($lead->email);
        $match = \App\Models\Crm\CrmContact::query()
            ->where(fn ($q) => $q->when($phone, fn ($x) => $x->orWhere('phone', $phone))->when($email, fn ($x) => $x->orWhere('email', $email)))
            ->when(! $phone && ! $email, fn ($q) => $q->whereRaw('1 = 0'))
            ->first();
        if ($match) {
            $lead->update(['contact_id' => $match->id]);
        }
    }
}
