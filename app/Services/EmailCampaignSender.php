<?php

namespace App\Services;

use App\Models\EmailCampaign;
use App\Models\EmailCampaignLog;
use App\Models\NotificationPreference;
use Illuminate\Support\Facades\Mail;

class EmailCampaignSender
{
    public static function startSending(EmailCampaign $campaign): void
    {
        $recipients = $campaign->resolveRecipients();

        foreach ($recipients as $user) {
            EmailCampaignLog::firstOrCreate(
                ['campaign_id' => $campaign->id, 'user_id' => $user->id],
                ['email' => $user->email, 'status' => 'pending']
            );
        }

        $campaign->update([
            'status' => 'sending',
            'recipient_count' => $recipients->count(),
        ]);
    }

    public static function processBatch(EmailCampaign $campaign, int $batchSize = 30): void
    {
        $logs = $campaign->logs()->where('status', 'pending')->with('user')->take($batchSize)->get();

        $fromEmail = $campaign->from_email ?: config('mail.from.address');
        $fromName = $campaign->from_name ?: config('mail.from.name');

        $sent = 0;
        $failed = 0;

        foreach ($logs as $log) {
            $user = $log->user;

            if (! $user) {
                $log->update(['status' => 'failed', 'error' => 'Recipient account no longer exists', 'sent_at' => now()]);
                $failed++;
                continue;
            }

            try {
                $body = static::renderBody($campaign->content, $user);

                Mail::html($body, function ($message) use ($user, $campaign, $fromEmail, $fromName) {
                    $message->to($user->email, $user->name)
                        ->subject($campaign->subject)
                        ->from($fromEmail, $fromName);
                });

                $log->update(['status' => 'sent', 'sent_at' => now()]);
                $sent++;
            } catch (\Throwable $e) {
                $log->update(['status' => 'failed', 'error' => $e->getMessage(), 'sent_at' => now()]);
                $failed++;
            }
        }

        if ($sent > 0 || $failed > 0) {
            $campaign->increment('sent_count', $sent);
            $campaign->increment('failed_count', $failed);
        }

        if (! $campaign->logs()->where('status', 'pending')->exists()) {
            $campaign->update(['status' => 'sent', 'sent_at' => $campaign->sent_at ?? now()]);
        }
    }

    private static function renderBody(string $content, $user): string
    {
        $body = str_replace(['{{ $name }}', '{{ $email }}'], [$user->name, $user->email], $content);

        $pref = NotificationPreference::forUser($user->id);
        $unsubscribeUrl = route('email.unsubscribe', $pref->unsubscribe_token);
        $unsubscribeLink = 'You\'re receiving this because you have an account with us. '
            . '<a href="' . $unsubscribeUrl . '" style="color:#6366f1;">Unsubscribe from promotional emails</a>';

        return wrap_branded_email($body, $unsubscribeLink);
    }
}
