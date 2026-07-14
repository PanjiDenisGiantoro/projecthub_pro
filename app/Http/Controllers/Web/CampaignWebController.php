<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Lead;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;

class CampaignWebController extends Controller
{
    public function index(Request $request)
    {
        $query = Campaign::with(['creator', 'owner', 'project'])
            ->when($request->status,  fn($q) => $q->where('status', $request->status))
            ->when($request->channel, fn($q) => $q->where('channel', $request->channel))
            ->when($request->search,  fn($q) => $q->where('name', 'like', '%'.$request->search.'%'));

        $campaigns = $query->latest()->paginate(15)->withQueryString();

        $stats = [
            'total'       => Campaign::count(),
            'active'      => Campaign::where('status', 'active')->count(),
            'total_leads' => Campaign::sum('leads_count'),
            'converted'   => Lead::whereHas('campaign')->where('status', 'client')->count(),
            'total_spend' => Campaign::sum('actual_spend'),
        ];
        $stats['conversion_rate'] = $stats['total_leads'] > 0
            ? round($stats['converted'] / $stats['total_leads'] * 100, 1) : 0;

        return view('campaigns.index', compact('campaigns', 'stats'));
    }

    public function create()
    {
        $projects = Project::where('status', 'active')->get(['id', 'name']);
        $users    = User::where('is_active', true)->get(['id', 'name']);
        return view('campaigns.create', compact('projects', 'users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:255',
            'channel'    => 'required|in:social_media,email,event,ads,seo,other',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
            'goal_leads' => 'nullable|integer|min:0',
            'budget'     => 'nullable|numeric|min:0',
        ]);

        Campaign::create([
            ...$request->only('name', 'description', 'channel', 'budget', 'target',
                'start_date', 'end_date', 'status', 'project_id', 'owner_id', 'goal_leads'),
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('campaigns.index')->with('success', 'Campaign berhasil dibuat.');
    }

    public function show(Campaign $campaign)
    {
        $campaign->load(['creator', 'owner', 'project', 'leads.assignee']);

        $leads = $campaign->leads;
        $funnel = [
            'lead'     => $leads->where('status', 'lead')->count(),
            'prospect' => $leads->where('status', 'prospect')->count(),
            'client'   => $leads->where('status', 'client')->count(),
            'lost'     => $leads->where('status', 'lost')->count(),
        ];
        $followUpDue = $leads->filter(fn($l) => $l->isFollowUpDue())->count();
        $totalValue  = $leads->where('status', 'client')->sum('value');
        $users       = User::where('is_active', true)->get(['id', 'name']);

        return view('campaigns.show', compact('campaign', 'funnel', 'followUpDue', 'totalValue', 'users'));
    }

    public function edit(Campaign $campaign)
    {
        $projects = Project::get(['id', 'name']);
        $users    = User::where('is_active', true)->get(['id', 'name']);
        return view('campaigns.edit', compact('campaign', 'projects', 'users'));
    }

    public function update(Request $request, Campaign $campaign)
    {
        $request->validate([
            'name'    => 'required|string|max:255',
            'channel' => 'required|in:social_media,email,event,ads,seo,other',
        ]);

        $campaign->update($request->only(
            'name', 'description', 'channel', 'budget', 'actual_spend',
            'target', 'start_date', 'end_date', 'status', 'project_id',
            'owner_id', 'goal_leads', 'impressions', 'clicks', 'reach'
        ));

        return redirect()->route('campaigns.show', $campaign)->with('success', 'Campaign diperbarui.');
    }

    public function destroy(Campaign $campaign)
    {
        $campaign->delete();
        return redirect()->route('campaigns.index')->with('success', 'Campaign dihapus.');
    }

    // ── Lead management ────────────────────────────────────────────────────────

    public function storeLead(Request $request, Campaign $campaign)
    {
        $request->validate([
            'name'    => 'required|string|max:255',
            'contact' => 'required|string|max:255',
            'score'   => 'nullable|integer|min:1|max:10',
            'value'   => 'nullable|numeric|min:0',
        ]);

        $campaign->leads()->create([
            ...$request->only('name', 'contact', 'email', 'phone', 'company',
                'source', 'score', 'value', 'notes', 'assigned_to', 'follow_up_at'),
            'status' => 'lead',
        ]);

        $campaign->increment('leads_count');
        return back()->with('success', 'Lead ditambahkan.');
    }

    public function updateLead(Request $request, Lead $lead)
    {
        $this->authorizeCompany($lead->campaign->company_id);

        $oldStatus = $lead->status;
        $updates   = $request->only('name', 'contact', 'email', 'phone', 'company',
            'source', 'score', 'value', 'status', 'notes', 'lost_reason',
            'assigned_to', 'follow_up_at', 'last_contacted_at');

        if ($request->status === 'client' && $oldStatus !== 'client') {
            $updates['converted_to_client_at'] = now();
        }
        if ($request->status === 'lost' && $oldStatus !== 'lost') {
            $updates['follow_up_at'] = null;
        }

        $lead->update($updates);
        return back()->with('success', 'Lead diperbarui.');
    }

    public function destroyLead(Lead $lead)
    {
        $campaign = $lead->campaign;
        $this->authorizeCompany($campaign->company_id);
        $lead->delete();
        if ($campaign) $campaign->decrement('leads_count');
        return back()->with('success', 'Lead dihapus.');
    }

    public function bulkUpdateLeads(Request $request, Campaign $campaign)
    {
        $request->validate([
            'lead_ids'   => 'required|array',
            'lead_ids.*' => 'integer',
            'status'     => 'required|in:lead,prospect,client,lost',
        ]);

        $updates = ['status' => $request->status];
        if ($request->status === 'client') $updates['converted_to_client_at'] = now();

        Lead::whereIn('id', $request->lead_ids)
            ->where('campaign_id', $campaign->id)
            ->update($updates);

        return back()->with('success', 'Lead berhasil diperbarui.');
    }

    public function updateMetrics(Request $request, Campaign $campaign)
    {
        $request->validate([
            'impressions'  => 'nullable|integer|min:0',
            'clicks'       => 'nullable|integer|min:0',
            'reach'        => 'nullable|integer|min:0',
            'actual_spend' => 'nullable|numeric|min:0',
        ]);

        $campaign->update($request->only('impressions', 'clicks', 'reach', 'actual_spend'));
        return back()->with('success', 'Metrik campaign diperbarui.');
    }
}
