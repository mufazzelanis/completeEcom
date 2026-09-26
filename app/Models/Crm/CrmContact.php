<?php

namespace App\Models\Crm;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmContact extends Model
{
    protected $table = 'crm_contacts';

    protected $fillable = [
        'user_id', 'name', 'phone', 'email', 'city', 'birthday', 'source', 'status', 'is_vip', 'owner_id', 'last_contacted_at',
    ];

    protected $casts = [
        'birthday' => 'date',
        'is_vip' => 'boolean',
        'total_spent' => 'decimal:2',
        'avg_order_value' => 'decimal:2',
        'first_order_at' => 'datetime',
        'last_order_at' => 'datetime',
        'last_contacted_at' => 'datetime',
        'metrics_refreshed_at' => 'datetime',
        'predicted_next_order_on' => 'date',
    ];

    public const LIFECYCLE = [
        'prospect' => ['label' => 'Prospect', 'color' => 'gray'],
        'new'      => ['label' => 'New', 'color' => 'blue'],
        'active'   => ['label' => 'Active', 'color' => 'green'],
        'at_risk'  => ['label' => 'At risk', 'color' => 'amber'],
        'lost'     => ['label' => 'Lost', 'color' => 'red'],
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function owner(): BelongsTo { return $this->belongsTo(User::class, 'owner_id'); }
    public function orders(): HasMany { return $this->hasMany(Order::class, 'crm_contact_id'); }
    public function activities(): HasMany { return $this->hasMany(CrmActivity::class, 'contact_id'); }
    public function tasks(): HasMany { return $this->hasMany(CrmTask::class, 'contact_id'); }
    public function leads(): HasMany { return $this->hasMany(CrmLead::class, 'contact_id'); }
    public function tags(): BelongsToMany { return $this->belongsToMany(CrmTag::class, 'crm_contact_tag', 'contact_id', 'tag_id'); }

    public function getInitialsAttribute(): string
    {
        $parts = preg_split('/\s+/', trim($this->name)) ?: [];
        $s = '';
        foreach (array_slice($parts, 0, 2) as $p) {
            $s .= mb_strtoupper(mb_substr($p, 0, 1));
        }
        return $s ?: '?';
    }
}
