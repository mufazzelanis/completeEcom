<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourierFraudCheck extends Model
{
    protected $fillable = [
        'phone', 'order_id', 'checked_by', 'provider', 'success', 'error',
        'total_orders', 'total_delivered', 'total_cancelled', 'success_rate',
        'risk_level', 'breakdown', 'raw_response',
    ];

    protected $casts = [
        'success' => 'boolean',
        'success_rate' => 'float',
        'breakdown' => 'array',
        'raw_response' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function checkedBy()
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    private const RISK_STYLES = [
        'high'    => ['label' => 'High Risk',    'bg' => 'bg-red-50',    'text' => 'text-red-600',    'border' => 'border-red-400',    'dot' => 'bg-red-500'],
        'medium'  => ['label' => 'Risky',        'bg' => 'bg-yellow-50', 'text' => 'text-yellow-600', 'border' => 'border-yellow-400', 'dot' => 'bg-yellow-500'],
        'low'     => ['label' => 'Safe',         'bg' => 'bg-green-50',  'text' => 'text-green-600',  'border' => 'border-green-400',  'dot' => 'bg-green-500'],
        'unknown' => ['label' => 'Not Enough Data', 'bg' => 'bg-gray-50', 'text' => 'text-gray-500', 'border' => 'border-gray-300', 'dot' => 'bg-gray-400'],
    ];

    public function getRiskStyle(): array
    {
        if (! $this->success) {
            return ['label' => 'Check Failed', 'bg' => 'bg-gray-50', 'text' => 'text-gray-500', 'border' => 'border-gray-300', 'dot' => 'bg-gray-400'];
        }

        return self::RISK_STYLES[$this->risk_level] ?? self::RISK_STYLES['unknown'];
    }
}
