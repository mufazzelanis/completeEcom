<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One day × query × page of Google Search Console performance (see App\Services\SearchConsole). */
class SearchConsoleRow extends Model
{
    public $timestamps = false;

    protected $fillable = ['date', 'query', 'query_hash', 'page', 'page_hash', 'page_type', 'product_id', 'clicks', 'impressions', 'position'];

    protected $casts = ['date' => 'date', 'position' => 'float'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
