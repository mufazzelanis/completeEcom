<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Tag extends Model
{
    protected $fillable = ['name', 'slug'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($tag) {
            if (empty($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }
        });
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_tag');
    }

    public static function findOrCreateByName(string $name): self
    {
        $slug = Str::slug($name);
        return static::firstOrCreate(['slug' => $slug], ['name' => trim($name), 'slug' => $slug]);
    }
}
