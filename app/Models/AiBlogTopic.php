<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One article the AI Blog autopilot will write (Admin → AI Blog → Topics). */
class AiBlogTopic extends Model
{
    protected $fillable = [
        'title', 'keyword', 'post_type', 'lang', 'product_ids', 'category_id', 'brand_id', 'blog_category_id',
        'note', 'not_before', 'run_now', 'status', 'slot_key', 'attempts', 'error', 'blog_post_id',
    ];

    protected $casts = [
        'product_ids' => 'array',
        'not_before'  => 'datetime',
        'run_now'     => 'boolean',
        'attempts'    => 'integer',
    ];

    public function post()     { return $this->belongsTo(BlogPost::class, 'blog_post_id'); }
    public function category() { return $this->belongsTo(Category::class); }
    public function brand()    { return $this->belongsTo(Brand::class); }
    public function runs()     { return $this->hasMany(AiBlogRun::class); }

    /** Waiting to be written and allowed to be written now. */
    public function scopeDue($query)
    {
        return $query->where('status', 'queued')
            ->where(fn ($q) => $q->whereNull('not_before')->orWhere('not_before', '<=', now()));
    }
}
