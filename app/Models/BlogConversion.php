<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** An order placed after the shopper clicked a product inside a blog post (see BlogOrderObserver). */
class BlogConversion extends Model
{
    public $timestamps = false;

    protected $fillable = ['blog_post_id', 'blog_read_id', 'order_id', 'revenue', 'matched_revenue', 'created_at'];

    protected $casts = [
        'revenue'         => 'decimal:2',
        'matched_revenue' => 'decimal:2',
        'created_at'      => 'datetime',
    ];

    public function post()  { return $this->belongsTo(BlogPost::class, 'blog_post_id'); }
    public function order() { return $this->belongsTo(Order::class); }
}
