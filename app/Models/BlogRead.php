<?php

namespace App\Models;

use App\Http\Controllers\BlogTrackController;
use App\Support\VisitorIntel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * One article page view. Written by the server when the page is served (so bots that never run
 * JavaScript are seen too, by name), then kept up to date by the in-page tracker
 * (BlogTrackController). Only views with engaged_at — a real touch / mouse / key event — are readers.
 */
class BlogRead extends Model
{
    public const SOURCES = [
        'search'   => 'Search',
        'social'   => 'Social',
        'internal' => 'This site',
        'referral' => 'Other sites',
        'ads'      => 'Ads',
        'email'    => 'Email / SMS',
        'direct'   => 'Direct',
    ];

    /** Scroll depth that counts as "read to the end" (the share row / product shelf sit below the text). */
    public const COMPLETE_AT = 90;

    protected $fillable = [
        'read_key', 'blog_post_id', 'visitor_key', 'user_id', 'ip', 'user_agent', 'bot_name', 'bot_kind',
        'read_date', 'source', 'referrer_host', 'device', 'active_seconds', 'max_scroll', 'product_clicks', 'engaged_at',
    ];

    protected $casts = [
        'read_date'  => 'date',
        'engaged_at' => 'datetime',
        'net_hosting'  => 'boolean',
        'bot_verified' => 'boolean',
    ];

    public function post() { return $this->belongsTo(BlogPost::class, 'blog_post_id'); }

    public function scopeReaders($query) { return $query->whereNotNull('engaged_at'); }

    /**
     * Signature the page's tracker sends back with its read key. Only keys this server handed out
     * (with a real page view) are accepted, so a script can't mint readers by posting random keys.
     */
    public static function sign(string $key, int $postId): string
    {
        return substr(hash_hmac('sha256', $key . '|' . $postId, (string) config('app.key')), 0, 32);
    }

    /**
     * Records the view being served now and returns its key for the page's tracker. Staff aren't
     * counted (null). A Turbo hover-prefetch isn't a view yet: it only gets a key, and the tracker
     * creates the row if the page is actually shown and read.
     */
    public static function begin(BlogPost $post, Request $request): ?string
    {
        if (auth()->user()?->canAccessAdmin()) {
            return null;
        }
        $key = (string) Str::uuid();
        if (\App\Support\PageView::isPrefetch($request)) {
            return $key;
        }

        $ua = mb_substr((string) $request->userAgent(), 0, 500);
        $bot = VisitorIntel::botFromUserAgent($ua);
        $automation = $bot ? null : ActivityLog::automationSignal($request);

        try {
            static::create([
                'read_key'      => $key,
                'blog_post_id'  => $post->id,
                // stands in until the tracker reports the browser's own id; one per IP + browser meanwhile
                'visitor_key'   => 's' . substr(sha1($request->ip() . '|' . $ua), 0, 30),
                'user_id'       => auth()->id(),
                'ip'            => $request->ip(),
                'user_agent'    => $ua,
                'bot_name'      => $bot['name'] ?? ($automation ? 'Scraper posing as a browser' : null),
                'bot_kind'      => $bot['kind'] ?? ($automation ? 'tool' : null),
                'read_date'     => now(setting('timezone', 'Asia/Dhaka'))->toDateString(),
                'source'        => BlogTrackController::source($request->header('referer'), $request->getQueryString(), $request->getHost()),
                'referrer_host' => BlogTrackController::host($request->header('referer')),
                'device'        => BlogTrackController::device($ua),
            ]);
            // no IP lookup queued here: visitors:resolve-ips picks up new IPs every minute, keeping
            // the article free of extra cache writes
        } catch (\Throwable $e) {
            report($e); // analytics must never break the article
        }

        return $key;
    }
}
