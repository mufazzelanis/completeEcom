<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BlogTag extends Model
{
    protected $fillable = ['name', 'slug'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($m) {
            if (empty($m->slug)) $m->slug = Str::slug($m->name);
        });
    }

    public function posts() { return $this->belongsToMany(BlogPost::class, 'blog_post_tag'); }

    public function getRouteKeyName() { return 'slug'; }
}
