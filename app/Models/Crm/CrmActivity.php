<?php

namespace App\Models\Crm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmActivity extends Model
{
    protected $table = 'crm_activities';
    protected $fillable = ['contact_id', 'lead_id', 'user_id', 'type', 'direction', 'subject', 'body', 'outcome', 'is_pinned', 'occurred_at', 'meta'];
    protected $casts = ['is_pinned' => 'boolean', 'occurred_at' => 'datetime', 'meta' => 'array'];

    public const TYPES = ['note' => 'Note', 'call' => 'Call', 'email' => 'Email', 'sms' => 'SMS', 'whatsapp' => 'WhatsApp', 'meeting' => 'Meeting'];
    public const OUTCOMES = ['connected' => 'Connected', 'no_answer' => 'No answer', 'busy' => 'Busy', 'callback' => 'Call back later', 'interested' => 'Interested', 'not_interested' => 'Not interested', 'ordered' => 'Placed order'];

    public function contact(): BelongsTo { return $this->belongsTo(CrmContact::class, 'contact_id'); }
    public function lead(): BelongsTo { return $this->belongsTo(CrmLead::class, 'lead_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
