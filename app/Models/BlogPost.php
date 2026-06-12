<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BlogPost extends Model
{
    protected $fillable = [
        'blog_category_id', 'user_id', 'title', 'slug', 'excerpt', 'content',
        'image', 'status', 'published_at', 'is_featured', 'views',
        'meta_title', 'meta_description', 'meta_keywords',
    ];

    protected $casts = [
        'is_featured'  => 'boolean',
        'published_at' => 'datetime',
        'views'        => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($m) {
            if (empty($m->slug)) $m->slug = Str::slug($m->title);
        });
    }

    public function category() { return $this->belongsTo(BlogCategory::class, 'blog_category_id'); }
    public function author()   { return $this->belongsTo(User::class, 'user_id'); }
    public function tags()     { return $this->belongsToMany(BlogTag::class, 'blog_post_tag'); }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')->where('published_at', '<=', now());
    }

    public function scopeFeatured($query) { return $query->where('is_featured', true); }

    public function getRouteKeyName() { return 'slug'; }
}
