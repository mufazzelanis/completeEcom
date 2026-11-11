<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiBlogRun;
use App\Models\AiBlogTopic;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\AiBlog\AiBlogException;
use App\Services\AiBlog\AiClient;
use App\Services\AiBlog\Autopilot;
use App\Services\AiBlog\Settings;
use App\Services\AiBlog\TopicSuggester;
use App\Services\AiBlog\Writer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/** Admin → AI Blog: dashboard, topic queue, topic ideas, settings, weak-post report. */
class AiBlogController extends Controller
{
    public function index()
    {
        $tz = Settings::timezone();
        $now = now($tz);
        $today = $now->toDateString();

        $slots = collect(Settings::slots())->map(function ($slot) use ($today, $now) {
            $topic = AiBlogTopic::with('post')->where('slot_key', "$today $slot")->first();

            return ['time' => $slot, 'passed' => $now->copy()->setTimeFromTimeString($slot)->lte($now), 'topic' => $topic];
        });

        $spend = AiBlogRun::monthSpend();
        $budget = Settings::monthlyBudget();
        $lastTick = Cache::get(Autopilot::HEARTBEAT);

        return view('admin.ai-blog.index', [
            'slots'      => $slots,
            'spend'      => $spend,
            'budget'     => $budget,
            'queued'     => AiBlogTopic::where('status', 'queued')->count(),
            'toReview'   => BlogPost::where('ai_generated', true)->where('status', 'draft')->latest()->limit(10)->get(),
            'recent'     => AiBlogTopic::with('post')->whereIn('status', ['done', 'failed', 'writing'])->latest('updated_at')->limit(10)->get(),
            'postsMonth' => BlogPost::where('ai_generated', true)->where('created_at', '>=', $now->copy()->startOfMonth()->utc())->count(),
            'cronOk'     => $lastTick && now()->diffInMinutes(\Carbon\Carbon::parse($lastTick)) < 10,
            'lastTick'   => $lastTick,
            'perPost'    => (float) AiBlogRun::whereIn('purpose', ['write', 'translate'])->where('ok', true)->where('created_at', '>=', now()->subDays(60))
                ->selectRaw('SUM(cost_usd) / NULLIF(COUNT(DISTINCT ai_blog_topic_id), 0) as avg')->value('avg'),
        ]);
    }

    // ── Topics ────────────────────────────────────────────────────────────────

