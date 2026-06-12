<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportTicketMessage extends Model
{
    protected $fillable = ['ticket_id', 'user_id', 'is_staff', 'message'];

    protected $casts = ['is_staff' => 'boolean'];

    public function ticket() { return $this->belongsTo(SupportTicket::class); }
    public function user()   { return $this->belongsTo(User::class); }
}
