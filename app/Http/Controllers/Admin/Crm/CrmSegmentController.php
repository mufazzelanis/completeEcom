<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Crm\CrmSegment;
use App\Models\Crm\CrmTag;
use App\Services\Crm\CrmMetrics;
use App\Services\Crm\CrmSegmentEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CrmSegmentController extends Controller
{
    public function __construct(private CrmSegmentEngine $engine)
    {
    }

    public function index()
    {
        $segments = CrmSegment::orderBy('name')->get();
        $installed = $segments->pluck('name')->all();

        return view('admin.crm.segments.index', [
            'segments' => $segments,
            'presets' => collect(CrmSegmentEngine::presets())->reject(fn ($p) => in_array($p['name'], $installed))->values(),
        ]);
    }

    public function show(Request $request, CrmSegment $segment)
    {
        $contacts = $this->engine->forSegment($segment)->with('tags:id,name,color')->orderByDesc('total_spent')->paginate(25);
        $this->engine->recount($segment);

        return view('admin.crm.segments.show', ['segment' => $segment->refresh(), 'contacts' => $contacts]);
    }

    public function create()
    {
        return view('admin.crm.segments.form', $this->formData(new CrmSegment(['color' => 'indigo', 'match' => 'all', 'rules' => [['field' => 'orders_count', 'op' => 'gte', 'value' => 1]]])));
    }

    public function edit(CrmSegment $segment)
    {
        return view('admin.crm.segments.form', $this->formData($segment));
    }

    public function store(Request $request)
    {
        $segment = CrmSegment::create($this->validated($request) + ['created_by' => $request->user()->id]);
        $this->engine->recount($segment);

        return redirect()->route('admin.crm.segments.show', $segment)->with('success', 'Segment saved.');
    }

    public function update(Request $request, CrmSegment $segment)
    {
        $segment->update($this->validated($request));
        $this->engine->recount($segment);

        return redirect()->route('admin.crm.segments.show', $segment)->with('success', 'Segment updated.');
    }

    public function destroy(CrmSegment $segment)
    {
        $segment->delete();

        return redirect()->route('admin.crm.segments.index')->with('success', 'Segment deleted.');
    }

    /** Live "how many people match?" while building rules. */
    public function preview(Request $request)
    {
        $rules = CrmSegmentEngine::sanitize((array) $request->input('rules', []));
        $match = $request->input('match') === 'any' ? 'any' : 'all';
        $q = $this->engine->query($rules, $match);

        return response()->json([
            'count' => (clone $q)->count(),
            'rules' => count($rules),
            'sample' => $q->orderByDesc('total_spent')->limit(5)->get(['id', 'name', 'phone', 'total_spent', 'orders_count'])->map(fn ($c) => [
                'id' => $c->id, 'name' => $c->name, 'phone' => $c->phone, 'spent' => (float) $c->total_spent, 'orders' => $c->orders_count,
            ]),
        ]);
    }

    public function installPresets(Request $request)
    {
        $n = 0;
        foreach (CrmSegmentEngine::presets() as $p) {
            if ($request->filled('only') && $request->input('only') !== $p['name']) {
                continue;
            }
            if (CrmSegment::where('name', $p['name'])->exists()) {
                continue;
            }
            $segment = CrmSegment::create($p + ['created_by' => $request->user()->id]);
            $this->engine->recount($segment);
            $n++;
        }

        return back()->with('success', $n ? "{$n} ready-made segment(s) added." : 'Nothing new to add.');
    }

    public function export(CrmSegment $segment)
    {
        $request = Request::create('/', 'GET', ['segment' => $segment->id]);

        return app(CrmContactController::class)->export($request);
    }

    private function validated(Request $request): array
    {
        $v = Validator::make($request->all(), [
            'name' => 'required|string|max:120',
            'description' => 'nullable|string|max:500',
            'color' => 'required|in:' . implode(',', CrmTag::COLORS + [10 => 'sky', 11 => 'yellow']),
            'match' => 'required|in:all,any',
            'rules' => 'required|array|min:1|max:12',
        ])->validate();

        $rules = CrmSegmentEngine::sanitize($v['rules']);
        if (! $rules) {
            abort(422, 'Add at least one complete rule.');
        }

        return ['name' => $v['name'], 'description' => $v['description'] ?? null, 'color' => $v['color'], 'match' => $v['match'], 'rules' => $rules];
    }

    private function formData(CrmSegment $segment): array
    {
        return [
            'segment' => $segment,
            'fields' => CrmSegmentEngine::FIELDS,
            'ops' => CrmSegmentEngine::OPS,
            'enums' => [
                'lifecycle_stage' => collect(\App\Models\Crm\CrmContact::LIFECYCLE)->map(fn ($x) => $x['label'])->all(),
                'rfm_segment' => array_combine(array_keys(CrmMetrics::SEGMENT_INFO), array_keys(CrmMetrics::SEGMENT_INFO)),
                'source' => ['order' => 'Order', 'registration' => 'Registration', 'newsletter' => 'Newsletter', 'lead' => 'Lead', 'contact_form' => 'Contact form', 'manual' => 'Manual'],
                'status' => ['active' => 'Active', 'blocked' => 'Blocked', 'do_not_contact' => 'Do not contact'],
                'tag' => CrmTag::orderBy('name')->pluck('name', 'name')->all(),
            ],
            'colors' => array_merge(CrmTag::COLORS, ['sky', 'yellow']),
        ];
    }
}