    public function topics(Request $request)
    {
        $status = $request->query('status', 'queued');
        $topics = AiBlogTopic::with(['post', 'category', 'brand'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->orderByRaw("FIELD(status, 'writing', 'queued', 'failed', 'done', 'skipped')")
            ->orderByRaw('not_before IS NULL')->orderBy('not_before')->orderBy('id')
            ->paginate(25)->withQueryString();

        $productNames = Product::whereIn('id', $topics->getCollection()->flatMap(fn ($t) => (array) $t->product_ids)->unique())->pluck('name', 'id');

        return view('admin.ai-blog.topics', $this->formData() + [
            'topics'       => $topics,
            'status'       => $status,
            'counts'       => AiBlogTopic::select('status', DB::raw('COUNT(*) c'))->groupBy('status')->pluck('c', 'status'),
            'productNames' => $productNames,
            'suggestions'  => session('ai_blog_suggestions', []),
        ]);
    }

    public function storeTopic(Request $request)
    {
        AiBlogTopic::create($this->validated($request) + ['status' => 'queued']);

        return back()->with('success', 'Topic added to the queue.');
    }

    public function editTopic(AiBlogTopic $topic)
    {
        return view('admin.ai-blog.topic-edit', $this->formData() + [
            'topic'    => $topic,
            'products' => Product::whereIn('id', (array) $topic->product_ids)->get()
                ->map(fn ($p) => BlogPostController::productRow($p))->values(),
        ]);
    }

    public function updateTopic(Request $request, AiBlogTopic $topic)
    {
        $data = $this->validated($request);
        if (in_array($topic->status, ['failed', 'skipped'], true)) {
            $data += ['status' => 'queued', 'attempts' => 0, 'error' => null];
        }
        $topic->update($data);

        return redirect()->route('admin.ai-blog.topics')->with('success', 'Topic saved.');
    }

    public function destroyTopic(AiBlogTopic $topic)
    {
        abort_if($topic->status === 'writing', 422, 'This topic is being written right now.');
        $topic->delete();

        return back()->with('success', 'Topic removed.');
    }

    /** "Write now": the scheduler picks it up within a minute (outside the posting times). */
    public function runTopic(AiBlogTopic $topic)
    {
        abort_if($topic->status === 'writing' || $topic->status === 'done', 422);
        if (!Settings::ready()) {
            return back()->with('error', 'Add your API key in AI Blog → Settings first.');
        }
        $topic->update(['status' => 'queued', 'run_now' => true, 'attempts' => 0, 'error' => null]);

        return back()->with('success', 'Writing will start within a minute — the post appears in "Needs review" when done (1–3 minutes).');
    }

    public function suggest(Request $request)
    {
        $request->validate(['count' => 'nullable|integer|min:3|max:20', 'focus' => 'nullable|string|max:300']);
        try {
            $ideas = (new TopicSuggester())->suggest((int) $request->input('count', 12), trim((string) $request->input('focus')));
        } catch (AiBlogException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.ai-blog.topics')->with('ai_blog_suggestions', $ideas)
            ->with('success', count($ideas) . ' ideas — tick the good ones and add your Mitavin Note to each.');
    }

    public function addSuggestions(Request $request)
    {
        $request->validate([
            'ideas'                 => 'required|array|max:20',
            'ideas.*.pick'          => 'nullable',
            'ideas.*.title'         => 'required|string|max:255',
            'ideas.*.keyword'       => 'nullable|string|max:255',
            'ideas.*.post_type'     => 'required|in:' . implode(',', array_keys(Writer::POST_TYPES)),
            'ideas.*.product_ids'   => 'nullable|string|max:200',
            'ideas.*.note'          => 'nullable|string|max:3000',
        ]);

        $added = 0;
        foreach ($request->input('ideas') as $idea) {
            if (empty($idea['pick'])) {
                continue;
            }
            AiBlogTopic::create([
                'title'       => $idea['title'],
                'keyword'     => $idea['keyword'] ?? null,
                'post_type'   => $idea['post_type'],
                'product_ids' => array_values(array_filter(array_map('intval', explode(',', (string) ($idea['product_ids'] ?? ''))))),
                'note'        => $idea['note'] ?? null,
                'status'      => 'queued',
            ]);
            $added++;
        }

        return redirect()->route('admin.ai-blog.topics')->with('success', "{$added} topic(s) added to the queue.");
    }

    // ── Settings ──────────────────────────────────────────────────────────────

    public function settings()
    {
        return view('admin.ai-blog.settings', [
            'authors'    => User::where('role', '!=', 'customer')->orderBy('name')->get(['id', 'name', 'role'])->filter->canAccessAdmin()->values(),
            'categories' => BlogCategory::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function saveSettings(Request $request)
    {
        $data = $request->validate([
            'ai_blog_enabled'          => 'nullable|in:0,1',
            'ai_blog_provider'         => 'required|in:' . implode(',', array_keys(Settings::PROVIDERS)),
            'ai_blog_model'            => 'required|in:' . implode(',', array_keys(Settings::ANTHROPIC_MODELS)),
            'ai_blog_openai_model'     => 'nullable|string|max:60|regex:/^[A-Za-z0-9._:-]*$/',
            'ai_blog_openai_price_in'  => 'nullable|numeric|min:0|max:500',
            'ai_blog_openai_price_out' => 'nullable|numeric|min:0|max:500',
            'ai_blog_posts_per_day'    => 'required|integer|min:0|max:' . Settings::HARD_DAILY_CAP,
            'ai_blog_slots'            => ['required', 'string', 'max:100', 'regex:/^\s*([01]?\d|2[0-3]):[0-5]\d(\s*,\s*([01]?\d|2[0-3]):[0-5]\d)*\s*$/'],
            'ai_blog_default_lang'     => 'required|in:bn,en',
            'ai_blog_bilingual'        => 'nullable|in:0,1',
            'ai_blog_publish_mode'     => 'required|in:draft,auto',
            'ai_blog_monthly_budget'   => 'required|numeric|min:0|max:1000',
            'ai_blog_words'            => 'required|integer|min:500|max:2500',
            'ai_blog_tone'             => 'nullable|string|max:500',
            'ai_blog_style_guide'      => 'nullable|string|max:5000',
            'ai_blog_disclaimer_bn'    => 'nullable|string|max:500',
            'ai_blog_disclaimer_en'    => 'nullable|string|max:500',
            'ai_blog_author_id'        => 'nullable|integer|exists:users,id',
            'ai_blog_category_id'      => 'nullable|integer|exists:blog_categories,id',
            'anthropic_key'            => 'nullable|string|max:300',
            'openai_key'               => 'nullable|string|max:300',
        ], ['ai_blog_slots.regex' => 'Posting times must look like 10:00, 15:30, 20:00']);

        foreach (['anthropic', 'openai'] as $provider) {
            $key = trim((string) ($data[$provider . '_key'] ?? ''));
            if ($request->boolean('forget_' . $provider . '_key')) {
                Settings::saveApiKey($provider, '');
            } elseif ($key !== '') {
                Settings::saveApiKey($provider, $key);
            }
            unset($data[$provider . '_key']);
        }

        $data['ai_blog_enabled'] = $request->boolean('ai_blog_enabled') ? '1' : '0';
        $data['ai_blog_bilingual'] = $request->boolean('ai_blog_bilingual') ? '1' : '0';
        foreach ($data as $key => $value) {
            Setting::set($key, $value === null ? '' : (string) $value, 'ai_blog');
        }
        Setting::bust();

        if ($data['ai_blog_enabled'] === '1' && !Settings::ready()) {
            return back()->with('warning', 'Saved — but the autopilot needs an API key before it can write.');
        }

        return back()->with('success', 'AI Blog settings saved.');
    }

    /** One tiny request to check the key, model and credit work. */
    public function test()
    {
        try {
            $data = AiClient::make(light: true)->json('Reply with the JSON asked for.', 'Say hello in Bangla.', [
                'type' => 'object', 'additionalProperties' => false, 'required' => ['hello'],
                'properties' => ['hello' => ['type' => 'string']],
            ], 1024, 'test', null, 'low');
        } catch (AiBlogException $e) {
            return back()->with('error', 'Connection failed: ' . $e->getMessage());
        }

        return back()->with('success', 'Connected ✓ ' . Settings::PROVIDERS[Settings::provider()] . ' answered: "' . mb_strimwidth((string) ($data['hello'] ?? ''), 0, 80) . '"');
    }

    // ── Weak posts ────────────────────────────────────────────────────────────

    /** Posts live for 90+ days that nobody finds: improve, merge or remove them (thin content drags the whole blog down). */
    public function weak()
    {
        $since = now()->subDays(90);
        $posts = BlogPost::published()->where('published_at', '<=', $since)
            ->withCount(['reads as reads_90' => fn ($q) => $q->where('created_at', '>=', $since)])
            ->orderBy('reads_90')->limit(100)->get();

        // Google clicks per post URL (Search Console sync), both language versions
        $clicks = DB::table('search_console_rows')->where('date', '>=', $since->toDateString())->where('page_type', 'blog')
            ->select('page', DB::raw('SUM(clicks) c'), DB::raw('SUM(impressions) i'))->groupBy('page')->get()
            ->groupBy(fn ($r) => preg_replace('#^.*/blog/([^/?#]+).*$#', '$1', $r->page));

        $rows = $posts->map(function ($post) use ($clicks) {
            $gsc = $clicks->get($post->slug, collect());

            return ['post' => $post, 'reads' => $post->reads_90, 'clicks' => (int) $gsc->sum('c'), 'impressions' => (int) $gsc->sum('i')];
        })->filter(fn ($r) => $r['clicks'] === 0 && $r['reads'] < 30)->values();

        return view('admin.ai-blog.weak', ['rows' => $rows]);
    }

    // ── helpers ───────────────────────────────────────────────────────────────

    private function formData(): array
    {
        return [
            'categories'     => Category::where('is_active', true)->orderBy('name')->get(['id', 'name', 'parent_id']),
            'brands'         => Brand::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'blogCategories' => BlogCategory::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'postTypes'      => Writer::POST_TYPES,
        ];
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title'            => 'required|string|max:255',
            'keyword'          => 'nullable|string|max:255',
            'post_type'        => 'required|in:' . implode(',', array_keys(Writer::POST_TYPES)),
            'lang'             => 'nullable|in:bn,en',
            'product_ids'      => 'nullable|array|max:8',
            'product_ids.*'    => 'integer|exists:products,id',
            'category_id'      => 'nullable|integer|exists:categories,id',
            'brand_id'         => 'nullable|integer|exists:brands,id',
            'blog_category_id' => 'nullable|integer|exists:blog_categories,id',
            'note'             => 'nullable|string|max:3000',
            'not_before'       => 'nullable|date',
        ]);

        if (empty($data['product_ids']) && empty($data['category_id']) && empty($data['brand_id'])) {
            throw \Illuminate\Validation\ValidationException::withMessages(['product_ids' => 'Pick at least one product, or a category / brand to take the best sellers from.']);
        }

        $data['product_ids'] = array_values(array_unique(array_map('intval', $data['product_ids'] ?? [])));
        $data['lang'] = $data['lang'] ?? null;
        $data['not_before'] = !empty($data['not_before'])
            ? \Carbon\Carbon::parse($data['not_before'], Settings::timezone())->utc() : null;

        return $data;
    }
}
