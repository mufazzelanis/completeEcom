<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlogProductClick extends Model
{
    public const PLACEMENTS = [
        'inline'  => 'Inside the article',
        'shelf'   => 'End-of-post shelf',
        'sticky'  => 'Sticky buy bar',
        'sidebar' => 'Sidebar',
    ];

    public const ACTIONS = ['open', 'cart', 'buy'];

    public $timestamps = false;

    protected $fillable = ['blog_post_id', 'blog_read_id', 'product_id', 'placement', 'action', 'created_at'];

    protected $casts = ['created_at' => 'datetime'];

    public function product() { return $this->belongsTo(Product::class); }
}
