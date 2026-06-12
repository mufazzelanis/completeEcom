<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SupportTicket extends Model
{
    protected $fillable = [
        'user_id', 'order_id', 'ticket_number', 'subject',
        'category', 'priority', 'status', 'reply_count', 'last_reply_at',
    ];

    protected $casts = ['last_reply_at' => 'datetime'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($ticket) {
            $ticket->ticket_number = 'TKT-' . strtoupper(Str::random(8));
        });
    }

    public function user()      { return $this->belongsTo(User::class); }
    public function order()     { return $this->belongsTo(Order::class); }
    public function messages()  { return $this->hasMany(SupportTicketMessage::class, 'ticket_id')->oldest(); }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'open'     => 'bg-green-100 text-green-700',
            'pending'  => 'bg-yellow-100 text-yellow-700',
            'resolved' => 'bg-blue-100 text-blue-700',
            'closed'   => 'bg-gray-100 text-gray-500',
            default    => 'bg-gray-100 text-gray-500',
        };
    }

    public function getPriorityBadgeAttribute(): string
    {
        return match($this->priority) {
            'urgent' => 'bg-red-100 text-red-700',
            'high'   => 'bg-orange-100 text-orange-700',
            'medium' => 'bg-yellow-100 text-yellow-700',
            'low'    => 'bg-gray-100 text-gray-500',
            default  => 'bg-gray-100 text-gray-500',
        };
    }
}
