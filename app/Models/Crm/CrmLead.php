<?php

namespace App\Models\Crm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmLead extends Model
{
    protected $table = 'crm_leads';
    protected $fillable = ['contact_id', 'name', 'phone', 'email', 'company', 'source', 'stage', 'value', 'expected_close_on', 'interest', 'lost_reason', 'owner_id', 'position', 'won_at', 'lost_at'];
    protected $casts = ['value' => 'decimal:2', 'expected_close_on' => 'date', 'won_at' => 'datetime', 'lost_at' => 'datetime'];

    public const STAGES = [
        'new'         => ['label' => 'New',         'color' => 'blue',   'probability' => 10],
        'contacted'   => ['label' => 'Contacted',   'color' => 'indigo', 'probability' => 25],
        'qualified'   => ['label' => 'Qualified',   'color' => 'purple', 'probability' => 50],
        'proposal'    => ['label' => 'Proposal',    'color' => 'amber',  'probability' => 65],
        'negotiation' => ['label' => 'Negotiation', 'color' => 'orange', 'probability' => 80],
        'won'         => ['label' => 'Won',         'color' => 'green',  'probability' => 100],
        'lost'        => ['label' => 'Lost',        'color' => 'red',    'probability' => 0],
    ];
    public const SOURCES = ['website' => 'Website', 'contact_form' => 'Contact form', 'phone' => 'Phone call', 'whatsapp' => 'WhatsApp', 'facebook' => 'Facebook', 'walk_in' => 'Walk-in', 'referral' => 'Referral', 'other' => 'Other'];

    public function contact(): BelongsTo { return $this->belongsTo(CrmContact::class, 'contact_id'); }
    public function owner(): BelongsTo { return $this->belongsTo(User::class, 'owner_id'); }
    public function activities(): HasMany { return $this->hasMany(CrmActivity::class, 'lead_id'); }
    public function tasks(): HasMany { return $this->hasMany(CrmTask::class, 'lead_id'); }
}
