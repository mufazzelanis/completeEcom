<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnItem extends Model
{
    protected $table = 'return_items';

    protected $fillable = [
        'return_id', 'product_id', 'order_item_id',
        'product_name', 'quantity_requested', 'quantity_approved',
    ];

    public function productReturn(): BelongsTo { return $this->belongsTo(ProductReturn::class, 'return_id'); }
    public function product(): BelongsTo       { return $this->belongsTo(Product::class); }
    public function orderItem(): BelongsTo     { return $this->belongsTo(OrderItem::class); }
}
