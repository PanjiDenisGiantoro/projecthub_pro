<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Lead;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public function index(Request $request)
    {
        $campaigns = Campaign::with(['creator', 'project'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->project_id, fn($q) => $q->where('project_id', $request->project_id))
            ->latest()
            ->paginate(15);

        return response()->json($campaigns);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'channel' => 'in:social_media,email,event,ads,seo,other',
            'budget' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'project_id' => 'nullable|exists:projects,id',
        ]);

        $campaign = Campaign::create([
            ...$request->only('name', 'channel', 'budget', 'target', 'start_date', 'end_date', 'status', 'project_id'),
            'created_by' => $request->user()->id,
        ]);

        return response()->json($campaign->load(['creator', 'project']), 201);
    }

    public function show(Campaign $campaign)
    {
        return response()->json($campaign->load(['creator', 'project', 'leads'])
            ->append('conversion_rate'));
    }

    public function update(Request $request, Campaign $campaign)
    {
        $campaign->update($request->only(
            'name', 'channel', 'budget', 'target', 'start_date', 'end_date', 'status', 'project_id', 'impressions'
        ));

        return response()->json($campaign->fresh());
    }

    public function destroy(Campaign $campaign)
    {
        $campaign->delete();
        return response()->json(['message' => 'Campaign deleted.']);
    }

    public function storeLead(Request $request, Campaign $campaign)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'contact' => 'required|string|max:255',
        ]);

        $lead = $campaign->leads()->create([
            ...$request->only('name', 'contact', 'company', 'notes', 'assigned_to'),
            'status' => 'lead',
        ]);

        $campaign->increment('leads_count');

        return response()->json($lead, 201);
    }

    public function leads(Request $request)
    {
        $leads = Lead::with(['campaign', 'assignee'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->campaign_id, fn($q) => $q->where('campaign_id', $request->campaign_id))
            ->latest()
            ->paginate(20);

        return response()->json($leads);
    }

    public function updateLead(Request $request, Lead $lead)
    {
        $request->validate(['status' => 'in:lead,prospect,client,lost']);

        $updates = $request->only('name', 'contact', 'company', 'status', 'notes', 'assigned_to');

        if ($request->status === 'client' && $lead->status !== 'client') {
            $updates['converted_to_client_at'] = now();
        }

        $lead->update($updates);

        return response()->json($lead->fresh());
    }
}
