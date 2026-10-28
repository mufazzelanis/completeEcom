<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LiveVisitor;
use Illuminate\Http\Request;

/**
 * Admin → Live Visitors: who's on the storefront right now, on which page / product (with
 * picture and price), from where, and what's in their cart. The page refreshes its feed every
 * few seconds without reloading; the dashboard shows a compact summary of the same data.
 * Team members (anyone who can open the admin) are left out unless ?team=1.
 */
class LiveVisitorController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.live-visitors.index', $this->snapshot($request));
    }

    /** The live part only — polled by the page every few seconds. */
    public function feed(Request $request)
    {
        $data = $this->snapshot($request);

        return response()->json([
            'count' => $data['live']->count(),
            'html' => view('admin.live-visitors._feed', $data)->render(),
        ]);
    }

    /** Compact numbers for the dashboard card. */
    public function summary(Request $request)
    {
        $data = $this->snapshot($request);

        return response()->json([
            'count' => $data['live']->count(),
            'byType' => $data['byType'],
            'top' => collect($data['topProducts'])->take(3)->values(),
            'checkout' => $data['live']->where('page_type', 'checkout')->count(),
            'url' => route('admin.live-visitors.index'),
        ]);
    }

    private function snapshot(Request $request): array
    {
        $includeTeam = $request->boolean('team');
        $recent = LiveVisitor::with(['user:id,name,phone,role', 'product:id,name,slug,price,sale_price,image'])
            ->where('last_seen_at', '>=', now()->subMinutes(30))
            ->orderByDesc('page_started_at')
            ->get();

        $isTeam = fn (LiveVisitor $v) => $v->user && $v->user->canAccessAdmin();
        $teamLive = $recent->filter(fn ($v) => $v->last_seen_at->gte(now()->subSeconds(LiveVisitor::LIVE_SECONDS)) && $isTeam($v))->count();
        if (!$includeTeam) {
            $recent = $recent->reject($isTeam)->values();
        }

        $liveSince = now()->subSeconds(LiveVisitor::LIVE_SECONDS);
        $live = $recent->filter(fn ($v) => $v->last_seen_at->gte($liveSince))
            // checkout and cart first — those are the visitors about to order
            ->sortBy(fn ($v) => [array_search($v->page_type, ['checkout', 'cart', 'product']) === false ? 9 : array_search($v->page_type, ['checkout', 'cart', 'product']), -$v->page_started_at?->timestamp])
            ->values();
        $left = $recent->filter(fn ($v) => $v->last_seen_at->lt($liveSince) && $v->last_seen_at->gte(now()->subMinutes(10)))->values();

        $byType = $live->groupBy('page_type')->map->count()->sortDesc()->all();

        $topProducts = $live->whereNotNull('product_id')->groupBy('product_id')
            ->map(fn ($group) => [
                'name' => $group->first()->product?->name ?? $group->first()->title,
                'price' => (float) ($group->first()->product?->sale_price ?? $group->first()->product?->price ?? 0),
                'image' => $group->first()->product?->image ? image_thumb($group->first()->product->image, 200) : null,
                'url' => $group->first()->product ? route('products.show', $group->first()->product) : $group->first()->url,
                'viewers' => $group->count(),
            ])->sortByDesc('viewers')->values()->all();

        // visitors on the site in each of the last 30 minutes (oldest first)
        $chart = [];
        for ($i = 29; $i >= 0; $i--) {
            $start = now()->subMinutes($i + 1);
            $end = now()->subMinutes($i);
            $chart[] = $recent->filter(fn ($v) => $v->first_seen_at && $v->first_seen_at->lte($end) && $v->last_seen_at->gte($start))->count();
        }

        $today = LiveVisitor::where('first_seen_at', '>=', now()->setTimezone(setting('timezone', 'Asia/Dhaka'))->startOfDay()->utc())->count();

        return [
            'live' => $live,
            'left' => $left,
            'byType' => $byType,
            'topProducts' => $topProducts,
            'chart' => $chart,
            'visitorsToday' => $today,
            'teamLive' => $teamLive,
            'includeTeam' => $includeTeam,
            'cartValue' => (float) $live->sum('cart_total'),
        ];
    }
}
