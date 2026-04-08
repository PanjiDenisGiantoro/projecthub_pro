<?php

namespace App\Http\Controllers;

use App\Models\SlaPolicy;
use Illuminate\Http\Request;

class SlaPolicyController extends Controller
{
    public function index(Request $request)
    {
        $policies = SlaPolicy::with('project')
            ->when($request->project_id, fn($q) => $q->where('project_id', $request->project_id))
            ->get();

        return response()->json($policies);
    }

    public function store(Request $request)
    {
        $request->validate([
            'priority' => 'required|in:critical,high,medium,low',
            'response_minutes' => 'required|integer|min:1',
            'resolution_minutes' => 'required|integer|min:1',
            'escalation_at_percent' => 'integer|min:1|max:100',
            'business_hours_only' => 'boolean',
            'project_id' => 'nullable|exists:projects,id',
        ]);

        $policy = SlaPolicy::create([
            ...$request->only('project_id', 'priority', 'response_minutes', 'resolution_minutes', 'escalation_at_percent', 'business_hours_only'),
            'created_by' => $request->user()->id,
        ]);

        return response()->json($policy, 201);
    }

    public function update(Request $request, SlaPolicy $slaPolicy)
    {
        $request->validate([
            'response_minutes' => 'sometimes|integer|min:1',
            'resolution_minutes' => 'sometimes|integer|min:1',
            'escalation_at_percent' => 'sometimes|integer|min:1|max:100',
        ]);

        $slaPolicy->update($request->only(
            'response_minutes', 'resolution_minutes', 'escalation_at_percent', 'business_hours_only'
        ));

        return response()->json($slaPolicy);
    }

    public function destroy(SlaPolicy $slaPolicy)
    {
        $slaPolicy->delete();
        return response()->json(['message' => 'SLA policy deleted.']);
    }
}
