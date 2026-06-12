<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailCampaign;
use App\Models\EmailCampaignLog;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class EmailCampaignController extends Controller
{
    public function index()
    {
        $campaigns = EmailCampaign::latest()->paginate(15);

        $stats = [
            'total'     => EmailCampaign::count(),
            'sent'      => EmailCampaign::where('status', 'sent')->count(),
            'draft'     => EmailCampaign::where('status', 'draft')->count(),
            'scheduled' => EmailCampaign::where('status', 'scheduled')->count(),
            'emails_sent' => EmailCampaignLog::where('status', 'sent')->count(),
        ];

        return view('admin.email-campaigns.index', compact('campaigns', 'stats'));
    }

    public function create()
    {
        return view('admin.email-campaigns.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:255',
            'subject'        => 'required|string|max:255',
            'preheader'      => 'nullable|string|max:255',
            'content'        => 'required|string',
            'from_name'      => 'nullable|string|max:100',
            'from_email'     => 'nullable|email',
            'recipient_type' => 'required|in:all,customers,new_30d,never_ordered',
            'scheduled_at'   => 'nullable|date|after:now',
        ]);

        $data['status'] = $request->filled('scheduled_at') ? 'scheduled' : 'draft';

        // Pre-count recipients
        $campaign = new EmailCampaign($data);
        $data['recipient_count'] = $campaign->resolveRecipients()->count();

        $campaign = EmailCampaign::create($data);
        AuditLogger::log('email_campaign.created', "Email campaign \"{$campaign->name}\" created", $campaign);

        return redirect()->route('admin.email-campaigns.show', $campaign)
            ->with('success', 'Campaign saved.');
    }

    public function show(EmailCampaign $emailCampaign)
    {
        $emailCampaign->load(['logs' => fn($q) => $q->with('user')->latest()->take(100)]);
        $failedCount = $emailCampaign->logs()->where('status', 'failed')->count();
        return view('admin.email-campaigns.show', compact('emailCampaign', 'failedCount'));
    }

    public function edit(EmailCampaign $emailCampaign)
    {
        abort_unless(in_array($emailCampaign->status, ['draft', 'scheduled']), 403, 'Cannot edit a sent campaign.');
        return view('admin.email-campaigns.edit', compact('emailCampaign'));
    }

    public function update(Request $request, EmailCampaign $emailCampaign)
    {
        abort_unless(in_array($emailCampaign->status, ['draft', 'scheduled']), 403);

        $data = $request->validate([
            'name'           => 'required|string|max:255',
            'subject'        => 'required|string|max:255',
            'preheader'      => 'nullable|string|max:255',
            'content'        => 'required|string',
            'from_name'      => 'nullable|string|max:100',
            'from_email'     => 'nullable|email',
            'recipient_type' => 'required|in:all,customers,new_30d,never_ordered',
            'scheduled_at'   => 'nullable|date|after:now',
        ]);

        $data['status'] = $request->filled('scheduled_at') ? 'scheduled' : 'draft';

        $campaign = new EmailCampaign($data);
        $data['recipient_count'] = $campaign->resolveRecipients()->count();

        $emailCampaign->update($data);

        return redirect()->route('admin.email-campaigns.show', $emailCampaign)->with('success', 'Campaign updated.');
    }

    public function send(Request $request, EmailCampaign $emailCampaign)
    {
        abort_unless(in_array($emailCampaign->status, ['draft', 'scheduled']), 403, 'Campaign already sent.');

        $emailCampaign->update(['status' => 'sending']);

        $recipients = $emailCampaign->resolveRecipients();
        $sent = 0;
        $failed = 0;
        $fromEmail = $emailCampaign->from_email ?: config('mail.from.address');
        $fromName  = $emailCampaign->from_name  ?: config('mail.from.name');

        foreach ($recipients as $user) {
            try {
                Mail::html($emailCampaign->content, function ($message) use ($user, $emailCampaign, $fromEmail, $fromName) {
                    $message->to($user->email, $user->name)
                        ->subject($emailCampaign->subject)
                        ->from($fromEmail, $fromName);
                });

                EmailCampaignLog::create([
                    'campaign_id' => $emailCampaign->id,
                    'user_id'     => $user->id,
                    'email'       => $user->email,
                    'status'      => 'sent',
                    'sent_at'     => now(),
                ]);
                $sent++;
            } catch (\Throwable $e) {
                EmailCampaignLog::create([
                    'campaign_id' => $emailCampaign->id,
                    'user_id'     => $user->id,
                    'email'       => $user->email,
                    'status'      => 'failed',
                    'error'       => $e->getMessage(),
                    'sent_at'     => now(),
                ]);
                $failed++;
            }
        }

        $emailCampaign->update([
            'status'       => 'sent',
            'sent_at'      => now(),
            'sent_count'   => $sent,
            'failed_count' => $failed,
        ]);

        AuditLogger::log('email_campaign.sent', "Campaign \"{$emailCampaign->name}\" sent to {$sent} recipients ({$failed} failed)", $emailCampaign);

        return back()->with('success', "Campaign sent! {$sent} delivered, {$failed} failed.");
    }

    public function destroy(EmailCampaign $emailCampaign)
    {
        AuditLogger::log('email_campaign.deleted', "Email campaign \"{$emailCampaign->name}\" deleted");
        $emailCampaign->delete();
        return redirect()->route('admin.email-campaigns.index')->with('success', 'Campaign deleted.');
    }
}
