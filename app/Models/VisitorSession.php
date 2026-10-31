<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One storefront visit (see the create_visitor_sessions migration and LiveVisitorController::ping). */
class VisitorSession extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'is_team' => 'boolean',
        'reached_product' => 'boolean',
        'reached_cart' => 'boolean',
        'reached_checkout' => 'boolean',
        'ordered' => 'boolean',
        'cart_max' => 'decimal:2',
        'started_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];
}
