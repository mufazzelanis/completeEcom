<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Page extends Model
{
    protected $fillable = [
        'type', 'title', 'slug', 'excerpt', 'content', 'image', 'template',
        'is_active', 'sort_order', 'meta_title', 'meta_description', 'meta_keywords',
        'og_title', 'og_description', 'og_image', 'canonical_url',
    ];

    protected $casts = ['is_active' => 'boolean'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($m) {
            if (empty($m->slug)) $m->slug = Str::slug($m->title);
        });
    }

    // Sanitized on write, not read — content is rendered unescaped ({!! !!}) in
    // pages/show and pages/contact, so stored HTML must already be safe rather
    // than trusted at render time. 'rich_content' (not the minimal 'default'
    // profile) matches what the Summernote editor's toolbar actually produces.
    public function setContentAttribute($value)
    {
        $this->attributes['content'] = $value !== null ? clean($value, 'rich_content') : $value;
    }

    public function scopeActive($query)  { return $query->where('is_active', true); }
    public function scopeStatic($query)  { return $query->where('type', 'static'); }
    public function scopeLanding($query) { return $query->where('type', 'landing'); }
    public function scopeSeo($query)     { return $query->where('type', 'seo'); }

    public function getRouteKeyName() { return 'slug'; }
}
