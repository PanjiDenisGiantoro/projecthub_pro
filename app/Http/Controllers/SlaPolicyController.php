<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Web\TicketSlaWebController;
use App\Models\Project;
use App\Models\SlaPolicy;
use Illuminate\Http\Request;

/**
 * API aturan SLA tiket.
 *
 * - Aturan default sistem (project_id NULL) dipakai semua perusahaan → hanya
 *   super admin yang boleh membuat/mengubah/menghapus.
 * - Aturan proyek hanya untuk proyek di perusahaan user (global scope Project)
 *   dan hanya oleh yang boleh mengelola SLA proyek itu (manage tickets / manager proyek).
 */
class SlaPolicyController extends Controller
{
    private function authorizePolicyScope(?int $projectId): void
    {
        $user = auth()->user();
        if ($projectId === null) {
            abort_unless($user->is_super_admin, 403, 'Aturan SLA default sistem hanya bisa diubah super admin.');
            return;
        }

        $project = Project::find($projectId); // global scope company
        abort_unless($project && TicketSlaWebController::canManage($project), 403, 'Anda tidak punya akses mengubah aturan SLA proyek ini.');
    }

    public function index(Request $request)
    {
        $projectIds = Project::query()->pluck('projects.id'); // proyek di company user

        $policies = SlaPolicy::with('project:id,name')
            ->where(fn ($q) => $q->whereNull('project_id')->orWhereIn('project_id', $projectIds))
            ->when($request->project_id, fn ($q) => $q->where(fn ($q2) => $q2->where('project_id', $request->project_id)->orWhereNull('project_id')))
            ->orderByRaw('project_id is null')->orderBy('project_id')
            ->orderByRaw("field(priority, 'critical', 'high', 'medium', 'low')")
            ->get();

        return response()->json($policies);
    }

    public function store(Request $request)
    {
        $request->validate([
            'priority'              => 'required|in:critical,high,medium,low',
            'response_minutes'      => 'required|integer|min:1',
            'resolution_minutes'    => 'required|integer|min:1',
            'escalation_at_percent' => 'integer|min:1|max:100',
            'business_hours_only'   => 'boolean',
            'project_id'            => 'nullable|integer',
        ]);
        $this->authorizePolicyScope($request->project_id ? (int) $request->project_id : null);

        $policy = SlaPolicy::updateOrCreate(
            ['project_id' => $request->project_id, 'priority' => $request->priority],
            [
                ...$request->only('response_minutes', 'resolution_minutes', 'escalation_at_percent', 'business_hours_only'),
                'created_by' => $request->user()->id,
            ]
        );

        return response()->json($policy, 201);
    }

    public function update(Request $request, SlaPolicy $slaPolicy)
    {
        $this->authorizePolicyScope($slaPolicy->project_id);

        $request->validate([
            'response_minutes'      => 'sometimes|integer|min:1',
            'resolution_minutes'    => 'sometimes|integer|min:1',
            'escalation_at_percent' => 'sometimes|integer|min:1|max:100',
            'business_hours_only'   => 'sometimes|boolean',
        ]);

        $slaPolicy->update($request->only(
            'response_minutes', 'resolution_minutes', 'escalation_at_percent', 'business_hours_only'
        ));

        return response()->json($slaPolicy);
    }

    public function destroy(SlaPolicy $slaPolicy)
    {
        $this->authorizePolicyScope($slaPolicy->project_id);

        $slaPolicy->delete();
        return response()->json(['message' => 'SLA policy deleted.']);
    }
}
