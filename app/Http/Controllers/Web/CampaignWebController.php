<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Lead;
use App\Models\Project;
use Illuminate\Http\Request;

class CampaignWebController extends Controller
{
    public function index(Request $request)
    {
        $campaigns = Campaign::with(['creator', 'project'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()->paginate(15);
        return view('campaigns.index', compact('campaigns'));
    }

    public function create()
    {
        $projects = Project::where('status', 'active')->get(['id', 'name']);
        return view('campaigns.create', compact('projects'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:255',
            'channel'    => 'in:social_media,email,event,ads,seo,other',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
        ]);

        Campaign::create([...$request->only('name', 'channel', 'budget', 'target', 'start_date', 'end_date', 'status', 'project_id'), 'created_by' => auth()->id()]);
        return redirect()->route('campaigns.index')->with('success', 'Campaign dibuat.');
    }

    public function show(Campaign $campaign)
    {
        $campaign->load(['creator', 'project', 'leads']);
        return view('campaigns.show', compact('campaign'));
    }

    public function edit(Campaign $campaign)
    {
        $projects = Project::get(['id', 'name']);
        return view('campaigns.edit', compact('campaign', 'projects'));
    }

    public function update(Request $request, Campaign $campaign)
    {
        $campaign->update($request->only('name', 'channel', 'budget', 'target', 'start_date', 'end_date', 'status', 'project_id', 'impressions'));
        return redirect()->route('campaigns.show', $campaign)->with('success', 'Campaign diperbarui.');
    }

    public function destroy(Campaign $campaign)
    {
        $campaign->delete();
        return redirect()->route('campaigns.index')->with('success', 'Campaign dihapus.');
    }

    public function storeLead(Request $request, Campaign $campaign)
    {
        $request->validate(['name' => 'required|string', 'contact' => 'required|string']);
        $campaign->leads()->create([...$request->only('name', 'contact', 'company', 'notes'), 'status' => 'lead']);
        $campaign->increment('leads_count');
        return back()->with('success', 'Lead ditambahkan.');
    }

    public function updateLead(Request $request, Lead $lead)
    {
        $updates = $request->only('name', 'contact', 'company', 'status', 'notes');
        if ($request->status === 'client' && $lead->status !== 'client') {
            $updates['converted_to_client_at'] = now();
        }
        $lead->update($updates);
        return back()->with('success', 'Lead diperbarui.');
    }
}
