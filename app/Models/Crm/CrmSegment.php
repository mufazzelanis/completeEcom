<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;

class CrmSegment extends Model
{
    protected $table = 'crm_segments';
    protected $fillable = ['name', 'description', 'color', 'rules', 'match', 'created_by', 'contact_count', 'counted_at'];
    protected $casts = ['rules' => 'array', 'counted_at' => 'datetime'];
}
