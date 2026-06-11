<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'order_id', 'payment_method_id', 'payment_method_slug', 'payment_method_name',
        'amount', 'charge', 'status',
        'transaction_id', 'sender_number', 'gateway_ref', 'gateway_response',
        'verified_by', 'verified_at', 'refunded_at', 'admin_note',
        'ip_address', 'user_agent',
    ];

    protected $casts = [
        'amount'      => 'decimal:2',
        'charge'      => 'decimal:2',
        'verified_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function statusBadge(): string
    {
        return match($this->status) {
            'pending'              => 'bg-yellow-100 text-yellow-700',
            'pending_verification' => 'bg-orange-100 text-orange-700',
            'completed'            => 'bg-green-100 text-green-700',
            'failed'               => 'bg-red-100 text-red-600',
            'refunded'             => 'bg-gray-100 text-gray-600',
            default                => 'bg-gray-100 text-gray-600',
        };
    }

    public function statusLabel(): string
    {
        return match($this->status) {
            'pending'              => 'Pending',
            'pending_verification' => 'Awaiting Verification',
            'completed'            => 'Completed',
            'failed'               => 'Failed',
            'refunded'             => 'Refunded',
            default                => ucfirst($this->status),
        };
    }
}
