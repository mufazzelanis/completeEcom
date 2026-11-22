<?php

namespace App\Models;

use App\Http\Controllers\BlogTrackController;
use App\Support\VisitorIntel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * One landing-page view. Written by the server when the page is served (begin()), then kept
 * current by the in-page tracker (LandingPageTrackController) with scroll depth, active time,
 * and the funnel milestones (reached the order form, started filling it). Mirrors BlogRead —
 * same visit_key/visitor_key/engaged_at shape, same source()/device() classification (reused
 * directly from BlogTrackController rather than duplicated).
 */
class LandingPageVisit extends Model
{
    /** Scroll depth that counts as "got deep into the page" — same bar as blog's COMPLETE_AT. */
    public const DEEP_SCROLL = 50;

    protected $fillable = [
        'visit_key', 'landing_page_id', 'visitor_key', 'visit_date', 'source', 'referrer_host',
        'device', 'active_seconds', 'max_scroll', 'cta_clicks', 'engaged_at',
        'reached_form_at', 'form_start_at', 'order_id',
    ];

    protected $casts = [
        'visit_date'       => 'date',
        'engaged_at'       => 'datetime',
        'reached_form_at'  => 'datetime',
        'form_start_at'    => 'datetime',
    ];

    public function landingPage() { return $this->belongsTo(LandingPage::class); }

    public function order() { return $this->belongsTo(Order::class); }

    public function ctaClicks() { return $this->hasMany(LandingPageCtaClick::class); }

    public function scopeEngaged($query) { return $query->whereNotNull('engaged_at'); }

    /**
     * Signature the page's tracker sends back with its visit key — only keys this server
     * handed out (with a real page view) are accepted, so a script can't mint visits by
     * posting random keys. Same construction as BlogRead::sign().
     */
    public static function sign(string $key, int $landingPageId): string
    {
        return substr(hash_hmac('sha256', $key . '|lp|' . $landingPageId, (string) config('app.key')), 0, 32);
    }

    /**
     * Records the view being served now and returns its key for the page's tracker. A Turbo
     * hover-prefetch isn't a view yet: it only gets a key, and the tracker creates the row if
     * the page is actually shown.
     */
    public static function begin(LandingPage $landingPage, Request $request): ?string
    {
        $key = (string) Str::uuid();
        if (\App\Support\PageView::isPrefetch($request)) {
            return $key;
        }

        $ua = mb_substr((string) $request->userAgent(), 0, 500);
        // A declared bot (or the same automation signal Activity Log uses) never becomes a
        // "visit" — there's no bot_name column here to file it under like blog_reads has, so
        // the simplest correct thing is to not record it at all rather than let it skew the
        // funnel's view-count baseline.
        if (VisitorIntel::botFromUserAgent($ua) !== null || ActivityLog::automationSignal($request) !== null) {
            return $key;
        }

        try {
            static::create([
                'visit_key'      => $key,
                'landing_page_id' => $landingPage->id,
                // stands in until the tracker reports the browser's own id
                'visitor_key'    => 's' . substr(sha1($request->ip() . '|' . $ua), 0, 30),
                'visit_date'     => now(setting('timezone', 'Asia/Dhaka'))->toDateString(),
                'source'         => BlogTrackController::source($request->header('referer'), $request->getQueryString(), $request->getHost()),
                'referrer_host'  => BlogTrackController::host($request->header('referer')),
                'device'         => BlogTrackController::device($ua),
            ]);
        } catch (\Throwable $e) {
            report($e); // analytics must never break the landing page
        }

        return $key;
    }
}
