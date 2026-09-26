<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Crm\CrmActivity;
use App\Models\Crm\CrmContact;
use App\Models\Crm\CrmTag;
use App\Models\Crm\CrmTask;
use App\Services\Crm\CrmContacts;
use App\Services\Crm\CrmMetrics;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CrmContactController extends Controller
{
    public const SORTS = [
        'recent' => ['created_at', 'desc'],
        'name' => ['name', 'asc'],
        'spent' => ['total_spent', 'desc'],
        'orders' => ['orders_count', 'desc'],
        'last_order' => ['last_order_at', 'desc'],
        'churn' => ['churn_risk', 'desc'],
    ];

    /** One filter implementation shared by the list, CSV export and "apply to everything matching". */
    public static function filtered(Request $request): Builder
    {
        $q = CrmContact::query()->with(['tags:id,name,color', 'owner:id,name']);

        if ($s = trim((string) $request->input('q'))) {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $s) . '%';
            $digits = CrmContacts::normalizePhone($s);
            $q->where(function ($w) use ($like, $digits) {
                $w->where('name', 'like', $like)->orWhere('email', 'like', $like)->orWhere('phone', 'like', $like);
                if ($digits) {
                    $w->orWhere('phone', $digits);
                }
            });
        }
        foreach (['lifecycle_stage', 'rfm_segment', 'source', 'status'] as $f) {
            if ($request->filled($f)) {
                $q->where($f, $request->input($f));
            }
        }
        if ($request->filled('tag')) {
            $q->whereHas('tags', fn ($t) => $t->where('crm_tags.id', (int) $request->input('tag')));
        }
        if ($request->filled('owner')) {
            $request->input('owner') === 'none' ? $q->whereNull('owner_id') : $q->where('owner_id', (int) $request->input('owner'));
        }
        if ($request->filled('city')) {
            $q->where('city', $request->input('city'));
        }
        if ($request->boolean('vip')) {
            $q->where('is_vip', true);
        }
        if ($request->input('account') === 'yes') {
            $q->whereNotNull('user_id');
        } elseif ($request->input('account') === 'no') {
            $q->whereNull('user_id');
        }
        if (is_numeric($request->input('min_spent'))) {
            $q->where('total_spent', '>=', $request->input('min_spent'));
        }
        if (is_numeric($request->input('max_spent'))) {
            $q->where('total_spent', '<=', $request->input('max_spent'));
        }
        if (is_numeric($request->input('inactive_days'))) {
            $q->whereNotNull('last_order_at')->where('last_order_at', '<', now()->subDays((int) $request->input('inactive_days')));
        }
        if ($request->filled('segment')) {
            $segment = \App\Models\Crm\CrmSegment::find((int) $request->input('segment'));
            if ($segment) {
                app(\App\Services\Crm\CrmSegmentEngine::class)->forSegment($segment, $q);
            }
        }

        [$col, $dir] = self::SORTS[$request->input('sort')] ?? self::SORTS['recent'];
        $q->orderByRaw("$col IS NULL")->orderBy($col, $dir)->orderByDesc('id');

        return $q;
    }

    public function index(Request $request)
    {
        $contacts = self::filtered($request)->paginate(25)->withQueryString();

        return view('admin.crm.contacts.index', [
            'contacts' => $contacts,
            'tags' => CrmTag::orderBy('name')->get(),
            'staff' => CrmContacts::staff(),
            'cities' => CrmContact::whereNotNull('city')->where('city', '!=', '')->distinct()->orderBy('city')->limit(200)->pluck('city'),
            'segments' => \App\Models\Crm\CrmSegment::orderBy('name')->get(['id', 'name']),
            'rfmSegments' => array_keys(CrmMetrics::SEGMENT_INFO),
            'totals' => [
                'all' => CrmContact::count(),
                'customers' => CrmContact::where('orders_count', '>', 0)->count(),
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $query = self::filtered($request);

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel shows Bangla names correctly
            fputcsv($out, ['Name', 'Phone', 'Email', 'City', 'Lifecycle', 'RFM segment', 'Orders', 'Total spent', 'Avg order', 'Last order', 'Churn risk', 'VIP', 'Status', 'Source', 'Tags', 'Created']);
            $query->reorder()->orderBy('id')->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $c) {
                    // A cell starting with = + - @ would be run as a formula when opened in Excel.
                    $safe = fn ($v) => is_string($v) && preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : $v;
                    fputcsv($out, [
                        $safe($c->name), $c->phone, $safe($c->email), $safe($c->city), $c->lifecycle_stage, $c->rfm_segment,
                        $c->orders_count, $c->total_spent, $c->avg_order_value, optional($c->last_order_at)->toDateString(),
                        $c->churn_risk, $c->is_vip ? 'yes' : 'no', $c->status, $c->source,
                        $safe($c->tags->pluck('name')->implode(', ')), $c->created_at->toDateString(),
                    ]);
                }
            });
            fclose($out);
        }, 'crm-contacts-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function show(CrmContact $contact)
    {
        $contact->load(['tags', 'owner:id,name', 'user:id,name,email,created_at', 'leads']);
        $orders = $contact->orders()->withCount('items')->latest()->limit(50)->get();

        // One merged, newest-first timeline of everything that ever happened with this person.
        $timeline = collect();
        foreach ($contact->activities()->with('user:id,name')->latest('occurred_at')->limit(200)->get() as $a) {
            $timeline->push(['kind' => 'activity', 'at' => $a->occurred_at, 'model' => $a]);
        }
        foreach ($orders as $o) {
            $timeline->push(['kind' => 'order', 'at' => $o->created_at, 'model' => $o]);
        }
        $tasks = $contact->tasks()->with('assignee:id,name')->orderByRaw("status = 'open' desc")->orderBy('due_at')->get();
        foreach ($tasks->where('status', 'done') as $t) {
            $timeline->push(['kind' => 'task', 'at' => $t->completed_at ?? $t->updated_at, 'model' => $t]);
        }
        foreach ($contact->leads as $l) {
            $timeline->push(['kind' => 'lead', 'at' => $l->created_at, 'model' => $l]);
        }
        $timeline = $timeline->sortByDesc(fn ($i) => $i['at']?->timestamp ?? 0)->values();

        $topProducts = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.crm_contact_id', $contact->id)
            ->whereNotIn('orders.status', CrmMetrics::EXCLUDED_STATUSES)
            ->groupBy('order_items.product_name')
            ->orderByRaw('SUM(order_items.quantity) DESC')
            ->limit(5)
            ->get(['order_items.product_name as name', DB::raw('SUM(order_items.quantity) as qty')]);

        return view('admin.crm.contacts.show', [
            'contact' => $contact,
            'orders' => $orders,
            'timeline' => $timeline,
            'tasks' => $tasks,
            'topProducts' => $topProducts,
            'allTags' => CrmTag::orderBy('name')->get(),
            'staff' => CrmContacts::staff(),
            'coupons' => Coupon::where('is_active', true)->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->orderBy('code')->limit(50)->get(['id', 'code', 'type', 'value']),
            'segmentInfo' => CrmMetrics::SEGMENT_INFO[$contact->rfm_segment] ?? null,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        if (! $data['phone'] && ! $data['email']) {
            return back()->withInput()->withErrors(['phone' => 'Give at least a phone number or an e-mail so the contact can be identified.']);
        }

        // Re-use an existing person rather than create a duplicate; tell staff that happened.
        $contact = app(CrmContacts::class)->resolve($data, 'manual');
        $existing = ! $contact->wasRecentlyCreated;
        $contact->update(array_filter([
            'birthday' => $data['birthday'] ?? null, 'owner_id' => $data['owner_id'] ?? null,
        ]));
        if ($request->input('is_vip')) {
            $contact->update(['is_vip' => true]);
        }
        app(CrmMetrics::class)->refreshContact($contact);

        return redirect()->route('admin.crm.contacts.show', $contact)
            ->with('success', $existing ? 'That person already existed — opened their profile instead of creating a duplicate.' : 'Contact created.');
    }

    public function update(Request $request, CrmContact $contact)
    {
        $data = $this->validated($request, $contact);
        $contact->update([
            'name' => $data['name'], 'phone' => $data['phone'], 'email' => $data['email'], 'city' => $data['city'],
            'birthday' => $data['birthday'], 'owner_id' => $data['owner_id'],
            'is_vip' => $request->boolean('is_vip'),
            'status' => in_array($request->input('status'), ['active', 'blocked', 'do_not_contact']) ? $request->input('status') : $contact->status,
        ]);

        return back()->with('success', 'Contact updated.');
    }

    public function destroy(CrmContact $contact)
    {
        if ($contact->orders()->exists()) {
            return back()->with('error', 'This contact has orders, so deleting would erase their purchase history from the CRM. Mark them "Do not contact" or blocked instead.');
        }
        $contact->delete();

        return redirect()->route('admin.crm.contacts.index')->with('success', 'Contact deleted.');
    }

    public function refresh(CrmContact $contact)
    {
        app(CrmMetrics::class)->refreshContact($contact);

        return back()->with('success', 'Metrics recalculated.');
    }

    public function tags(Request $request, CrmContact $contact)
    {
        $names = collect(explode(',', (string) $request->input('tags')))
            ->map(fn ($n) => mb_substr(trim($n), 0, 60))->filter()->unique(fn ($n) => mb_strtolower($n))->take(30);

        $ids = $names->map(fn ($n) => CrmTag::firstOrCreate(['name' => $n], ['color' => CrmTag::COLORS[crc32($n) % count(CrmTag::COLORS)]])->id);
        $contact->tags()->sync($ids->all());

        return back()->with('success', 'Tags saved.');
    }

    public function logActivity(Request $request, CrmContact $contact)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(CrmActivity::TYPES))],
            'direction' => ['nullable', Rule::in(['in', 'out'])],
            'subject' => 'nullable|string|max:200',
            'body' => 'nullable|string|max:5000',
            'outcome' => ['nullable', Rule::in(array_keys(CrmActivity::OUTCOMES))],
            'occurred_at' => 'nullable|date',
            'follow_up_at' => 'nullable|date|after:now',
        ]);
        if (blank($data['subject'] ?? null) && blank($data['body'] ?? null)) {
            $msg = ['subject' => 'Write a note or a subject.'];
            return $request->wantsJson() ? response()->json(['message' => 'Write a note or a subject.', 'errors' => $msg], 422) : back()->withErrors($msg)->withInput();
        }

        $activity = $contact->activities()->create([
            'user_id' => $request->user()->id,
            'type' => $data['type'],
            'direction' => $data['direction'] ?? ($data['type'] === 'note' ? null : 'out'),
            'subject' => $data['subject'] ?? null,
            'body' => $data['body'] ?? null,
            'outcome' => $data['outcome'] ?? null,
            'occurred_at' => $data['occurred_at'] ?? now(),
        ]);
        if ($data['type'] !== 'note') {
            $contact->forceFill(['last_contacted_at' => $activity->occurred_at])->saveQuietly();
        }
        if (! empty($data['follow_up_at'])) {
            CrmTask::create([
                'contact_id' => $contact->id, 'assigned_to' => $request->user()->id, 'created_by' => $request->user()->id,
                'title' => 'Follow up with ' . $contact->name, 'type' => $data['type'] === 'note' ? 'follow_up' : $data['type'],
                'due_at' => $data['follow_up_at'],
            ]);
        }

        return $request->wantsJson() ? response()->json(['ok' => true, 'id' => $activity->id]) : back()->with('success', 'Logged.');
    }

    public function pinActivity(CrmActivity $activity)
    {
        $activity->update(['is_pinned' => ! $activity->is_pinned]);

        return back();
    }

    public function destroyActivity(CrmActivity $activity)
    {
        if (($activity->meta['system'] ?? false)) {
            return back()->with('error', 'System entries are part of the audit trail and cannot be deleted.');
        }
        $activity->delete();

        return back()->with('success', 'Entry removed.');
    }

    /** Bulk actions from the directory: either the ticked rows or everything matching the current filters. */
    public function bulk(Request $request)
    {
        $request->validate([
            'action' => ['required', Rule::in(['tag', 'untag', 'owner', 'vip', 'unvip', 'status', 'delete_empty'])],
            'scope' => ['required', Rule::in(['selected', 'filtered'])],
            'ids' => 'array|max:2000', 'ids.*' => 'integer',
        ]);

        $query = $request->input('scope') === 'filtered'
            ? self::filtered($request)->reorder()
            : CrmContact::whereIn('id', $request->input('ids', []));

        $ids = (clone $query)->pluck('crm_contacts.id');
        if ($ids->isEmpty()) {
            return back()->with('error', 'No contacts selected.');
        }
        if ($ids->count() > 5000) {
            return back()->with('error', 'That would touch more than 5,000 contacts at once. Narrow the filters first.');
        }

        $n = $ids->count();
        switch ($request->input('action')) {
            case 'tag':
            case 'untag':
                $name = mb_substr(trim((string) $request->input('tag_name')), 0, 60);
                if ($name === '') {
                    return back()->with('error', 'Pick or type a tag first.');
                }
                $tag = CrmTag::firstOrCreate(['name' => $name], ['color' => CrmTag::COLORS[crc32($name) % count(CrmTag::COLORS)]]);
                if ($request->input('action') === 'tag') {
                    foreach ($ids->chunk(500) as $chunk) {
                        DB::table('crm_contact_tag')->insertOrIgnore($chunk->map(fn ($id) => ['contact_id' => $id, 'tag_id' => $tag->id])->all());
                    }
                } else {
                    DB::table('crm_contact_tag')->where('tag_id', $tag->id)->whereIn('contact_id', $ids)->delete();
                }
                $msg = "Tag \"{$name}\" " . ($request->input('action') === 'tag' ? 'added to' : 'removed from') . " {$n} contact(s).";
                break;
            case 'owner':
                $owner = $request->input('owner_id') ? (int) $request->input('owner_id') : null;
                if ($owner && ! CrmContacts::staff()->contains('id', $owner)) {
                    return back()->with('error', 'That user cannot own contacts.');
                }
                CrmContact::whereIn('id', $ids)->update(['owner_id' => $owner]);
                $msg = "Owner updated for {$n} contact(s).";
                break;
            case 'vip':
            case 'unvip':
                CrmContact::whereIn('id', $ids)->update(['is_vip' => $request->input('action') === 'vip']);
                $msg = "VIP flag updated for {$n} contact(s).";
                break;
            case 'status':
                $st = in_array($request->input('new_status'), ['active', 'blocked', 'do_not_contact']) ? $request->input('new_status') : null;
                if (! $st) {
                    return back()->with('error', 'Pick a status.');
                }
                CrmContact::whereIn('id', $ids)->update(['status' => $st]);
                $msg = "Status set to {$st} for {$n} contact(s).";
                break;
            default: // delete_empty — only contacts with no orders, never purchase history
                $deletable = CrmContact::whereIn('id', $ids)->where('orders_count', 0)->whereDoesntHave('orders')->pluck('id');
                CrmContact::whereIn('id', $deletable)->delete();
                $msg = $deletable->count() . ' contact(s) deleted' . ($deletable->count() < $n ? ' (' . ($n - $deletable->count()) . ' skipped because they have orders).' : '.');
        }

        return back()->with('success', $msg);
    }

    private function validated(Request $request, ?CrmContact $contact = null): array
    {
        $request->merge([
            'phone' => CrmContacts::normalizePhone($request->input('phone')),
            'email' => CrmContacts::normalizeEmail($request->input('email')) ?: null,
        ]);

        $data = $request->validate([
            'name' => 'required|string|max:250',
            'phone' => ['nullable', 'string', 'max:20', Rule::unique('crm_contacts', 'phone')->ignore($contact?->id)],
            'email' => 'nullable|email|max:255',
            'city' => 'nullable|string|max:100',
            'birthday' => 'nullable|date|before:today',
            'owner_id' => ['nullable', 'integer', Rule::in(CrmContacts::staff()->pluck('id')->all())],
        ], [
            'phone.unique' => 'Another contact already has this phone number.',
        ]);

        return $data + ['phone' => null, 'email' => null, 'city' => null, 'birthday' => null, 'owner_id' => null];
    }
}
