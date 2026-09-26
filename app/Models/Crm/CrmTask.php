<?php

namespace App\Models\Crm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmTask extends Model
{
    protected $table = 'crm_tasks';
    protected $fillable = ['contact_id', 'lead_id', 'assigned_to', 'created_by', 'title', 'description', 'type', 'priority', 'due_at', 'status', 'completed_at', 'completed_by', 'reminded_at', 'is_auto'];
    protected $casts = ['due_at' => 'datetime', 'completed_at' => 'datetime', 'reminded_at' => 'datetime', 'is_auto' => 'boolean'];

    public const TYPES = ['follow_up' => 'Follow-up', 'call' => 'Call', 'email' => 'Email', 'whatsapp' => 'WhatsApp', 'meeting' => 'Meeting', 'other' => 'Other'];
    public const PRIORITIES = ['low' => 'Low', 'normal' => 'Normal', 'high' => 'High', 'urgent' => 'Urgent'];

    public function contact(): BelongsTo { return $this->belongsTo(CrmContact::class, 'contact_id'); }
    public function lead(): BelongsTo { return $this->belongsTo(CrmLead::class, 'lead_id'); }
    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }

    public function scopeOpen($q)
    {
        return $q->where('status', 'open');
    }

    public function isOverdue(): bool
    {
        return $this->status === 'open' && $this->due_at && $this->due_at->isPast();
    }
}
