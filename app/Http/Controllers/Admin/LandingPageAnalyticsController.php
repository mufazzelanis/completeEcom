<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FacebookConversionLog;
use App\Models\LandingPage;
use App\Models\LandingPageCtaClick;
use App\Models\LandingPageVisit;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Admin → Landing Pages → Analytics: the funnel a landing page's own report page never
 * showed — not just "views → orders", but every step between (scrolled deep, reached the
 * order form, actually touched it) so a page with traffic but no sales shows exactly where
 * people are dropping off. Mirrors Admin\BlogAnalyticsController's shape (range picker,
 * funnel bars, time histogram) for the same single-page order funnel instead of an article.
 */
class LandingPageAnalyticsController extends Controller
{
    public const PERIODS = [
        'today'     => 'Today',
        'yesterday' => 'Yesterday',
        '7d'        => 'Last 7 days',
        '30d'       => 'Last 30 days',
        '90d'       => 'Last 90 days',
    ];

    public function show(Request $request, LandingPage $landingPage)
    {
        $r = $this->range($request);

        $visits = $this->visits($landingPage->id, $r['from'], $r['to']);
        $depth = (clone $visits)->toBase()
            ->selectRaw('COUNT(*) n, SUM(max_scroll >= 25) d25, SUM(max_scroll >= ?) d50, SUM(max_scroll >= 75) d75, SUM(reached_form_at IS NOT NULL) form_reached, SUM(form_start_at IS NOT NULL) form_started, SUM(order_id IS NOT NULL) ordered', [LandingPageVisit::DEEP_SCROLL])
            ->first();

        $time = (clone $visits)->toBase()
            ->selectRaw('SUM(active_seconds < 10) t0, SUM(active_seconds BETWEEN 10 AND 29) t1, SUM(active_seconds BETWEEN 30 AND 59) t2, SUM(active_seconds BETWEEN 60 AND 179) t3, SUM(active_seconds BETWEEN 180 AND 299) t4, SUM(active_seconds >= 300) t5')
            ->first();

        $ctaClicks = LandingPageCtaClick::where('landing_page_id', $landingPage->id)
            ->whereBetween('created_at', $this->utc($r['from'], $r['to']))
            ->groupBy('placement')->selectRaw('placement, COUNT(*) n')->pluck('n', 'placement');

        $sources = (clone $visits)->toBase()->groupBy('source')->selectRaw('source, COUNT(*) n')->orderByDesc('n')->pluck('n', 'source');
        $devices = (clone $visits)->toBase()->groupBy('device')->selectRaw('device, COUNT(*) n')->orderByDesc('n')->pluck('n', 'device');

        $kpi = [
            'views'         => (int) $depth->n,
            'readers'       => (int) LandingPageVisit::where('landing_page_id', $landingPage->id)
                ->whereBetween('visit_date', [$r['from']->toDateString(), $r['to']->toDateString()])->engaged()->count(),
            'avg_scroll'    => $depth->n ? (float) (clone $visits)->avg('max_scroll') : 0,
            'avg_time'      => $depth->n ? (float) (clone $visits)->avg('active_seconds') : 0,
            'reached_form'  => (int) $depth->form_reached,
            'started_form'  => (int) $depth->form_started,
            'orders'        => (int) $depth->ordered,
            'conv_rate'     => $depth->n ? $depth->ordered / $depth->n * 100 : 0,
        ];

        // Pixel/CAPI health for THIS page specifically — the site-wide log table had no
        // column to filter by landing page at all before this feature.
        $capi = FacebookConversionLog::where('landing_page_id', $landingPage->id)
            ->where('sent_at', '>=', $r['from']->copy()->startOfDay()->utc())
            ->groupBy('event_name', 'status')
            ->selectRaw('event_name, status, COUNT(*) n')
            ->get()
            ->groupBy('event_name')
            ->map(fn ($rows) => ['sent' => (int) ($rows->firstWhere('status', 'sent')->n ?? 0), 'failed' => (int) ($rows->firstWhere('status', 'failed')->n ?? 0)]);
        $capiConfigured = (bool) ($landingPage->fb_pixel_id ?: setting('facebook_pixel_id', ''));

        return view('admin.landing-pages.analytics', compact('landingPage', 'r', 'kpi', 'depth', 'time', 'ctaClicks', 'sources', 'devices', 'capi', 'capiConfigured'));
    }

    private function visits(int $landingPageId, Carbon $from, Carbon $to)
    {
        return LandingPageVisit::where('landing_page_id', $landingPageId)
            ->whereBetween('visit_date', [$from->toDateString(), $to->toDateString()]);
    }

    /** Same range picker shape as Admin\BlogAnalyticsController::range(). */
    private function range(Request $request): array
    {
        $tz = setting('timezone', 'Asia/Dhaka');
        $today = now($tz)->startOfDay();
        $period = array_key_exists($request->query('period'), self::PERIODS) ? $request->query('period') : '30d';

        [$from, $to] = match ($period) {
            'today'     => [$today->copy(), $today->copy()],
            'yesterday' => [$today->copy()->subDay(), $today->copy()->subDay()],
            '7d'        => [$today->copy()->subDays(6), $today->copy()],
            '90d'       => [$today->copy()->subDays(89), $today->copy()],
            default     => [$today->copy()->subDays(29), $today->copy()],
        };

        return ['period' => $period, 'tz' => $tz, 'from' => $from, 'to' => $to, 'label' => self::PERIODS[$period]];
    }

    private function utc(Carbon $from, Carbon $to): array
    {
        return [$from->copy()->startOfDay()->utc(), $to->copy()->endOfDay()->utc()];
    }
}
