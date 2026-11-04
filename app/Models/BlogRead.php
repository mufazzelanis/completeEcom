<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One article page view, kept up to date by the in-page tracker (BlogTrackController). */
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
        'read_key', 'blog_post_id', 'visitor_key', 'user_id', 'read_date', 'source', 'referrer_host',
        'device', 'active_seconds', 'max_scroll', 'product_clicks',
    ];

    protected $casts = [
        'read_date' => 'date',
    ];

    public function post() { return $this->belongsTo(BlogPost::class, 'blog_post_id'); }
}
