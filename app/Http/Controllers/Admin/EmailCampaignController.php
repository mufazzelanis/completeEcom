<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailCampaign;
use App\Models\EmailCampaignLog;
use App\Services\AuditLogger;
use App\Services\EmailCampaignSender;
use Illuminate\Http\Request;

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

        EmailCampaignSender::startSending($emailCampaign);
        EmailCampaignSender::processBatch($emailCampaign);
        $emailCampaign->refresh();

        AuditLogger::log(
            'email_campaign.sent',
            "Campaign \"{$emailCampaign->name}\" started sending to {$emailCampaign->recipient_count} recipients",
            $emailCampaign
        );

        if ($emailCampaign->status === 'sent') {
            return back()->with('success', "Campaign sent! {$emailCampaign->sent_count} delivered, {$emailCampaign->failed_count} failed.");
        }

        return back()->with('success', "Sending started — {$emailCampaign->sent_count} of {$emailCampaign->recipient_count} sent so far. The rest will go out automatically over the next few minutes.");
    }

    public function destroy(EmailCampaign $emailCampaign)
    {
        AuditLogger::log('email_campaign.deleted', "Email campaign \"{$emailCampaign->name}\" deleted");
        $emailCampaign->delete();
        return redirect()->route('admin.email-campaigns.index')->with('success', 'Campaign deleted.');
    }
}
