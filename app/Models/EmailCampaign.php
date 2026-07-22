<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailCampaign extends Model
{
    protected $fillable = [
        'name', 'subject', 'preheader', 'content',
        'from_name', 'from_email', 'recipient_type',
        'status', 'recipient_count', 'sent_count', 'failed_count',
        'scheduled_at', 'sent_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at'      => 'datetime',
    ];

    public function logs()
    {
        return $this->hasMany(EmailCampaignLog::class, 'campaign_id');
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'sent'      => 'bg-green-100 text-green-700',
            'sending'   => 'bg-blue-100 text-blue-700',
            'scheduled' => 'bg-purple-100 text-purple-700',
            'failed'    => 'bg-red-100 text-red-700',
            default     => 'bg-gray-100 text-gray-600',
        };
    }

    public function recipientLabel(): string
    {
        return match ($this->recipient_type) {
            'customers'    => 'All Customers (placed order)',
            'new_30d'      => 'New (last 30 days)',
            'never_ordered'=> 'Never Ordered',
            default        => 'All Users',
        };
    }

    public function resolveRecipients()
    {
        $optedOut = NotificationPreference::where('email_promo', false)->pluck('user_id');

        $query = match ($this->recipient_type) {
            'customers'     => User::whereHas('orders')->where('is_active', true),
            'new_30d'       => User::where('created_at', '>=', now()->subDays(30))->where('is_active', true),
            'never_ordered' => User::doesntHave('orders')->where('is_active', true),
            default         => User::where('is_active', true),
        };

        return $query->whereNotIn('id', $optedOut)->get();
    }
}
