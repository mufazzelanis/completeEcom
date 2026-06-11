<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $fillable = [
        'name', 'slug', 'type', 'description', 'logo',
        'account_name', 'account_number', 'bank_name', 'branch', 'routing_number',
        'instructions', 'charge_type', 'charge_value',
        'min_amount', 'max_amount', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'charge_value' => 'decimal:2',
        'min_amount'   => 'decimal:2',
        'max_amount'   => 'decimal:2',
    ];

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function calculateCharge(float $amount): float
    {
        return match($this->charge_type) {
            'fixed'   => (float) $this->charge_value,
            'percent' => round($amount * $this->charge_value / 100, 2),
            default   => 0.0,
        };
    }

    public function requiresVerification(): bool
    {
        return in_array($this->type, ['mobile_banking', 'bank_transfer']);
    }

    public function typeBadge(): string
    {
        return match($this->type) {
            'cod'            => 'bg-green-100 text-green-700',
            'mobile_banking' => 'bg-pink-100 text-pink-700',
            'bank_transfer'  => 'bg-blue-100 text-blue-700',
            'card'           => 'bg-purple-100 text-purple-700',
            default          => 'bg-gray-100 text-gray-600',
        };
    }

    public function typeLabel(): string
    {
        return match($this->type) {
            'cod'            => 'Cash on Delivery',
            'mobile_banking' => 'Mobile Banking',
            'bank_transfer'  => 'Bank Transfer',
            'card'           => 'Card',
            default          => $this->type,
        };
    }
}
