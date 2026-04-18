<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Risk;
use Illuminate\Http\Request;

class RiskWebController extends Controller
{
    public function index(Project $project)
    {
        $risks = $project->risks()->with('creator')->orderByDesc('id')->get();
        return view('risks.index', compact('project', 'risks'));
    }

    public function store(Request $request, Project $project)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|in:technical,schedule,resource,budget,external,other',
            'probability' => 'required|integer|min:1|max:5',
            'impact' => 'required|integer|min:1|max:5',
            'status' => 'required|in:open,mitigated,accepted,closed',
            'mitigation_plan' => 'nullable|string',
            'owner' => 'nullable|string|max:100',
        ]);

        $data['project_id'] = $project->id;
        $data['created_by'] = auth()->id();

        Risk::create($data);

        return back()->with('success', 'Risiko berhasil ditambahkan.');
    }

    public function update(Request $request, Project $project, Risk $risk)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|in:technical,schedule,resource,budget,external,other',
            'probability' => 'required|integer|min:1|max:5',
            'impact' => 'required|integer|min:1|max:5',
            'status' => 'required|in:open,mitigated,accepted,closed',
            'mitigation_plan' => 'nullable|string',
            'owner' => 'nullable|string|max:100',
        ]);

        $risk->update($data);

        return back()->with('success', 'Risiko berhasil diperbarui.');
    }

    public function destroy(Project $project, Risk $risk)
    {
        $risk->delete();
        return back()->with('success', 'Risiko dihapus.');
    }

    public function matrix(Project $project)
    {
        $risks = $project->risks()->where('status', '!=', 'closed')->get(['probability', 'impact', 'title', 'id']);
        return response()->json($risks->map(fn($r) => [
            'id' => $r->id,
            'title' => $r->title,
            'x' => $r->probability,
            'y' => $r->impact,
        ]));
    }
}
