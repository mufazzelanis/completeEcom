<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailCampaignLog extends Model
{
    protected $fillable = ['campaign_id', 'user_id', 'email', 'status', 'error', 'sent_at'];

    protected $casts = ['sent_at' => 'datetime'];

    public function campaign()
    {
        return $this->belongsTo(EmailCampaign::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
