<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BlogCategory extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'image', 'parent_id', 'sort_order', 'is_active'];
    protected $casts    = ['is_active' => 'boolean'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($m) {
            if (empty($m->slug)) $m->slug = Str::slug($m->name);
        });
    }

    public function posts()    { return $this->hasMany(BlogPost::class); }
    public function parent()   { return $this->belongsTo(BlogCategory::class, 'parent_id'); }
    public function children() { return $this->hasMany(BlogCategory::class, 'parent_id'); }

    public function getRouteKeyName() { return 'slug'; }
}
