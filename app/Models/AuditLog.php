<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id', 'action', 'description', 'model_type', 'model_id',
        'old_values', 'new_values', 'ip_address', 'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getActionLabelAttribute(): string
    {
        return match (true) {
            str_starts_with($this->action, 'order.')   => 'Orders',
            str_starts_with($this->action, 'product.') => 'Products',
            str_starts_with($this->action, 'user.')    => 'Users',
            str_starts_with($this->action, 'coupon.')  => 'Coupons',
            default => ucfirst(str_replace(['.', '_'], ' ', $this->action)),
        };
    }

    public function getActionColorAttribute(): string
    {
        return match (true) {
            str_contains($this->action, 'deleted')  => 'bg-red-100 text-red-700',
            str_contains($this->action, 'created')  => 'bg-green-100 text-green-700',
            str_contains($this->action, 'updated')  => 'bg-blue-100 text-blue-700',
            str_contains($this->action, 'status')   => 'bg-purple-100 text-purple-700',
            str_contains($this->action, 'approved') => 'bg-green-100 text-green-700',
            str_contains($this->action, 'rejected') => 'bg-red-100 text-red-700',
            default => 'bg-gray-100 text-gray-700',
        };
    }
}
